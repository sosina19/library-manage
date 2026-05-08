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
                <a data-tab="catalog-tab" onclick="switchTab('catalog-tab'); loadCatalogBooks();">Book Catalog</a>
                <a data-tab="transactions-tab" onclick="switchTab('transactions-tab'); loadTransactions();">Transactions</a>
                <a id="notificationsNavLink" data-tab="notifications-tab" onclick="switchTab('notifications-tab'); loadNotifications();">Notifications
                 <span id="notificationsDot" class="notif-dot hidden"></span></a>
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
                <h1><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></h1>
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
                <div id="notificationHeaderActions" style="display:none; gap:10px; align-items:center;">
                    <button class="btn btn-secondary btn-sm" onclick="markAllNotificationsRead()">Mark all read</button>
                    <button class="btn btn-danger btn-sm" onclick="clearNotifications()">Clear all</button>
                </div>
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

            <!-- Catalog Tab -->
            <div id="catalog-tab" class="tab-content hidden">
                <div class="card">
                    <div class="card-header">
                        <h3>Book Catalog</h3>
                    </div>
                    <div class="controls-bar">
                        <div style="display:flex; gap:10px; margin:15px 0;">
                            <input type="text" id="catalogSearch" class="form-control" placeholder="🔍 Search catalog...">
                            <button class="btn btn-primary btn-sm" onclick="openBookModal()">+ Add Book</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="catalogBooksTable">
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
                            <thead><tr><th>ID</th><th>Book</th><th>User</th><th>Borrow Date</th><th>Return Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="notifications-tab" class="tab-content hidden">
                <div class="card">
                    <div class="card-header">
                        <h3>Librarian Notifications</h3>
                    </div>
                    <div class="controls-bar">
                        <div style="display:flex; gap:10px; margin:15px 0;">
                            <div style="position:relative; flex:1; min-width:260px;">
                                <input type="text" id="targetUserSearch" 
                                class="form-control" list="userRecipientsList"
                                 placeholder="🔎 Search user by username..." autocomplete="off" oninput="showRecipientSuggestions(this.value)" onfocus="showRecipientSuggestions(this.value)">
                                <div id="recipientSuggestions" 
                                class="hidden" style="position:absolute; 
                                top:44px; left:0; right:0; max-height:220px; overflow:auto; 
                                background:#fff; border:1px solid #e2e8f0; border-radius:10px; z-index:50; box-shadow:0 8px 20px rgba(0,0,0,0.12);"></div>
                            </div>
                            <datalist id="userRecipientsList"></datalist>
                            <input type="text" id="announcementInput" class="form-control" placeholder="📢 Send library announcement to students...">
                            <button class="btn btn-primary btn-sm" onclick="sendDirectNotification()">Send to User</button>
                            <button class="btn btn-secondary btn-sm" onclick="sendAnnouncement()">Broadcast to all</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="librarianNotificationsTable">
                            <thead><tr><th>Type</th><th>Message</th><th>Time</th><th>Action</th></tr></thead>
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
                        <td class="actions-cell">
                         <button class="action-btn edit" title="Edit book" aria-label="Edit book"
                            onclick='editBook(${JSON.stringify(b).replace(/'/g, "&#39;")})'>
                            ✏️
                        </button>
                        <button class="action-btn delete" title="Delete book" aria-label="Delete book"
                            onclick="deleteBook(${b.id})">
                            🗑️
                        </button>
                        </td>
                    </tr>`;
                });
            }
        }
        function showManageBooksView() {
            switchTab('books-tab');
            // Keep overview cards visible with Manage Books.
            const dashboardTab = document.getElementById('dashboard-tab');
            if (dashboardTab) {
                dashboardTab.classList.remove('hidden');
            }
            loadDashboardStats();
            loadBooks();
        }
        // Search functionality
        document.getElementById("bookSearch").addEventListener("input", function () {
        filterTable("booksTable", this.value);
         });

    document.getElementById("transactionSearch").addEventListener("input", function () {
        filterTable("transactionsTable", this.value);
       });
        document.getElementById("catalogSearch").addEventListener("input", function () {
        filterTable("catalogBooksTable", this.value);
         });
        function filterTable(tableId, query) {
        query = query.toLowerCase();

        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? "" : "none";
        });
    } 
    // Dashboard stats
        async function loadDashboardStats() {
            const res = await apiCall('librarian_dashboard_stats');
            if (res.success) {
                document.getElementById('totalBooks').innerText = res.data.total_books;
                document.getElementById('availableBooks').innerText = res.data.available_books;
                document.getElementById('borrowedBooks').innerText = res.data.borrowed_books;
                document.getElementById('totalUsers').innerText = res.data.total_users;
                document.getElementById('overdueBooks').innerText = res.data.overdue_books;
                document.getElementById('dueSoonBooks').innerText = res.data.due_soon_books;
            }
        }
     // Load books for Catalog tab (similar to Manage Books but can be extended with more details or filters)
        async function loadCatalogBooks() {
            const res = await apiCall('get_books');
            if (res.success) {
                const tbody = document.querySelector('#catalogBooksTable tbody');
                tbody.innerHTML = '';
                res.data.forEach(b => {
                    tbody.innerHTML += `<tr>
                        <td>${b.id}</td>
                        <td>${b.title}</td>
                        <td>${b.author}</td>
                        <td>${b.isbn}</td>
                        <td><span class="badge ${b.status === 'Available' ? 'available' : 'borrowed'}">${b.status}</span></td>
                        <td class="actions-cell">
                            <button class="action-btn edit" title="Edit book" aria-label="Edit book"
                                onclick='editBook(${JSON.stringify(b).replace(/'/g, "&#39;")})'>
                                ✏️
                            </button>
                            <button class="action-btn delete" title="Delete book" aria-label="Delete book"
                                onclick="deleteBook(${b.id})">
                                🗑️
                            </button>
                        </td>
                    </tr>`;
                });
            }
        }
    // Load transactions for Transactions tab
        async function loadTransactions() {
            const res = await apiCall('get_transactions');
            if(res.success) {
                const tbody = document.querySelector('#transactionsTable tbody');
                tbody.innerHTML = '';
                res.data.forEach(t => {
                    let actionHtml = '-';
                    if (t.status === 'Pending') {
                        actionHtml = `
                            <button class="btn btn-primary btn-sm" onclick="processTransaction(${t.id}, 'approve')">Approve</button>
                            <button class="btn btn-danger btn-sm" onclick="processTransaction(${t.id}, 'reject')">Reject</button>
                        `;
                    } else if (t.status === 'Return Pending') {
                        actionHtml = `
                            <button class="btn btn-primary btn-sm" onclick="processTransaction(${t.id}, 'confirm_return')">Confirm Return</button>
                            <button class="btn btn-danger btn-sm" onclick="processTransaction(${t.id}, 'reject_return')">Reject Return</button>
                        `;
                    }
                    tbody.innerHTML += `<tr>
                        <td>${t.id}</td>
                        <td>${t.book_title}</td>
                        <td>${t.username}</td>
                        <td>${t.borrow_date}</td>
                        <td>${t.return_date || '-'}</td>
                        <td><span class="badge ${t.status === 'Returned' ? 'returned' : 'borrowed'}">${t.status}</span></td>
                        <td>${actionHtml}</td>
                    </tr>`;
                });
            }
        }
        async function processTransaction(transactionId, decision) {
            const res = await apiCall('process_transaction', { transaction_id: transactionId, decision });
            if (res.success) {
                loadTransactions();
                loadBooks();
                loadCatalogBooks();
                loadDashboardStats();
                loadNotifications();
            } else {
                alert(res.message || 'Failed to process transaction');
            }
        }
        let recipientMap = {};
        let recipientItems = [];
        async function loadNotifications() {
            const res = await apiCall('get_notifications');
            if (res.success) {
                const tbody = document.querySelector('#librarianNotificationsTable tbody');
                tbody.innerHTML = '';
                let unreadCount = 0;
                res.data.forEach(n => {
                    if (!Number(n.is_read)) unreadCount += 1;
                    tbody.innerHTML += `<tr>
                        <td><span class="badge">${n.title}</span></td>
                        <td>${n.message}</td>
                        <td>${n.created_at}</td>
                        <td>${Number(n.is_read) ? '<span class="badge returned">Read</span>' : `<button class="btn btn-secondary btn-sm" onclick="markNotificationRead(${n.id})">Mark read</button>`}</td>
                    </tr>`;
                });
                updateNotificationDot(unreadCount);
            }
        }
        function updateNotificationDot(unreadCount) {
            const dot = document.getElementById('notificationsDot');
            if (!dot) return;
            if (unreadCount > 0) {
                dot.classList.remove('hidden');
            } else {
                dot.classList.add('hidden');
            }
        }
        async function markNotificationRead(id) {
            const res = await apiCall('mark_notification_read', { id });
            if (res.success) loadNotifications();
        }
        async function markAllNotificationsRead() {
            const res = await apiCall('mark_all_notifications_read');
            if (res.success) loadNotifications();
        }
        async function clearNotifications() {
            if (!await showConfirm('Clear all notifications?', 'Clear Notifications')) return;
            const res = await apiCall('clear_notifications');
            if (res.success) loadNotifications();
        }
        async function loadRecipients() {
            const res = await apiCall('get_notification_recipients');
            const list = document.getElementById('userRecipientsList');
            if (!list) return;
            if (res.success) {
                list.innerHTML = '';
                recipientMap = {};
                recipientItems = [];
                res.data
                    .filter(u => u.role === 'user')
                    .forEach(u => {
                        const label = `${u.username} (#${u.id})`;
                        recipientMap[label] = u.id;
                        recipientItems.push({ id: u.id, label, username: u.username });
                        list.innerHTML += `<option value="${label}"></option>`;
                    });
            }
        }
        // Show recipient suggestions based on input query
        function showRecipientSuggestions(query) {
            const box = document.getElementById('recipientSuggestions');
            if (!box) return;
            const q = (query || '').trim().toLowerCase();
            if (!q) {
                box.classList.add('hidden');
                box.innerHTML = '';
                return;
            }
            const matches = recipientItems
                .filter(u => u.username.toLowerCase().includes(q) || u.label.toLowerCase().includes(q))
                .slice(0, 8);
            if (matches.length === 0) {
                box.classList.add('hidden');
                box.innerHTML = '';
                return;
            }
            box.innerHTML = matches.map(u => `
                <button type="button" style="display:block; width:100%; text-align:left; padding:10px 12px; border:none; background:white; cursor:pointer;"
                    onclick="selectRecipient('${u.label.replace(/'/g, "\\'")}')">${u.label}</button>
            `).join('');
            box.classList.remove('hidden');
        }
        // When a recipient is selected from suggestions, fill the input and hide suggestions
        function selectRecipient(label) {
            document.getElementById('targetUserSearch').value = label;
            const box = document.getElementById('recipientSuggestions');
            box.classList.add('hidden');
            box.innerHTML = '';
        }
        // Send direct notification to selected user
        async function sendDirectNotification() {
            const selected = document.getElementById('targetUserSearch').value.trim();
            const userId = recipientMap[selected];
            const input = document.getElementById('announcementInput');
            const message = input.value.trim();
            if (!userId || !message) {
                alert('Search and select a user, then write message.');
                return;
            }
            const res = await apiCall('send_user_notification', {
                user_id: userId,
                title: 'Message from librarian',
                message
            });
            if (res.success) {
                input.value = '';
                document.getElementById('targetUserSearch').value = '';
                const box = document.getElementById('recipientSuggestions');
                box.classList.add('hidden');
                box.innerHTML = '';
                loadNotifications();
                alert('Notification sent to selected user.');
            } else {
                alert(res.message || 'Failed to send notification');
            }
        }
        document.addEventListener('click', function (e) {
            const searchWrap = document.getElementById('targetUserSearch');
            const box = document.getElementById('recipientSuggestions');
            if (!searchWrap || !box) return;
            if (e.target !== searchWrap && !box.contains(e.target)) {
                box.classList.add('hidden');
            }
        });
        // Show/hide header actions based on active tab
        function updateHeaderNotificationActions() {
            const actions = document.getElementById('notificationHeaderActions');
            const notificationsTab = document.getElementById('notifications-tab');
            if (!actions || !notificationsTab) return;
            actions.style.display = notificationsTab.classList.contains('hidden') ? 'none' : 'flex';
        }
        document.querySelectorAll('.sidebar nav a').forEach(link => {
            link.addEventListener('click', () => {
                setTimeout(updateHeaderNotificationActions, 0);
            });
        });
        async function sendAnnouncement() {
            const input = document.getElementById('announcementInput');
            const message = input.value.trim();
            if (!message) return;
            const res = await apiCall('add_announcement', { title: 'Library announcement', message });
            if (res.success) {
                input.value = '';
                loadNotifications();
                alert('Announcement sent to students.');
            } else {
                alert(res.message || 'Failed to send announcement');
            }
        }
    // Book management functions
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
                loadCatalogBooks();
                loadDashboardStats();
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
        loadCatalogBooks();
        loadDashboardStats();
    } else {
        alert(res.message);
    }

    selectedBookId = null;
}

        // Initialize
        showManageBooksView();
        loadCatalogBooks();
        loadDashboardStats();
        loadNotifications();
        loadRecipients();
        updateHeaderNotificationActions();
        setInterval(loadNotifications, 30000);

        // Ensure sidebar "Manage Books" always restores the full view.
        const manageBooksLink = document.querySelector('[data-tab="books-tab"]');
        if (manageBooksLink) {
            manageBooksLink.onclick = function () {
                showManageBooksView();
            };
        }
    </script>
</body>
</html>
