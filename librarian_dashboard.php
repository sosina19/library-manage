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
             <div class="profile-wrapper">
        <div class="profile-circle" onclick="toggleProfileCard()">
            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
        </div>

    <div id="profileCard" class="profile-card hidden">
        <div class="profile-header">
            <div class="avatar-big">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>

            <div>
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <small><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
            </div>
        </div>

        <div class="profile-actions">
            <button onclick="toggleTheme()" class="theme-btn">🌓 Theme</button>
            <button onclick="openLogoutModal()" class="logout-btn"> Logout</button>
        </div>
    </div>
</div>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>👋Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            </div>
             <div id="dashboard-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Library Overview</h3>
                    </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <h4>📚 Total Books</h4>
                    <p id="totalBooks">0</p>
                </div>

                <div class="stat-box">
                    <h4> 📚 Available Books</h4>
                    <p id="availableBooks">0</p>
                </div>

                <div class="stat-box">
                    <h4>🔄Borrowed Books</h4>
                    <p id="borrowedBooks">0</p>
                </div>

                <div class="stat-box">
                    <h4>👩‍🎓 Total Users</h4>
                    <p id="totalUsers">0</p>
                </div>

               <div class="stat-box overdue-box">
                    <h4>⏰ Overdue Books</h4>
                    <p id="overdueBooks">0</p>
                </div>

                <div class="stat-box warning-box">
                    <h4>⚠️ Due Soon</h4>
                    <p id="dueSoonBooks">0</p>
                </div>
            </div>
                </div>
            </div>

            <!-- Books Tab -->
            <div id="books-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Books Catalog</h3>
                    </div>
                     <div class="controls-bar">
                    <div style="display:flex; gap:10px; margin:15px 0;">
                    <input type="text" id="bookSearch" class="form-control" placeholder="🔍 Search...">
                        <button class="btn btn-primary btn-sm" onclick="openBookModal()">+ Add Book</button>
                    </div>
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
                      <div class="controls-bar">
                    <div style="display:flex; gap:10px; margin:15px 0;">
                    <input type="text" id="transactionSearch" class="form-control" placeholder="🔍 Search...">
                    </div>  
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
    <div id="bookModal" class="modal ">
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
      <div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width:400px; text-align:center;">
        
        <h3 style="margin-bottom:10px;color:black;">⚠️ Confirm Delete</h3>
        <p style="margin-bottom:20px; color:black;">Are you sure you want to delete this user?</p>

        <input type="hidden" id="deleteUserId">

        <div style="display:flex; justify-content:center; gap:10px;">
            <button class="btn btn-danger" onclick="confirmDelete()">Delete</button>
            <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
        </div>

    </div>
</div>
     <div id="logoutModal" class="modal">
    <div class="modal-content" style="max-width:400px; text-align:center;">

        <h3 style="margin-bottom:10px;color:black;"> 🔓Confirm Logout</h3>
        <p style="margin-bottom:20px;color:black;">Are you sure you want to log out?</p>

        <div style="display:flex; justify-content:center; gap:10px;">
            <button class="btn btn-danger" onclick="confirmLogout()">Logout</button>
            <button class="btn btn-secondary" onclick="closeModal('logoutModal')">Cancel</button>
        </div>

    </div>
</div>
<div id="infoModal" class="modal">
    <div class="modal-content">
        <h3>Book Info</h3>
        <p id="infoText"></p>
        <button onclick="closeModal('infoModal')">Close</button>
    </div>
</div>


    <script src="script.js"></script>
    <script>
         function toggleProfileMenu() {
    document.getElementById('profileDropdown').classList.toggle('hidden');
        }

        // close when clicking outside
        document.addEventListener('click', function (e) {
            const menu = document.querySelector('.profile-menu');
            if (!menu.contains(e.target)) {
                document.getElementById('profileDropdown').classList.add('hidden');
            }
        });

        function toggleTheme() {
            const body = document.body;
            body.classList.toggle("dark-mode");

            // save preference
            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("theme", "dark");
            } else {
                localStorage.setItem("theme", "light");
            }
        }

        // load saved theme on page start
        window.addEventListener("load", () => {
            const theme = localStorage.getItem("theme");
            if (theme === "dark") {
                document.body.classList.add("dark-mode");
            }
        });
        function toggleProfileCard() {
    document.getElementById('profileCard').classList.toggle('hidden');
        }
       function openLogoutModal() {
    openModal('logoutModal');
  }

        async function confirmLogout() {
            const res = await apiCall('logout');

            if (res.success) {
                window.location.href = 'index.php'; // or login page
            } else {
                alert("Logout failed");
            }
        }
        // close when clicking outside
        document.addEventListener('click', function (e) {
            const wrapper = document.querySelector('.profile-wrapper');
            if (!wrapper.contains(e.target)) {
                document.getElementById('profileCard').classList.add('hidden');
            }
        });

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
                         <button class="action-btn edit"
                            onclick='editBook(${JSON.stringify(b).replace(/'/g, "&#39;")})'>
                            ✏️
                        </button>
                        <button class="action-btn delete"
                            onclick="deleteBook(${b.id})">
                            🗑️
                        </button>
                        </td>
                    </tr>`;
                });
            }
        }
        // Search functionality
        document.getElementById("bookSearch").addEventListener("input", function () {
        filterTable("booksTable", this.value);
         });

    document.getElementById("transactionSearch").addEventListener("input", function () {
        filterTable("transactionsTable", this.value);
       });
        function filterTable(tableId, query) {
        query = query.toLowerCase();

        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? "" : "none";
        });
    } 
    
    // 
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
// Store selected book ID for deletion
       let selectedBookId = null;

function deleteBook(id) {
    selectedBookId = id;
    openModal('deleteModal');
} 
async function confirmDelete() {

    if (!selectedBookId) return;

    const res = await apiCall('delete_book', { id: selectedBookId });

    if (res.success) {
        closeModal('deleteModal');
        loadBooks();
    } else {
        alert(res.message);
    }

    selectedBookId = null;
}

        // Initialize
        loadBooks();
    </script>
</body>
</html>
