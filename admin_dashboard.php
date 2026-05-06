<?php
require 'auth.php';
checkLoggedIn();
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <a class="active" data-tab="dashboard-tab" onclick="switchTab('dashboard-tab'); loadDashboard();">Dashboard</a>
                <a data-tab="users-tab" onclick="switchTab('users-tab'); loadUsers();">Manage Users</a>
                <a data-tab="books-tab" onclick="switchTab('books-tab'); loadBooks();">View Books</a>
                <a id="notificationsNavLink" data-tab="notifications-tab" onclick="switchTab('notifications-tab'); loadNotifications();">Notifications <span id="notificationsDot" class="notif-dot hidden"></span></a>
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
                <div id="notificationHeaderActions" style="display:none; gap:10px; align-items:center;">
                    <button class="btn btn-secondary btn-sm" onclick="markAllNotificationsRead()">Mark all read</button>
                    <button class="btn btn-danger btn-sm" onclick="clearNotifications()">Clear all</button>
                </div>
            </div>

            <!-- DASHBOARD TAB -->
            <div id="dashboard-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Library Overview</h3>
                    </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <h4>📚 Total Books</h4>
                    <p id=" TotalBooks">0</p>
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
                    <h4>👤 Total Users</h4>
                    <p id="totalUsers">0</p>
                </div>

                <div class="stat-box">
                    <h4>🛡️Admins</h4>
                    <p id="totalAdmins">0</p>
                </div>

                <div class="stat-box">
                    <h4>📚Librarians</h4>
                    <p id="totalLibrarians">0</p>
                </div>

                <div class="stat-box">
                    <h4>👩‍🎓 Students</h4>
                    <p id="totalNormalUsers">0</p>
                </div>
            </div>
                </div>

                <!-- Recent Activities -->
                <div class="card" id="recentActivities" style="margin-top:20px;">
                    <div class="card-header">
                        <h3>Recent Activities (Borrows / Returns)</h3>
                    </div>
                    <div class="table-responsive">
                        <table id="activitiesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Book</th>
                                    <th>Action</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div id="users-tab" class="tab-content hidden">
    <div class="card">

        <!-- Header -->
        <div class="card-header" id="rece" style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Users Management</h3>
           
        </div>

        <!-- Controls Bar -->
         <div class="controls-bar">
        <div style="display:flex; gap:10px; margin:15px 0; flex-wrap:wrap;">

            <!-- Search -->
            <input 
                type="text" 
                id="userSearch" 
                class="form-control" 
                placeholder="🔍 Search..."
                onkeyup="loadUsers()"
                style="flex:1; min-width:200px;"
            >
        
            <!-- Filter -->
            <select id="userFilter" class="form-control" style="max-width:200px;" onchange="loadUsers()">
                <option value="all">🌐 All Roles</option>
                <option value="admin">🛡️ Admins</option>
                <option value="librarian">📚 Librarians</option>
                <option value="user">👤 Users</option>
            </select>
              <button class="btn btn-primary btn-sm" onclick="openModal('userModal')">
                + Add User
            </button>
