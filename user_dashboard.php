<?php
require_once 'auth.php';
checkLoggedIn();
requireRole('user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>User Library</h2>
            <nav>
                <a class="active" data-tab="browse-tab" onclick="switchTab('browse-tab'); loadAvailableBooks();">Browse Books</a>
                <a data-tab="my-books-tab" onclick="switchTab('my-books-tab'); loadMyTransactions();">My Books</a>
            </nav>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            </div>

            <!-- Browse Books Tab -->
            <div id="browse-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Available Books</h3>
                        <input type="text" id="searchInput" class="form-control" style="width: 250px;" placeholder="Search title or author..." onkeyup="filterBooks()">
                    </div>
                    <div class="table-responsive">
                        <table id="browseTable">
                            <thead><tr><th>Title</th><th>Author</th><th>ISBN</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- My Books Tab -->
            <div id="my-books-tab" class="tab-content hidden">
                <div class="card">
                    <div class="card-header">
                        <h3>My Borrowed Books</h3>
                    </div>
                    <div class="table-responsive">
                        <table id="myBooksTable">
                            <thead><tr><th>Book Title</th><th>Borrow Date</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
    <script>
        let allBooks = [];

        async function loadAvailableBooks() {
            const res = await apiCall('get_books');
            if(res.success) {
                allBooks = res.data.filter(b => b.status === 'Available');
                renderAvailableBooks(allBooks);
            }
        }

        function renderAvailableBooks(books) {
            const tbody = document.querySelector('#browseTable tbody');
            tbody.innerHTML = '';
            books.forEach(b => {
                tbody.innerHTML += `<tr>
                    <td>${b.title}</td>
                    <td>${b.author}</td>
                    <td>${b.isbn}</td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="borrowBook(${b.id})">Borrow</button>
                    </td>
                </tr>`;
            });
        }

        function filterBooks() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allBooks.filter(b => 
                b.title.toLowerCase().includes(query) || 
                b.author.toLowerCase().includes(query)
            );
            renderAvailableBooks(filtered);
        }

        async function borrowBook(book_id) {
            if(confirm('Do you want to borrow this book?')) {
                const res = await apiCall('borrow_book', {book_id});
                if(res.success) {
                    alert('Book borrowed successfully!');
                    loadAvailableBooks();
                } else {
                    alert(res.message);
                }
            }
        }

        async function loadMyTransactions() {
            const res = await apiCall('get_my_transactions');
            if(res.success) {
                const tbody = document.querySelector('#myBooksTable tbody');
                tbody.innerHTML = '';
                res.data.forEach(t => {
                    let actionHtml = t.status === 'Borrowed' 
                        ? `<button class="btn btn-secondary btn-sm" onclick="returnBook(${t.book_id})">Return</button>`
                        : `<span class="badge returned">Returned on ${t.return_date}</span>`;
                    
                    tbody.innerHTML += `<tr>
                        <td>${t.book_title}</td>
                        <td>${t.borrow_date}</td>
                        <td><span class="badge ${t.status === 'Returned' ? 'returned' : 'borrowed'}">${t.status}</span></td>
                        <td>${actionHtml}</td>
                    </tr>`;
                });
            }
        }

        async function returnBook(book_id) {
            if(confirm('Are you sure you want to return this book?')) {
                const res = await apiCall('return_book', {book_id});
                if(res.success) {
                    alert('Book returned successfully!');
                    loadMyTransactions();
                } else {
                    alert(res.message);
                }
            }
        }

        // Initialize
        loadAvailableBooks();
    </script>
</body>
</html>
