<?php
require 'auth.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function ensureAppSchema(SQLite3 $db): void {
    // Keep runtime schema compatible with existing databases.
    @$db->exec("ALTER TABLE transactions ADD COLUMN due_date DATETIME");
    @$db->exec("ALTER TABLE transactions ADD COLUMN fine_amount REAL DEFAULT 0");
    @$db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        target_role TEXT,
        type TEXT NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        event_key TEXT UNIQUE,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

function createNotification(SQLite3 $db, ?int $userId, ?string $targetRole, string $type, string $title, string $message, ?string $eventKey = null): void {
    $stmt = $db->prepare("
        INSERT OR IGNORE INTO notifications (user_id, target_role, type, title, message, event_key)
        VALUES (:user_id, :target_role, :type, :title, :message, :event_key)
    ");
    if ($userId === null) {
        $stmt->bindValue(':user_id', null, SQLITE3_NULL);
    } else {
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    }
    if ($targetRole === null) {
        $stmt->bindValue(':target_role', null, SQLITE3_NULL);
    } else {
        $stmt->bindValue(':target_role', $targetRole, SQLITE3_TEXT);
    }
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    if ($eventKey === null) {
        $stmt->bindValue(':event_key', null, SQLITE3_NULL);
    } else {
        $stmt->bindValue(':event_key', $eventKey, SQLITE3_TEXT);
    }
    $stmt->execute();
}

function createRoleNotification(SQLite3 $db, string $role, string $type, string $title, string $message, ?string $eventKey = null): void {
    createNotification($db, null, $role, $type, $title, $message, $eventKey);
}

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        echo json_encode(['success' => true, 'role' => $_SESSION['role']]);
    } else {
        $db = getDB();
        ensureAppSchema($db);
        createRoleNotification(
            $db,
            'admin',
            'unauthorized_login',
            'Unauthorized login attempt',
            "Failed login attempt for username '{$username}'.",
            'unauth_login_' . md5($username . '_' . date('Y-m-d-H'))
        );
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
    ensureAppSchema($db);

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
        $newUserId = $db->lastInsertRowID();
        createRoleNotification(
            $db,
            'admin',
            'new_registration',
            'New user registration',
            "User '{$username}' registered with email '{$email}'.",
            'new_registration_' . $newUserId
        );
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
ensureAppSchema($db);
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

        // ============================
        // DASHBOARD STATS (LIBRARIAN / ADMIN)
        // ============================
        case 'librarian_dashboard_stats':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }

            $totalBooks = $db->querySingle("SELECT COUNT(*) FROM books");
            $availableBooks = $db->querySingle("SELECT COUNT(*) FROM books WHERE status = 'Available'");
            $borrowedBooks = $db->querySingle("SELECT COUNT(*) FROM books WHERE status = 'Borrowed'");
            $totalUsers = $db->querySingle("SELECT COUNT(*) FROM users");

            $overdueBooks = $db->querySingle("
                SELECT COUNT(*)
                FROM transactions
                WHERE status = 'Borrowed'
                AND date(due_date) < date('now')
            ");

            $dueSoonBooks = $db->querySingle("
                SELECT COUNT(*)
                FROM transactions
                WHERE status = 'Borrowed'
                AND date(due_date) >= date('now')
                AND date(due_date) <= date('now', '+3 day')
            ");

            echo json_encode([
                'success' => true,
                'data' => [
                    'total_books' => $totalBooks,
                    'available_books' => $availableBooks,
                    'borrowed_books' => $borrowedBooks,
                    'total_users' => $totalUsers,
                    'overdue_books' => $overdueBooks,
                    'due_soon_books' => $dueSoonBooks
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

        case 'get_notifications':
            // Generate fresh due/overdue reminders before returning.
            if ($role === 'user') {
                $stmt = $db->prepare("
                    SELECT t.id, b.title, t.due_date, t.fine_amount
                    FROM transactions t
                    JOIN books b ON b.id = t.book_id
                    WHERE t.user_id = :uid AND t.status = 'Borrowed'
                ");
                $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
                $res = $stmt->execute();
                while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                    $daysLeft = (int)floor((strtotime($row['due_date']) - time()) / 86400);
                    if ($daysLeft < 0) {
                        $over = abs($daysLeft);
                        createNotification(
                            $db,
                            $user_id,
                            null,
                            'overdue_warning',
                            'Overdue warning',
                            "⚠️ '{$row['title']}' is overdue by {$over} day(s).",
                            'user_overdue_' . $row['id'] . '_' . date('Y-m-d')
                        );
                    } elseif ($daysLeft <= 2) {
                        createNotification(
                            $db,
                            $user_id,
                            null,
                            'due_reminder',
                            'Due date reminder',
                            "📅 '{$row['title']}' is due in {$daysLeft} day(s).",
                            'user_due_soon_' . $row['id'] . '_' . date('Y-m-d')
                        );
                    }
                    if ((float)($row['fine_amount'] ?? 0) > 0) {
                        createNotification(
                            $db,
                            $user_id,
                            null,
                            'fine_updated',
                            'Fine updated',
                            "💰 Fine for '{$row['title']}' is now {$row['fine_amount']}.",
                            'user_fine_' . $row['id'] . '_' . $row['fine_amount']
                        );
                    }
                }
            } elseif ($role === 'librarian') {
                $result = $db->query("
                    SELECT t.id, b.title, u.username, t.due_date
                    FROM transactions t
                    JOIN books b ON b.id = t.book_id
                    JOIN users u ON u.id = t.user_id
                    WHERE t.status = 'Borrowed'
                ");
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $daysLeft = (int)floor((strtotime($row['due_date']) - time()) / 86400);
                    if ($daysLeft < 0) {
                        $over = abs($daysLeft);
                        createRoleNotification(
                            $db,
                            'librarian',
                            'overdue_list',
                            'Overdue book',
                            "⚠️ '{$row['title']}' kept by {$row['username']} is overdue by {$over} day(s).",
                            'lib_overdue_' . $row['id'] . '_' . date('Y-m-d')
                        );
                    } elseif ($daysLeft <= 2) {
                        createRoleNotification(
                            $db,
                            'librarian',
                            'due_soon',
                            'Book due soon',
                            "⏳ '{$row['title']}' for {$row['username']} is due in {$daysLeft} day(s).",
                            'lib_due_soon_' . $row['id'] . '_' . date('Y-m-d')
                        );
                    }
                }
            }

            $stmt = $db->prepare("
                SELECT id, type, title, message, is_read, created_at
                FROM notifications
                WHERE (user_id = :uid) OR (target_role = :role)
                ORDER BY datetime(created_at) DESC
                LIMIT 100
            ");
            $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':role', $role, SQLITE3_TEXT);
            $result = $stmt->execute();
            $notifications = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $notifications[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $notifications]);
            break;

        case 'mark_notification_read':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = :id
                AND (user_id = :uid OR target_role = :role)
            ");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':role', $role, SQLITE3_TEXT);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'mark_all_notifications_read':
            $stmt = $db->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE (user_id = :uid) OR (target_role = :role)
            ");
            $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':role', $role, SQLITE3_TEXT);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'clear_notifications':
            $stmt = $db->prepare("
                DELETE FROM notifications
                WHERE (user_id = :uid) OR (target_role = :role)
            ");
            $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':role', $role, SQLITE3_TEXT);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'add_announcement':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $title = trim($_POST['title'] ?? 'Library announcement');
            $message = trim($_POST['message'] ?? '');
            if ($message === '') {
                echo json_encode(['success' => false, 'message' => 'Announcement message is required']);
                break;
            }
            createRoleNotification(
                $db,
                'user',
                'library_announcement',
                $title,
                "📢 {$message}",
                'announcement_' . md5($title . '_' . $message . '_' . date('Y-m-d-H-i-s'))
            );
            echo json_encode(['success' => true]);
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
                $newUserId = $db->lastInsertRowID();
                createRoleNotification(
                    $db,
                    'admin',
                    'librarian_changed',
                    'User created',
                    "👤 New {$r} account '{$u}' was created.",
                    'admin_user_created_' . $newUserId
                );
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
            $beforeStmt = $db->prepare("SELECT username, role FROM users WHERE id = :id");
            $beforeStmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $before = $beforeStmt->execute()->fetchArray(SQLITE3_ASSOC);
            $stmt = $db->prepare("UPDATE users SET role = :r, email = :e WHERE id = :id");
            $stmt->bindValue(':r', $r, SQLITE3_TEXT);
            $stmt->bindValue(':e', $e, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            if ($before && $before['role'] !== $r) {
                createRoleNotification(
                    $db,
                    'admin',
                    'role_change',
                    'Role change',
                    "🔑 {$before['username']} role changed from {$before['role']} to {$r}.",
                    'admin_role_change_' . $id . '_' . $r
                );
                createNotification(
                    $db,
                    (int)$id,
                    null,
                    'role_change',
                    'Role updated',
                    "🔑 Your role is now '{$r}'.",
                    'user_role_change_' . $id . '_' . $r
                );
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_user':
            requireRole('admin');
            $id = $_POST['id'];
            if ($id == $user_id) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
                exit();
            }
            $userStmt = $db->prepare("SELECT username, role FROM users WHERE id = :id");
            $userStmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $deletedUser = $userStmt->execute()->fetchArray(SQLITE3_ASSOC);
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            if ($deletedUser) {
                createRoleNotification(
                    $db,
                    'admin',
                    'deletion_log',
                    'User deleted',
                    "🗑️ {$deletedUser['role']} '{$deletedUser['username']}' was deleted.",
                    'admin_delete_user_' . $id
                );
            }
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
            $bookId = $db->lastInsertRowID();
            createRoleNotification(
                $db,
                'librarian',
                'new_book_added',
                'New book added',
                "📚 '{$t}' by {$a} was added to catalog.",
                'lib_book_added_' . $bookId
            );
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
            $bookStmt = $db->prepare("SELECT title FROM books WHERE id=:id");
            $bookStmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $book = $bookStmt->execute()->fetchArray(SQLITE3_ASSOC);
            $stmt = $db->prepare("DELETE FROM books WHERE id=:id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            if ($book) {
                createRoleNotification(
                    $db,
                    'admin',
                    'deletion_log',
                    'Book deleted',
                    "🗑️ Book '{$book['title']}' was deleted.",
                    'admin_delete_book_' . $id
                );
            }
            echo json_encode(['success' => true]);
            break;


        // --- TRANSACTIONS ---
        case 'get_transactions':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $result = $db->query("
                SELECT t.id, t.borrow_date, t.return_date, t.due_date, t.status, b.title as book_title, u.username, u.id as user_id, t.book_id
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
            $stmt = $db->prepare("SELECT title, status FROM books WHERE id = :id");
            $stmt->bindValue(':id', $book_id, SQLITE3_INTEGER);
            $b = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            
            if ($b && $b['status'] === 'Available') {
                $db->exec('BEGIN');
                $stmt1 = $db->prepare("UPDATE books SET status = 'Borrowed' WHERE id = :id");
                $stmt1->bindValue(':id', $book_id, SQLITE3_INTEGER);
                $stmt1->execute();
                
                $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));

            $stmt2 = $db->prepare("
                INSERT INTO transactions (book_id, user_id, borrow_date, due_date, status)
                VALUES (:bid, :uid, CURRENT_TIMESTAMP, :due, 'Borrowed')
            ");

            $stmt2->bindValue(':bid', $book_id, SQLITE3_INTEGER);
            $stmt2->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt2->bindValue(':due', $due_date, SQLITE3_TEXT);
                $stmt2->execute();
                $transactionId = $db->lastInsertRowID();
                $db->exec('COMMIT');
                createNotification(
                    $db,
                    $user_id,
                    null,
                    'book_issued',
                    'Book issued successfully',
                    "✅ '{$b['title']}' has been issued. Due date: {$due_date}.",
                    'user_issued_' . $transactionId
                );
                createRoleNotification(
                    $db,
                    'librarian',
                    'new_borrow',
                    'New borrow recorded',
                    "🆕 User #{$user_id} borrowed '{$b['title']}'.",
                    'lib_new_borrow_' . $transactionId
                );
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Book not available']);
            }
            break;

        case 'request_borrow':
            requireRole('user');
            $book_id = (int)($_POST['book_id'] ?? 0);
            $bookStmt = $db->prepare("SELECT title, status FROM books WHERE id = :id");
            $bookStmt->bindValue(':id', $book_id, SQLITE3_INTEGER);
            $book = $bookStmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (!$book || $book['status'] !== 'Available') {
                echo json_encode(['success' => false, 'message' => 'Book is not available']);
                break;
            }
            $activeStmt = $db->prepare("
                SELECT COUNT(*)
                FROM transactions
                WHERE book_id = :bid AND status IN ('Pending', 'Borrowed', 'Return Pending')
            ");
            $activeStmt->bindValue(':bid', $book_id, SQLITE3_INTEGER);
            $active = (int)$activeStmt->execute()->fetchArray(SQLITE3_NUM)[0];
            if ($active > 0) {
                echo json_encode(['success' => false, 'message' => 'A request is already in progress for this book']);
                break;
            }
            $stmt = $db->prepare("
                INSERT INTO transactions (book_id, user_id, borrow_date, status)
                VALUES (:bid, :uid, CURRENT_TIMESTAMP, 'Pending')
            ");
            $stmt->bindValue(':bid', $book_id, SQLITE3_INTEGER);
            $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt->execute();
            $txId = $db->lastInsertRowID();
            createNotification(
                $db, $user_id, null, 'request_pending', 'Request pending approval',
                "⏳ Borrow request submitted for '{$book['title']}'. Waiting for librarian approval.",
                'user_request_pending_' . $txId
            );
            createRoleNotification(
                $db, 'librarian', 'borrow_request', 'New borrow request',
                "🆕 Borrow request #{$txId} is waiting for approval.",
                'lib_borrow_request_' . $txId
            );
            echo json_encode(['success' => true]);
            break;

        case 'return_book':
            requireRole('user');
            $book_id = $_POST['book_id'];
            $bookStmt = $db->prepare("SELECT title FROM books WHERE id = :id");
            $bookStmt->bindValue(':id', $book_id, SQLITE3_INTEGER);
            $book = $bookStmt->execute()->fetchArray(SQLITE3_ASSOC);
            
            $db->exec('BEGIN');
            $stmt1 = $db->prepare("UPDATE books SET status = 'Available' WHERE id = :id");
            $stmt1->bindValue(':id', $book_id, SQLITE3_INTEGER);
            $stmt1->execute();
            
            $stmt2 = $db->prepare("UPDATE transactions SET status = 'Returned', return_date = CURRENT_TIMESTAMP WHERE book_id = :bid AND user_id = :uid AND status = 'Borrowed'");
            $stmt2->bindValue(':bid', $book_id, SQLITE3_INTEGER);
            $stmt2->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $stmt2->execute();
            $db->exec('COMMIT');
            if ($book) {
                createNotification(
                    $db,
                    $user_id,
                    null,
                    'book_returned',
                    'Book returned',
                    "📚 Return confirmed for '{$book['title']}'.",
                    'user_return_' . $book_id . '_' . date('Y-m-d-H-i-s')
                );
                createRoleNotification(
                    $db,
                    'librarian',
                    'book_returned',
                    'Book returned',
                    "🔄 User #{$user_id} returned '{$book['title']}'.",
                    'lib_return_' . $book_id . '_' . date('Y-m-d-H-i-s')
                );
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'request_return':
            requireRole('user');
            $book_id = (int)($_POST['book_id'] ?? 0);
            $txStmt = $db->prepare("
                SELECT id
                FROM transactions
                WHERE book_id = :bid AND user_id = :uid AND status = 'Borrowed'
                ORDER BY id DESC LIMIT 1
            ");
            $txStmt->bindValue(':bid', $book_id, SQLITE3_INTEGER);
            $txStmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $tx = $txStmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (!$tx) {
                echo json_encode(['success' => false, 'message' => 'No active borrowed transaction found']);
                break;
            }
            $up = $db->prepare("UPDATE transactions SET status = 'Return Pending' WHERE id = :id");
            $up->bindValue(':id', $tx['id'], SQLITE3_INTEGER);
            $up->execute();
            createNotification(
                $db, $user_id, null, 'return_pending', 'Return pending confirmation',
                "⏳ Return request submitted. Librarian will confirm soon.",
                'user_return_pending_' . $tx['id']
            );
            createRoleNotification(
                $db, 'librarian', 'return_request', 'Book return request',
                "🔄 Return request for transaction #{$tx['id']} needs confirmation.",
                'lib_return_request_' . $tx['id']
            );
            echo json_encode(['success' => true]);
            break;

        case 'process_transaction':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $transactionId = (int)($_POST['transaction_id'] ?? 0);
            $decision = trim($_POST['decision'] ?? '');
            $txStmt = $db->prepare("
                SELECT t.id, t.user_id, t.book_id, t.status, b.title
                FROM transactions t
                JOIN books b ON b.id = t.book_id
                WHERE t.id = :id
            ");
            $txStmt->bindValue(':id', $transactionId, SQLITE3_INTEGER);
            $tx = $txStmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (!$tx) {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
                break;
            }
            if ($tx['status'] === 'Pending') {
                if ($decision === 'approve') {
                    $bookStmt = $db->prepare("SELECT status FROM books WHERE id = :id");
                    $bookStmt->bindValue(':id', $tx['book_id'], SQLITE3_INTEGER);
                    $bookStatus = $bookStmt->execute()->fetchArray(SQLITE3_ASSOC);
                    if (!$bookStatus || $bookStatus['status'] !== 'Available') {
                        echo json_encode(['success' => false, 'message' => 'Book is no longer available']);
                        break;
                    }
                    $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
                    $db->exec('BEGIN');
                    $upTx = $db->prepare("UPDATE transactions SET status = 'Borrowed', borrow_date = CURRENT_TIMESTAMP, due_date = :due WHERE id = :id");
                    $upTx->bindValue(':due', $due_date, SQLITE3_TEXT);
                    $upTx->bindValue(':id', $transactionId, SQLITE3_INTEGER);
                    $upTx->execute();
                    $upBook = $db->prepare("UPDATE books SET status = 'Borrowed' WHERE id = :id");
                    $upBook->bindValue(':id', $tx['book_id'], SQLITE3_INTEGER);
                    $upBook->execute();
                    $db->exec('COMMIT');
                    createNotification($db, (int)$tx['user_id'], null, 'book_approved', 'Book approved / issued successfully', "✅ '{$tx['title']}' approved and issued. Due: {$due_date}.", 'user_approved_' . $transactionId);
                } elseif ($decision === 'reject') {
                    $upTx = $db->prepare("UPDATE transactions SET status = 'Rejected' WHERE id = :id");
                    $upTx->bindValue(':id', $transactionId, SQLITE3_INTEGER);
                    $upTx->execute();
                    createNotification($db, (int)$tx['user_id'], null, 'borrow_rejected', 'Borrow request rejected', "❌ Borrow request for '{$tx['title']}' was rejected.", 'user_rejected_' . $transactionId);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid decision for pending request']);
                    break;
                }
            } elseif ($tx['status'] === 'Return Pending') {
                if ($decision === 'confirm_return') {
                    $db->exec('BEGIN');
                    $upTx = $db->prepare("UPDATE transactions SET status = 'Returned', return_date = CURRENT_TIMESTAMP WHERE id = :id");
                    $upTx->bindValue(':id', $transactionId, SQLITE3_INTEGER);
                    $upTx->execute();
                    $upBook = $db->prepare("UPDATE books SET status = 'Available' WHERE id = :id");
                    $upBook->bindValue(':id', $tx['book_id'], SQLITE3_INTEGER);
                    $upBook->execute();
                    $db->exec('COMMIT');
                    createNotification($db, (int)$tx['user_id'], null, 'return_confirmed', 'Book returned confirmation', "📚 Librarian confirmed return of '{$tx['title']}'.", 'user_return_confirmed_' . $transactionId);
                } elseif ($decision === 'reject_return') {
                    $upTx = $db->prepare("UPDATE transactions SET status = 'Borrowed' WHERE id = :id");
                    $upTx->bindValue(':id', $transactionId, SQLITE3_INTEGER);
                    $upTx->execute();
                    createNotification($db, (int)$tx['user_id'], null, 'return_rejected', 'Return rejected', "❌ Return request for '{$tx['title']}' was rejected. Please contact librarian.", 'user_return_rejected_' . $transactionId);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid decision for return request']);
                    break;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction is not awaiting librarian action']);
                break;
            }
            echo json_encode(['success' => true]);
            break;

        case 'get_notification_recipients':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $result = $db->query("SELECT id, username, role FROM users ORDER BY username ASC");
            $users = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $users[] = $row; }
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'send_user_notification':
            if ($role !== 'admin' && $role !== 'librarian') { requireRole('librarian'); }
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $title = trim($_POST['title'] ?? 'Notification');
            $message = trim($_POST['message'] ?? '');
            if ($targetUserId <= 0 || $message === '') {
                echo json_encode(['success' => false, 'message' => 'User and message are required']);
                break;
            }
            createNotification(
                $db, $targetUserId, null, 'direct_message', $title, $message,
                'direct_' . $targetUserId . '_' . md5($title . '_' . $message . '_' . date('Y-m-d-H-i-s'))
            );
            echo json_encode(['success' => true]);
            break;

        case 'get_my_transactions':
            requireRole('user');
            $stmt = $db->prepare("
                SELECT t.id, t.borrow_date, t.return_date, t.due_date, t.fine_amount, t.status, b.title as book_title, b.id as book_id
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
        case 'get_overdue':
            $result = $db->query("
                SELECT t.*, u.username, b.title
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                JOIN books b ON t.book_id = b.id
                WHERE t.status = 'Borrowed'
                AND date(t.due_date) < date('now')
            ");

            $data = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;   

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>