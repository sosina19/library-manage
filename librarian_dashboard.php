<?php
require_once 'auth.php';
checkLoggedIn();
requireRole('librarian');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Librarian Panel</h2>
            <nav>
                <a class="active" data-tab="books-tab" onclick="switchTab('books-tab'); loadBooks();">Manage Books</a>
                <a data-tab="transactions-tab" onclick="switchTab('transactions-tab'); loadTransactions();">Transactions</a>
            </nav>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            </div>

            <!-- Books Tab -->
            <div id="books-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Books Catalog</h3>
                        <button class="btn btn-primary btn-sm" onclick="openBookModal()">+ Add Book</button>
                    </div>
                    <div class="table-responsive">
                        <table id="booksTable">
                            <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Transactions Tab -->
            <div id="transactions-tab" class="tab-content hidden">
                <div class="card">
                    <div class="card-header">
                        <h3>Borrow & Return Records</h3>
                    </div>
                    <div class="table-responsive">
                        <table id="transactionsTable">
                            <thead><tr><th>ID</th><th>Book</th><th>User</th><th>Borrow Date</th><th>Return Date</th><th>Status</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Book Modal -->
    <div id="bookModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('bookModal')">&times;</button>
            <h3 class="modal-title">Manage Book</h3>
            <form id="bookForm">
                <input type="hidden" id="b_id">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" id="b_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" id="b_author" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" id="b_isbn" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Published Year</label>
                    <input type="number" id="b_year" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Save Book</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        async function loadBooks() {
            const res = await apiCall('get_books');
            if(res.success) {
                const tbody = document.querySelector('#booksTable tbody');
                tbody.innerHTML = '';
                res.data.forEach(b => {
                    tbody.innerHTML += `<tr>
                        <td>${b.id}</td>
                        <td>${b.title}</td>
                        <td>${b.author}</td>
                        <td>${b.isbn}</td>
                        <td><span class="badge ${b.status === 'Available' ? 'available' : 'borrowed'}">${b.status}</span></td>
                        <td>
                            <button class="action-btn" onclick='editBook(${JSON.stringify(b).replace(/'/g, "&#39;")})'>✏️</button>
                            <button class="action-btn" onclick="deleteBook(${b.id})">❌</button>
                        </td>
                    </tr>`;
                });
            }
        }
        
        async function loadTransactions() {
            const res = await apiCall('get_transactions');
            if(res.success) {
                const tbody = document.querySelector('#transactionsTable tbody');
                tbody.innerHTML = '';
                res.data.forEach(t => {
                    tbody.innerHTML += `<tr>
                        <td>${t.id}</td>
                        <td>${t.book_title}</td>
                        <td>${t.username}</td>
                        <td>${t.borrow_date}</td>
                        <td>${t.return_date || '-'}</td>
                        <td><span class="badge ${t.status === 'Returned' ? 'returned' : 'borrowed'}">${t.status}</span></td>
                    </tr>`;
                });
            }
        }

        function openBookModal() {
            document.getElementById('bookForm').reset();
            document.getElementById('b_id').value = '';
            openModal('bookModal');
        }

        function editBook(book) {
            document.getElementById('b_id').value = book.id;
            document.getElementById('b_title').value = book.title;
            document.getElementById('b_author').value = book.author;
            document.getElementById('b_isbn').value = book.isbn;
            document.getElementById('b_year').value = book.published_year;
            openModal('bookModal');
        }

        document.getElementById('bookForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('b_id').value;
            const data = {
                title: document.getElementById('b_title').value,
                author: document.getElementById('b_author').value,
                isbn: document.getElementById('b_isbn').value,
                published_year: document.getElementById('b_year').value
            };
            
            let res;
            if(!id) {
                res = await apiCall('add_book', data);
            } else {
                data.id = id;
                res = await apiCall('update_book', data);
            }
            
            if(res.success) {
                closeModal('bookModal');
                loadBooks();
            } else {
                alert(res.message);
            }
        });

        async function deleteBook(id) {
            if(confirm('Are you sure you want to delete this book?')) {
                const res = await apiCall('delete_book', {id});
                if(res.success) loadBooks();
                else alert(res.message);
            }
        }

        // Initialize
        loadBooks();
    </script>
</body>
</html>
