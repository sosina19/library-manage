<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

$dbFile = __DIR__ . '/library.db';
$pdo = null;

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password_hash_value TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('student', 'librarian', 'admin')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            author TEXT NOT NULL,
            isbn TEXT NOT NULL UNIQUE,
            total_quantity INTEGER NOT NULL CHECK(total_quantity >= 0),
            available_quantity INTEGER NOT NULL CHECK(available_quantity >= 0)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS borrow_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            book_id INTEGER NOT NULL,
            borrowed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            returned_at TEXT DEFAULT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(book_id) REFERENCES books(id) ON DELETE CASCADE
        )'
    );

    ensureDefaultAdmin($pdo);
} catch (Throwable $e) {
    respondError('Database initialization failed.');
}

if (!$pdo instanceof PDO) {
    respondError('Database initialization failed.');
}

$action = $_GET['action'] ?? '';
$input = readJsonInput();

try {
    switch ($action) {
        case 'signup':
            signupStudent($pdo, $input);
            break;
        case 'login':
            loginUser($pdo, $input);
            break;
        case 'logout':
            logoutUser();
            break;
        case 'me':
            currentUser();
            break;
        case 'list_books':
            listBooks($pdo, (string)($_GET['q'] ?? ''));
            break;
        case 'borrow':
            borrowBook($pdo, $input);
            break;
        case 'return':
            returnBook($pdo, $input);
            break;
        case 'add_book':
            requireRole(['librarian', 'admin']);
            addBook($pdo, $input);
            break;
        case 'update_book':
            requireRole(['librarian', 'admin']);
            updateBook($pdo, $input);
            break;
        case 'delete_book':
            requireRole(['librarian', 'admin']);
            deleteBook($pdo, $input);
            break;
        case 'list_records':
            requireRole(['librarian', 'admin']);
            listRecords($pdo);
            break;
        case 'create_user':
            requireRole(['admin']);
            createUserByAdmin($pdo, $input);
            break;
        case 'delete_user':
            requireRole(['admin']);
            deleteUserByAdmin($pdo, $input);
            break;
        case 'list_users':
            requireRole(['admin']);
            listUsers($pdo);
            break;
        default:
            respondError('Invalid action.');
    }
} catch (Throwable $e) {
    respondError($e->getMessage());
}

function readJsonInput(): array
{
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode($rawBody ?: '{}', true);
    return is_array($decoded) ? $decoded : [];
}

function ensureDefaultAdmin(PDO $pdo): void
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => 'admin']);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (name, username, password_hash_value, role)
         VALUES (:name, :username, :password_hash, :role)'
    );
    $insert->execute([
        ':name' => 'System Admin',
        ':username' => 'admin',
        ':password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        ':role' => 'admin'
    ]);
}

function signupStudent(PDO $pdo, array $input): void
{
    $name = trim((string)($input['name'] ?? ''));
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    if ($name === '' || $username === '' || strlen($password) < 4) {
        respondError('Name, username and password (min 4 chars) are required.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO users (name, username, password_hash_value, role)
         VALUES (:name, :username, :password_hash, 'student')"
    );
    $stmt->execute([
        ':name' => $name,
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT)
    ]);
    respondSuccess('Student signup successful.');
}

function loginUser(PDO $pdo, array $input): void
{
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    if ($username === '' || $password === '') {
        respondError('Username and password are required.');
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, username, password_hash_value, role
         FROM users WHERE username = :username LIMIT 1'
    );
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, (string)$user['password_hash_value'])) {
        respondError('Invalid username or password.');
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => (string)$user['name'],
        'username' => (string)$user['username'],
        'role' => (string)$user['role']
    ];

    respondSuccess('Login successful.', ['user' => $_SESSION['user']]);
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    respondSuccess('Logged out.');
}

function currentUser(): void
{
    if (!isset($_SESSION['user'])) {
        respondError('Not authenticated.');
    }
    respondSuccess('Authenticated.', ['user' => $_SESSION['user']]);
}

function requireRole(array $roles): void
{
    if (!isset($_SESSION['user'])) {
        respondError('You must login first.');
    }
    $role = (string)$_SESSION['user']['role'];
    if (!in_array($role, $roles, true)) {
        respondError('Permission denied.');
    }
}

function listBooks(PDO $pdo, string $query): void
{
    if (!isset($_SESSION['user'])) {
        respondError('You must login first.');
    }
    $query = trim($query);
    if ($query === '') {
        $stmt = $pdo->query('SELECT * FROM books ORDER BY id DESC');
    } else {
        $stmt = $pdo->prepare(
            'SELECT * FROM books
             WHERE title LIKE :q OR author LIKE :q OR isbn LIKE :q
             ORDER BY id DESC'
        );
        $stmt->execute([':q' => '%' . $query . '%']);
    }
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respondSuccess('Books loaded.', ['books' => $books]);
}

