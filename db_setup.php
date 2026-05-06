<?php
$dbFile = __DIR__ . '/library.db';
$db = new SQLite3($dbFile);

// Create Users table
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL,
    email TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Create Books table
$db->exec("CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    isbn TEXT UNIQUE,
    published_year INTEGER,
    status TEXT DEFAULT 'Available'
)");

// Create Transactions table
$db->exec("CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    book_id INTEGER,
    user_id INTEGER,
    borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    return_date DATETIME,
    due_date DATETIME,
    fine_amount REAL DEFAULT 0,
    status TEXT DEFAULT 'Borrowed',
    FOREIGN KEY(book_id) REFERENCES books(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// Notifications table
$db->exec("CREATE TABLE IF NOT EXISTS notifications (
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

// Insert default admin
$adminUser = 'admin';
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT OR IGNORE INTO users (username, password_hash, role, email) VALUES (:user, :pass, 'admin', 'admin@example.com')");
$stmt->bindValue(':user', $adminUser, SQLITE3_TEXT);
$stmt->bindValue(':pass', $adminPass, SQLITE3_TEXT);
$stmt->execute();

echo "Database setup complete. Default admin created (username: admin, password: admin123)\n";
?>