</div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>#Id</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
</div>

            <!-- Books Tab -->
            <div id="books-tab" class="tab-content hidden" >
                <div class="card">
                    <div class="card-header">
                        <h3>All Books Catalog</h3>
                    </div>

                    <!-- Search + Filter -->
                     <div class="controls-bar">
                    <div style="display:flex; gap:10px; margin:15px 0;">
                        <input type="text" id="bookSearch" class="form-control" placeholder="🔍 Search..." oninput="loadBooks()">
                        <select id="bookFilter" class="form-control" style="max-width:200px;" onchange="loadBooks()">
                            <option value="all">All</option>
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                        </select>
                        
                    </div>
                     </div>
                    <div class="table-responsive">
                        <table id="booksTable">
                            <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>Status</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="notifications-tab" class="tab-content hidden">
                <div class="card">
                    <div class="card-header">
                        <h3>Admin Notifications</h3>
                    </div>
                    <div class="controls-bar">
                        <div style="display:flex; gap:10px; margin:15px 0; flex-wrap:wrap; align-items:center;">
                            <select id="adminRecipientRole" class="form-control" style="max-width:180px;" onchange="onRecipientRoleChange()">
                                <option value="user">👤 Users</option>
                                <option value="librarian">📚 Librarians</option>
                            </select>
                            <div style="position:relative; flex:1; min-width:260px;">
                                <input type="text" id="adminTargetUserSearch" class="form-control" 
                                list="adminRecipientsList" placeholder="🔎 Search recipient..." autocomplete="off" 
                                oninput="showAdminRecipientSuggestions(this.value)" onfocus="showAdminRecipientSuggestions(this.value)">
                                <div id="adminRecipientSuggestions" class="hidden" style="position:absolute; top:44px; left:0; right:0; max-height:220px; overflow:auto; background:#fff; border:1px solid #e2e8f0; border-radius:10px; z-index:50; box-shadow:0 8px 20px rgba(0,0,0,0.12);"></div>
                            </div>
                            <datalist id="adminRecipientsList"></datalist>
                            <input type="text" id="adminAnnouncementInput" class="form-control" 
                            style="flex:1; min-width:260px;" placeholder="✉️ Write message...">
                            <button class="btn btn-primary btn-sm" onclick="sendDirectNotification()">Send </button>
                            <button class="btn btn-secondary btn-sm" onclick="sendAnnouncement()">Broadcast </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="adminNotificationsTable">
                            <thead><tr><th>Type</th><th>Message</th><th>Time</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('userModal')">&times;</button>
            <h3 class="modal-title">Manage User</h3>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="u_username" class="form-control" required>
                </div>
                <div class="form-group" id="passGroup">
                    <label>Password</label>
                    <input type="password" id="u_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="u_role" class="form-control">
                        <option value="user">User</option>
                        <option value="librarian">Librarian</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="u_email" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Save User</button>
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
        async function loadDashboard() {
            const res = await apiCall('dashboard_stats');
            if(res.success) {
                document.getElementById('totalBooks').innerText = res.data.total_books;
                document.getElementById('availableBooks').innerText = res.data.available_books;
                document.getElementById('borrowedBooks').innerText = res.data.borrowed_books;

                document.getElementById('totalUsers').innerText = res.data.total_users;
                document.getElementById('totalAdmins').innerText = res.data.admins;
                document.getElementById('totalLibrarians').innerText = res.data.librarians;
                document.getElementById('totalNormalUsers').innerText = res.data.users;
            }

            const act = await apiCall('recent_activities');
            if(act.success) {
                const tbody = document.querySelector('#activitiesTable tbody');
                tbody.innerHTML = '';
                act.data.forEach(a => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${a.id}</td>
                            <td>${a.username}</td>
                            <td>${a.book_title}</td>
                            <td><span class="badge ${a.action === 'borrow' ? 'borrowed' : 'returned'}">${a.action}</span></td>
                            <td>${a.date}</td>
                        </tr>
                    `;
                });
            }
        }

   async function loadUsers() {
    const filter = document.getElementById('userFilter').value;
    const search = document.getElementById('userSearch').value;

    const res = await apiCall('get_users', {filter, search});

    if (res.success) {
        const tbody = document.querySelector('#usersTable tbody');
        tbody.innerHTML = '';

        let count = 1;

        res.data.forEach(u => {
            tbody.innerHTML += `
                <tr>
                    <td>${count++}</td>
                    <td>${u.username}</td>
                    <td><span class="badge">${u.role}</span></td>
                    <td>${u.email || '-'}</td>
                    <td style="text-align:right;">
                        <button class="action-btn edit"
                            onclick="editUser(${u.id}, '${u.username}', '${u.role}', '${u.email || ''}')">
                            ✏️
                        </button>

                        <button class="action-btn delete"
                            onclick="openDeleteModal(${u.id})">
                            🗑️
                        </button>
                    </td>
                </tr>
            `;
        });
            }
        } 
        function openDeleteModal(id) {
            document.getElementById('deleteUserId').value = id;
            openModal('deleteModal');
        }

        async function confirmDelete() {
            const id = document.getElementById('deleteUserId').value;

            const res = await apiCall('delete_user', {id});

            if (res.success) {
                closeModal('deleteModal');
                loadUsers();
                loadDashboard();
            } else {
                alert(res.message);
            }
        }

       let bookSearchTimeout;

async function loadBooks() {
    clearTimeout(bookSearchTimeout);

    bookSearchTimeout = setTimeout(async () => {
        const search = document.getElementById('bookSearch').value;
        const filter = document.getElementById('bookFilter').value;

        const res = await apiCall('get_books', {search, filter});

        if (res.success) {
            const tbody = document.querySelector('#booksTable tbody');
            tbody.innerHTML = '';

            res.data.forEach(b => {
                tbody.innerHTML += `
                    <tr>
                        <td>${b.id}</td>
                        <td>${b.title}</td>
                        <td>${b.author}</td>
                        <td>${b.isbn}</td>
                        <td>
                            <span class="badge ${b.status === 'Available' ? 'available' : 'borrowed'}">
                                ${b.status}
                            </span>
                        </td>
                    </tr>
                `;
            });
        }
    }, 300); // delay for smooth typing
}

async function loadNotifications() {
    const res = await apiCall('get_notifications');
    if (res.success) {
        const tbody = document.querySelector('#adminNotificationsTable tbody');
        tbody.innerHTML = '';
        let unreadCount = 0;
        res.data.forEach(n => {
            if (!Number(n.is_read)) unreadCount += 1;
            tbody.innerHTML += `
                <tr>
                    <td><span class="badge">${n.title}</span></td>
                    <td>${n.message}</td>
                    <td>${n.created_at}</td>
                    <td>${Number(n.is_read) ? '<span class="badge returned">Read</span>' : `<button class="btn btn-secondary btn-sm" onclick="markNotificationRead(${n.id})">Mark read</button>`}</td>
                </tr>
            `;
        });
        updateNotificationDot(unreadCount);
    }
}
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

let adminRecipientMap = {};
let adminRecipientItems = [];
async function loadRecipients() {
    const res = await apiCall('get_notification_recipients');
    const list = document.getElementById('adminRecipientsList');
    if (!list) return;
    if (res.success) {
        list.innerHTML = '';
        adminRecipientMap = {};
        const selectedRole = document.getElementById('adminRecipientRole')?.value || 'user';
        adminRecipientItems = [];
        res.data
            .filter(u => u.role === selectedRole)
            .forEach(u => {
                const label = `${u.username} (#${u.id})`;
                adminRecipientMap[label] = u.id;
                adminRecipientItems.push({ id: u.id, label, username: u.username });
                list.innerHTML += `<option value="${label}"></option>`;
            });
    }
}
function onRecipientRoleChange() {
    const search = document.getElementById('adminTargetUserSearch');
    if (search) search.value = '';
    const box = document.getElementById('adminRecipientSuggestions');
    if (box) {
        box.classList.add('hidden');
        box.innerHTML = '';
    }
    loadRecipients();
}
function showAdminRecipientSuggestions(query) {
    const box = document.getElementById('adminRecipientSuggestions');
    if (!box) return;
    const q = (query || '').trim().toLowerCase();
    if (!q) {
        box.classList.add('hidden');
        box.innerHTML = '';
        return;
    }
    const matches = adminRecipientItems
        .filter(u => u.username.toLowerCase().includes(q) || u.label.toLowerCase().includes(q))
        .slice(0, 8);
    if (matches.length === 0) {
        box.classList.add('hidden');
        box.innerHTML = '';
        return;
    }
    box.innerHTML = matches.map(u => `
        <button type="button" style="display:block; width:100%; text-align:left; padding:10px 12px; border:none; background:white; cursor:pointer;"
            onclick="selectAdminRecipient('${u.label.replace(/'/g, "\\'")}')">${u.label}</button>
    `).join('');
    box.classList.remove('hidden');
}
function selectAdminRecipient(label) {
    document.getElementById('adminTargetUserSearch').value = label;
    const box = document.getElementById('adminRecipientSuggestions');
    box.classList.add('hidden');
    box.innerHTML = '';
}
async function sendDirectNotification() {
    const selected = document.getElementById('adminTargetUserSearch').value.trim();
    const userId = adminRecipientMap[selected];
    const input = document.getElementById('adminAnnouncementInput');
    const message = input.value.trim();
    const roleLabel = document.getElementById('adminRecipientRole')?.value === 'librarian' ? 'librarian' : 'user';
    if (!userId || !message) {
        alert(`Search and select a ${roleLabel}, then write message.`);
        return;
    }
    const res = await apiCall('send_user_notification', {
        user_id: userId,
        title: 'Message from admin',
        message
    });
    if (res.success) {
        input.value = '';
        document.getElementById('adminTargetUserSearch').value = '';
        const box = document.getElementById('adminRecipientSuggestions');
        box.classList.add('hidden');
        box.innerHTML = '';
        loadNotifications();
        alert(`Notification sent to selected ${roleLabel}.`);
    } else {
        alert(res.message || 'Failed to send notification');
    }
}
document.addEventListener('click', function (e) {
    const searchWrap = document.getElementById('adminTargetUserSearch');
    const box = document.getElementById('adminRecipientSuggestions');
    if (!searchWrap || !box) return;
    if (e.target !== searchWrap && !box.contains(e.target)) {
        box.classList.add('hidden');
    }
});
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
    const input = document.getElementById('adminAnnouncementInput');
    const message = input.value.trim();
    const targetRole = document.getElementById('adminRecipientRole')?.value === 'librarian' ? 'librarian' : 'user';
    if (!message) return;
    const res = await apiCall('add_announcement', {
        title: targetRole === 'librarian' ? 'Admin message to librarians' : 'Library announcement',
        message,
        target_role: targetRole
    });
    if (res.success) {
        input.value = '';
        loadNotifications();
        showNotification(targetRole === 'librarian' ? 'Broadcast sent to librarians.' : 'Broadcast sent to users.');
    } else {
        showNotification(res.message || 'Failed to send broadcast', true);
    }
}

        document.getElementById('userForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('userId').value;

        const data = {
            username: document.getElementById('u_username').value,
            role: document.getElementById('u_role').value,
            email: document.getElementById('u_email').value
        };

        let res;

        if (!id) {
            data.password = document.getElementById('u_password').value;
            res = await apiCall('add_user', data);
        } else {
            res = await apiCall('update_user', {
                id,
                role: data.role,
                email: data.email
            });
        }

        if (res.success) {
            closeModal('userModal');
            loadUsers();
            loadDashboard();

            document.getElementById('userForm').reset();
            document.getElementById('passGroup').style.display = "block";
        } else {
            alert(res.message);
        }
    });
        function editUser(id, username, role, email) {
            document.getElementById('userId').value = id;
            document.getElementById('u_username').value = username;
            document.getElementById('u_role').value = role;
            document.getElementById('u_email').value = email;
            // Hide password when editing
            document.getElementById('passGroup').style.display = "none";
            openModal('userModal');
        }
        // Initialize Dashboard
        loadDashboard();
        loadNotifications();
        loadRecipients();
        updateHeaderNotificationActions();
        setInterval(loadNotifications, 30000);
    </script>
</body>
</html>