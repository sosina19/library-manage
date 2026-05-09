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
                <a id="notificationsNavLink" data-tab="notifications-tab" onclick="switchTab('notifications-tab'); loadNotifications();">Notifications <span id="notificationsDot" class="notif-dot hidden"></span></a>
            </nav>
            <!-- <div class="notification-panel">
                <h4>Notifications</h4>
                <ul id="notificationList"></ul>
            </div> -->
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

            <!-- Browse Books Tab -->
            <div id="browse-tab" class="tab-content">
                <div class="card">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <h3>Available Books</h3>
                        <input type="text" id="searchInput" class="form-control" style="flex:1; min-width:200px; max-width:300px;" placeholder="Search title or author..." onkeyup="filterBooks()">
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
                            <thead><tr><th>Book Title</th><th>Borrow Date</th><th>Return Date</th><th>Due Date</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="notifications-tab" class="tab-content hidden">
                <div class="card">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <h3>My Notifications</h3>
                        <div class="notification-menu-wrapper">
                            <button class="hamburger-btn" onclick="toggleNotifMenu(event)">
                                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                            </button>
                            <div id="notifActionsMenu" class="notif-dropdown-menu hidden">
                                <a onclick="markAllNotificationsRead(); toggleNotifMenu(event);" class="notif-dropdown-item">✔️ Mark All as Read</a>
                                <a onclick="clearNotifications(); toggleNotifMenu(event);" class="notif-dropdown-item text-danger">🗑️ Clear All</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="notificationsTable">
                            <thead><tr><th>Type</th><th>Message</th><th>Time</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
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
        // Notifications functions
        async function loadNotifications() {
            const res = await apiCall('get_notifications');
            if (res.success) {
                const tbody = document.querySelector('#notificationsTable tbody');
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
        // Show/hide notification dot in nav based on unread count
        function updateNotificationDot(unreadCount) {
            const dot = document.getElementById('notificationsDot');
            if (!dot) return;
            dot.classList.toggle('hidden', unreadCount === 0);
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

        let allBooks = [];

        async function loadAvailableBooks() {
            const res = await apiCall('get_books');
            if(res.success) {
                allBooks = res.data.filter(b => b.status === 'Available');
                renderAvailableBooks(allBooks);
            }
        }
        // Render books in the Browse tab with a Borrow button  
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
     // Filter books in the Browse tab based on search input (title or author)
        function filterBooks() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allBooks.filter(b => 
                b.title.toLowerCase().includes(query) || 
                b.author.toLowerCase().includes(query)
            );
            renderAvailableBooks(filtered);
        }
       // Handle borrow request when user clicks "Borrow" button
        async function borrowBook(book_id) {
            if(await showConfirm('Do you want to send this borrow request?', 'Confirm Borrow Request')) {
                const res = await apiCall('request_borrow', {book_id});
                if(res.success) {
                    alert('Borrow request sent. Wait for librarian approval.');
                    loadAvailableBooks();
                    loadMyTransactions();
                    loadNotifications();
                } else {
                    alert(res.message);
                }
            }
        }
      // Load user's current borrow transactions and display in My Books tab with status and action buttons
        async function loadMyTransactions() {
            const res = await apiCall('get_my_transactions');
            if(res.success) {
                const tbody = document.querySelector('#myBooksTable tbody');
                tbody.innerHTML = '';
                res.data.forEach(t => {
                    // 1. Detect overdue
                let badgeClass = 'borrowed';
            let statusText = t.status;

            if (t.status === 'Borrowed' && t.due_date) {

                const today = new Date();
                const due = new Date(t.due_date);

                const daysLeft = Math.ceil((due - today) / (1000 * 60 * 60 * 24));

                    if (daysLeft < 0) {
                        badgeClass = 'danger';
                        statusText = `Overdue (${Math.abs(daysLeft)} days late)`;
                    }
                    else if (daysLeft <= 2) {
                        badgeClass = 'warning';
                        statusText = `Due soon (${daysLeft} days left)`;
                    }
                }
                else if (t.status === 'Returned') {
                    badgeClass = 'returned';
                    statusText = 'Returned';
                }
                // 2. Action button
                let actionHtml = t.status === 'Borrowed' 
                    ? `<button class="btn btn-secondary btn-sm" onclick="returnBook(${t.book_id})">Request Return</button>`
                    : `<span class="badge returned">Returned on ${t.return_date}</span>`;
                if (t.status === 'Pending') {
                    actionHtml = `<span class="badge warning">Pending librarian approval</span>`;
                } else if (t.status === 'Return Pending') {
                    actionHtml = `<span class="badge warning">Waiting return confirmation</span>`;
                } else if (t.status === 'Rejected') {
                    actionHtml = `<span class="badge overdue">Request rejected</span>`;
                }

                // 3. Table row
                tbody.innerHTML += `
                    <tr>
                        <td>${t.book_title}</td>
                        <td>${t.borrow_date}</td>
                        <td>${t.return_date}</td>
                        <td>${t.due_date}</td>
                        <td>
                            <span class="badge ${badgeClass}">
                                ${statusText}
                            </span>
                        </td>
                        <td>${actionHtml}</td>
                    </tr>
                `;
            });
         }
        }
     // Handle return request when user clicks "Request Return" button for a borrowed book
        async function returnBook(book_id) {
            if(await showConfirm('Send return request to librarian?', 'Confirm Return Request')) {
                const res = await apiCall('request_return', {book_id});
                if(res.success) {
                    alert('Return request sent. Wait for librarian confirmation.');
                    loadMyTransactions();
                    loadNotifications();
                    loadAvailableBooks();
                } else {
                    alert(res.message);
                }
            }
        }

        // Initialize
        loadAvailableBooks();
        loadNotifications();
        setInterval(loadNotifications, 30000);
    </script>
</body>
</html>
