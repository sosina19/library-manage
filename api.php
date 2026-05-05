<?php
require 'auth.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        echo json_encode(['success' => true, 'role' => $_SESSION['role']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    exit();
}

if ($action === 'logout') {
    logout();
    echo json_encode(['success' => true]);
    exit();
}
  
if ($action === 'signup') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($username === '' || $password === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    $db = getDB();

    // check if username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :u");
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $result = $stmt->execute();

    if ($result->fetchArray()) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // signup role always normal user
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, email) VALUES (:u, :p, 'user', :e)");
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':p', $hashed, SQLITE3_TEXT);
    $stmt->bindValue(':e', $email, SQLITE3_TEXT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Signup failed']);
    }
    exit();
}

// Ensure logged in for other actions
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = getDB();
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {

        // ============================
        // DASHBOARD STATS (ADMIN ONLY)
        // ============================
        case 'dashboard_stats':
            requireRole('admin');

            // total books
            $totalBooks = $db->querySingle("SELECT COUNT(*) FROM books");

            // available books
            $availableBooks = $db->querySingle("SELECT COUNT(*) FROM books WHERE status = 'Available'");

            // borrowed books
            $borrowedBooks = $db->querySingle("SELECT COUNT(*) FROM books WHERE status = 'Borrowed'");

            // total users
            $totalUsers = $db->querySingle("SELECT COUNT(*) FROM users");

            // grouped users by role
            $admins = $db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $librarians = $db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'librarian'");
            $users = $db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'user'");

            echo json_encode([
                'success' => true,
                'data' => [
                    'total_books' => $totalBooks,
                    'available_books' => $availableBooks,
                    'borrowed_books' => $borrowedBooks,
                    'total_users' => $totalUsers,
                    'admins' => $admins,
                    'librarians' => $librarians,
                    'users' => $users
                ]
            ]);
            break;


        // ===================================
        // RECENT ACTIVITIES (ADMIN ONLY)
        // ===================================
        case 'recent_activities':
            requireRole('admin');

            $result = $db->query("
                SELECT 
                    t.id,
                    u.username,
                    b.title AS book_title,
                    CASE 
                        WHEN t.status = 'Returned' THEN 'return'
                        ELSE 'borrow'
                    END AS action,
                    COALESCE(t.return_date, t.borrow_date) AS date
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                JOIN books b ON t.book_id = b.id
                ORDER BY date DESC
                LIMIT 10
            ");

            $activities = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $activities[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $activities]);
            break;


        // --- ADMIN ENDPOINTS ---
       case 'get_users':
    requireRole('admin');
            $filter = $_POST['filter'] ?? 'all';
            $search = trim($_POST['search'] ?? '');
            $query = "SELECT id, username, role, email, created_at FROM users WHERE 1=1";
            // Role filter
            if ($filter !== 'all') {
                $query .= " AND role = :role";
            }
            // Search filter (username OR email)
            if ($search !== '') {
                $query .= " AND (username LIKE :search OR email LIKE :search)";
            }
           $stmt = $db->prepare($query);
            if ($filter !== 'all') {
                $stmt->bindValue(':role', $filter, SQLITE3_TEXT);
            }
            if ($search !== '') {
                $stmt->bindValue(':search', "%$search%", SQLITE3_TEXT);
            }
            $result = $stmt->execute();
            $users = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $users[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'add_user':
            requireRole('admin');
            $u = $_POST['username'];
            $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $r = $_POST['role'];
            $e = $_POST['email'];
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, email) VALUES (:u, :p, :r, :e)");
            $stmt->bindValue(':u', $u, SQLITE3_TEXT);
            $stmt->bindValue(':p', $p, SQLITE3_TEXT);
            $stmt->bindValue(':r', $r, SQLITE3_TEXT);
            $stmt->bindValue(':e', $e, SQLITE3_TEXT);
            if($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add user. Username might be taken.']);
            }
            break;
            
        case 'update_user':
            requireRole('admin');
            $id = $_POST['id'];
            $r = $_POST['role'];
            $e = $_POST['email'];
            $stmt = $db->prepare("UPDATE users SET role = :r, email = :e WHERE id = :id");
            $stmt->bindValue(':r', $r, SQLITE3_TEXT);
            $stmt->bindValue(':e', $e, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'delete_user':
            requireRole('admin');
            $id = $_POST['id'];
            if ($id == $user_id) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
                exit();
            }
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;


        // --- LIBRARIAN / ADMIN / USER ENDPOINTS (Books management) ---
        case 'get_books':
            $search = trim($_POST['search'] ?? '');
            $filter = trim($_POST['filter'] ?? 'all');

            $query = "SELECT * FROM books WHERE 1=1";

            if ($search !== '') {
                $query .= " AND (title LIKE :s OR author LIKE :s OR isbn LIKE :s)";
            }

            if ($filter === 'available') {
                $query .= " AND status = 'Available'";
            } elseif ($filter === 'borrowed') {
                $query .= " AND status = 'Borrowed'";
            }

            $query .= " ORDER BY id DESC";

            $stmt = $db->prepare($query);

            if ($search !== '') {
                $stmt->bindValue(':s', '%' . $search . '%', SQLITE3_TEXT);
            }

            $result = $stmt->execute();

            $books = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $books[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $books]);
            break;


        case 'add_book':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $t = $_POST['title'];
            $a = $_POST['author'];
            $i = $_POST['isbn'];
            $y = $_POST['published_year'];
            $stmt = $db->prepare("INSERT INTO books (title, author, isbn, published_year) VALUES (:t, :a, :i, :y)");
            $stmt->bindValue(':t', $t, SQLITE3_TEXT);
            $stmt->bindValue(':a', $a, SQLITE3_TEXT);
            $stmt->bindValue(':i', $i, SQLITE3_TEXT);
            $stmt->bindValue(':y', $y, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'update_book':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $id = $_POST['id'];
            $t = $_POST['title'];
            $a = $_POST['author'];
            $i = $_POST['isbn'];
            $y = $_POST['published_year'];
            $stmt = $db->prepare("UPDATE books SET title=:t, author=:a, isbn=:i, published_year=:y WHERE id=:id");
            $stmt->bindValue(':t', $t, SQLITE3_TEXT);
            $stmt->bindValue(':a', $a, SQLITE3_TEXT);
            $stmt->bindValue(':i', $i, SQLITE3_TEXT);
            $stmt->bindValue(':y', $y, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'delete_book':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $id = $_POST['id'];
            $stmt = $db->prepare("DELETE FROM books WHERE id=:id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;


        // --- TRANSACTIONS ---
        case 'get_transactions':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $result = $db->query("
                SELECT t.id, t.borrow_date, t.return_date, t.status, b.title as book_title, u.username 
                FROM transactions t 
                JOIN books b ON t.book_id = b.id 
                JOIN users u ON t.user_id = u.id
                ORDER BY t.borrow_date DESC
            ");
            $transactions = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $transactions[] = $row; }
            echo json_encode(['success' => true, 'data' => $transactions]);
            break;


        // --- USER ENDPOINTS ---
        case 'borrow_book':
            requireRole('user');
            $book_id = $_POST['book_id'];
            
            // Check availability
            $stmt = $db->prepare("SELECT status FROM books WHERE id = :id");
            $stmt->bindValue(':id', $book_id, SQLITE3_INTEGER);
            $b = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            
            if ($b && $b['status'] === 'Available') {
                $db->exec('BEGIN');
                $stmt1 = $db->prepare("UPDATE books SET status = 'Borrowed' WHERE id = :id");
                $stmt1->bindValue(':id', $book_id, SQLITE3_INTEGER);
                $stmt1->execute();
                
                $stmt2 = $db->prepare("INSERT INTO transactions (book_id, user_id) VALUES (:bid, :uid)");
                $stmt2->bindValue(':bid', $book_id, SQLITE3_INTEGER);
                $stmt2->bindValue(':uid', $user_id, SQLITE3_INTEGER);
                $stmt2->execute();
                $db->exec('COMMIT');
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Book not available']);
            }
            break;

        case 'return_book':
            requireRole('user');
            $book_id = $_POST['book_id'];
            
            $db->exec('BEGIN');
            $stmt1 = $db->prepare("UPDATE books SET status = 'Available' WHERE id = :id");
            $stmt1->bindValue(':id', $book_id, SQLITE3_INTEGER);
            $stmt1->execute();
            
            $stmt2 = $db->prepare("UPDATE transactions SET status = 'Returned', return_date = CURRENT_TIMESTAMP WHERE book_id = :bid AND user_id = :uid AND status = 'Borrowed'");
            $stmt2->bindValue(':bid', $book_id, SQLITE3_INTEGER);
            $stmt2->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt2->execute();
            $db->exec('COMMIT');
            
            echo json_encode(['success' => true]);
            break;

        case 'get_my_transactions':
            requireRole('user');
            $stmt = $db->prepare("
                SELECT t.id, t.borrow_date, t.return_date, t.status, b.title as book_title, b.id as book_id
                FROM transactions t 
                JOIN books b ON t.book_id = b.id 
                WHERE t.user_id = :uid
                ORDER BY t.borrow_date DESC
            ");
            $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $transactions = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $transactions[] = $row; }
            echo json_encode(['success' => true, 'data' => $transactions]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>