function borrowBook(PDO $pdo, array $input): void
{
    requireRole(['student']);
    $bookId = (int)($input['book_id'] ?? 0);
    if ($bookId < 1) {
        respondError('Valid book ID is required.');
    }

    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
    $stmt->execute([':id' => $bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$book) {
        respondError('Book not found.');
    }
    if ((int)$book['available_quantity'] < 1) {
        respondError('All copies are already borrowed.');
    }

    $pdo->beginTransaction();
    $decrement = $pdo->prepare('UPDATE books SET available_quantity = available_quantity - 1 WHERE id = :id');
    $decrement->execute([':id' => $bookId]);

    $record = $pdo->prepare(
        'INSERT INTO borrow_records (user_id, book_id)
         VALUES (:user_id, :book_id)'
    );
    $record->execute([
        ':user_id' => (int)$_SESSION['user']['id'],
        ':book_id' => $bookId
    ]);
    $pdo->commit();

    respondSuccess('Book borrowed.');
}

function returnBook(PDO $pdo, array $input): void
{
    requireRole(['student']);
    $bookId = (int)($input['book_id'] ?? 0);
    if ($bookId < 1) {
        respondError('Valid book ID is required.');
    }

    $recordStmt = $pdo->prepare(
        'SELECT id FROM borrow_records
         WHERE user_id = :user_id AND book_id = :book_id AND returned_at IS NULL
         ORDER BY id DESC LIMIT 1'
    );
    $recordStmt->execute([
        ':user_id' => (int)$_SESSION['user']['id'],
        ':book_id' => $bookId
    ]);
    $record = $recordStmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        respondError('No active borrowed record found for this book.');
    }

    $pdo->beginTransaction();
    $markReturned = $pdo->prepare('UPDATE borrow_records SET returned_at = CURRENT_TIMESTAMP WHERE id = :id');
    $markReturned->execute([':id' => (int)$record['id']]);

    $increment = $pdo->prepare('UPDATE books SET available_quantity = available_quantity + 1 WHERE id = :book_id');
    $increment->execute([':book_id' => $bookId]);
    $pdo->commit();

    respondSuccess('Book returned.');
}

function addBook(PDO $pdo, array $input): void
{
    $title = trim((string)($input['title'] ?? ''));
    $author = trim((string)($input['author'] ?? ''));
    $isbn = trim((string)($input['isbn'] ?? ''));
    $quantity = (int)($input['quantity'] ?? 0);
    if ($title === '' || $author === '' || $isbn === '' || $quantity < 1) {
        respondError('Title, author, ISBN and quantity (>= 1) are required.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO books (title, author, isbn, total_quantity, available_quantity)
         VALUES (:title, :author, :isbn, :total, :available)'
    );
    $stmt->execute([
        ':title' => $title,
        ':author' => $author,
        ':isbn' => $isbn,
        ':total' => $quantity,
        ':available' => $quantity
    ]);
    respondSuccess('Book added.');
}

function updateBook(PDO $pdo, array $input): void
{
    $id = (int)($input['id'] ?? 0);
    $title = trim((string)($input['title'] ?? ''));
    $author = trim((string)($input['author'] ?? ''));
    $isbn = trim((string)($input['isbn'] ?? ''));
    $quantity = (int)($input['quantity'] ?? 0);
    if ($id < 1 || $title === '' || $author === '' || $isbn === '' || $quantity < 1) {
        respondError('Book ID, title, author, ISBN and quantity are required.');
    }

    $stmt = $pdo->prepare('SELECT total_quantity, available_quantity FROM books WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$book) {
        respondError('Book not found.');
    }

    $borrowedCount = (int)$book['total_quantity'] - (int)$book['available_quantity'];
    if ($quantity < $borrowedCount) {
        respondError('Total quantity cannot be less than currently borrowed copies.');
    }

    $newAvailable = $quantity - $borrowedCount;
    $update = $pdo->prepare(
        'UPDATE books
         SET title = :title, author = :author, isbn = :isbn, total_quantity = :total, available_quantity = :available
         WHERE id = :id'
    );
    $update->execute([
        ':title' => $title,
        ':author' => $author,
        ':isbn' => $isbn,
        ':total' => $quantity,
        ':available' => $newAvailable,
        ':id' => $id
    ]);
    if ($update->rowCount() === 0) {
        respondError('No changes saved.');
    }
    respondSuccess('Book updated.');
}

function deleteBook(PDO $pdo, array $input): void
{
    $id = (int)($input['id'] ?? 0);
    if ($id < 1) {
        respondError('Valid book ID is required.');
    }
    $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount() === 0) {
        respondError('Book not found.');
    }
    respondSuccess('Book deleted.');
}

function listRecords(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT br.id, b.title AS book_title, u.username, br.borrowed_at, br.returned_at
         FROM borrow_records br
         INNER JOIN books b ON b.id = br.book_id
         INNER JOIN users u ON u.id = br.user_id
         ORDER BY br.id DESC'
    );
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respondSuccess('Records loaded.', ['records' => $records]);
}

function createUserByAdmin(PDO $pdo, array $input): void
{
    $name = trim((string)($input['name'] ?? ''));
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $role = trim((string)($input['role'] ?? 'student'));
    if ($name === '' || $username === '' || strlen($password) < 4) {
        respondError('Name, username and password (min 4 chars) are required.');
    }
    if (!in_array($role, ['student', 'librarian', 'admin'], true)) {
        respondError('Invalid role.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, username, password_hash_value, role),
         VALUES (:name, :username, :password_hash, :role)'
    );
    $stmt->execute([
        ':name' => $name,
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role
    ]);
    respondSuccess('User created.');
}

function deleteUserByAdmin(PDO $pdo, array $input): void
{
    $id = (int)($input['id'] ?? 0);
    if ($id < 1) {
        respondError('Valid user ID is required.');
    }

    if ((int)$_SESSION['user']['id'] === $id) {
        respondError('Admin cannot remove own account.');
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount() === 0) {
        respondError('User not found.');
    }
    respondSuccess('User removed.');
}

function listUsers(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT id, name, username, role, created_at FROM users ORDER BY id DESC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respondSuccess('Users loaded.', ['users' => $users]);
}

function respondSuccess(string $message, array $data = []): void
{
    echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
    exit;
}

function respondError(string $message): void
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
