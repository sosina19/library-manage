<?php
session_start();

function getDB() {
    $dbFile = __DIR__ . '/library.db';
    return new SQLite3($dbFile);
}

function login($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :user");
    $stmt->bindValue(':user', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            return true;
        }
    }
    return false;
}

function logout() {
    session_destroy();
}

function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("HTTP/1.1 403 Forbidden");
        exit(json_encode(['error' => 'Unauthorized access.']));
    }
}

function checkLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}
?>
