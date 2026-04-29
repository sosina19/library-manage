<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="container">
        <h1>Library Management System</h1>
        <div id="message" aria-live="polite"></div>

        <section id="auth-section" class="card">
            <h2>Login</h2>
            <form id="login-form" class="inline-form">
                <input type="text" id="login-username" placeholder="Username" required>
                <input type="password" id="login-password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <h3>Student Signup</h3>
            <form id="signup-form" class="inline-form">
                <input type="text" id="signup-name" placeholder="Full Name" required>
                <input type="text" id="signup-username" placeholder="Username" required>
                <input type="password" id="signup-password" placeholder="Password" required>
                <button type="submit">Signup</button>
            </form>
            <p class="hint">Librarian accounts can only be created by admin.</p>
        </section>

        <section id="app-section" class="hidden">
            <section class="card">
                <div class="header-row">
                    <h2>Dashboard</h2>
                    <div>
                        <span id="current-user"></span>
                        <button id="logout-btn" type="button">Logout</button>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>Search Books</h2>
                <form id="search-form" class="inline-form">
                    <input type="text" id="search-input" placeholder="Search by title, author, ISBN">
                    <button type="submit">Search</button>
                    <button id="clear-search" type="button">Clear</button>
                </form>
            </section>

            <section id="student-actions" class="card hidden">
                <h2>Student Actions</h2>
                <form id="borrow-form" class="inline-form">
                    <input type="number" id="borrow-book-id" placeholder="Book ID" min="1" required>
                    <button type="submit">Borrow Book</button>
                </form>
                <form id="return-form" class="inline-form">
                    <input type="number" id="return-book-id" placeholder="Book ID" min="1" required>
                    <button type="submit">Return Book</button>
                </form>
            </section>

            <section id="librarian-actions" class="card hidden">
                <h2>Librarian Actions</h2>
                <form id="book-form" class="grid-form">
                    <label>Book ID (fill to update/delete)
                        <input type="number" id="book-id" min="1">
                    </label>
                    <label>Title
                        <input type="text" id="title">
                    </label>
                    <label>Author
                        <input type="text" id="author">
                    </label>
                    <label>ISBN
                        <input type="text" id="isbn">
                    </label>
                    <label>Total Quantity
                        <input type="number" id="quantity" min="1" value="1">
                    </label>
                    <div class="button-row">
                        <button type="button" id="add-book-btn">Add Book</button>
                        <button type="button" id="update-book-btn">Update Book</button>
                        <button type="button" id="delete-book-btn" class="danger">Delete Book</button>
                    </div>
                </form>
            </section>

            <section id="admin-actions" class="card hidden">
                <h2>Admin User Management</h2>
                <form id="create-user-form" class="inline-form">
                    <input type="text" id="new-user-name" placeholder="Full Name" required>
                    <input type="text" id="new-user-username" placeholder="Username" required>
                    <input type="password" id="new-user-password" placeholder="Password" required>
                    <select id="new-user-role" required>
                        <option value="student">student</option>
                        <option value="librarian">librarian</option>
                        <option value="admin">admin</option>
                    </select>
                    <button type="submit">Add User</button>
                </form>
                <form id="remove-user-form" class="inline-form">
                    <input type="number" id="remove-user-id" placeholder="User ID" min="1" required>
                    <button type="submit" class="danger">Remove User</button>
                </form>
            </section>

            <section class="card">
                <h2>Books Information</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Total</th>
                            <th>Available</th>
                        </tr>
                        </thead>
                        <tbody id="books-body"></tbody>
                    </table>
                </div>
            </section>

            <section id="records-section" class="card hidden">
                <h2>Borrowed / Returned Records</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                        <tr>
                            <th>Record ID</th>
                            <th>Book</th>
                            <th>User</th>
                            <th>Borrowed At</th>
                            <th>Returned At</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody id="records-body"></tbody>
                    </table>
                </div>
            </section>

            <section id="users-section" class="card hidden">
                <h2>All Users</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                        </thead>
                        <tbody id="users-body"></tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>

    <script src="script.js"></script>
</body>
</html>
