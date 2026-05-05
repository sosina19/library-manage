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
            <button onclick="logout()" class="logout-btn"> Logout</button>
        </div>
    </div>
</div>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            </div>

            <!-- DASHBOARD TAB -->
            <div id="dashboard-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Library Overview</h3>
                    </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <h4>Total Books</h4>
                    <p id="totalBooks">0</p>
                </div>

                <div class="stat-box">
                    <h4>Available Books</h4>
                    <p id="availableBooks">0</p>
                </div>

                <div class="stat-box">
                    <h4>Borrowed Books</h4>
                    <p id="borrowedBooks">0</p>
                </div>

                <div class="stat-box">
                    <h4>Total Users</h4>
                    <p id="totalUsers">0</p>
                </div>

                <div class="stat-box">
                    <h4>Admins</h4>
                    <p id="totalAdmins">0</p>
                </div>

                <div class="stat-box">
                    <h4>Librarians</h4>
                    <p id="totalLibrarians">0</p>
                </div>

                <div class="stat-box">
                    <h4>Students</h4>
                    <p id="totalNormalUsers">0</p>
                </div>
            </div>
                </div>

                <!-- Recent Activities -->
                <div class="card" style="margin-top:20px;">
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
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Users Management</h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('userModal')">
                + Add User
            </button>
        </div>

        <!-- Controls Bar -->
        <div style="display:flex; gap:10px; margin:15px 0; flex-wrap:wrap;">

            <!-- Search -->
            <input 
                type="text" 
                id="userSearch" 
                class="form-control" 
                placeholder="Search by username or email..."
                onkeyup="loadUsers()"
                style="flex:1; min-width:200px;"
            >

            <!-- Filter -->
            <select id="userFilter" class="form-control" style="max-width:200px;" onchange="loadUsers()">
                <option value="all">All Roles</option>
                <option value="admin">Admins</option>
                <option value="librarian">Librarians</option>
                <option value="user">Users</option>
            </select>

        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>#</th>
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
            <div id="books-tab" class="tab-content hidden">
                <div class="card">
                    <div class="card-header">
                        <h3>All Books Catalog</h3>
                    </div>

                    <!-- Search + Filter -->
                    <div style="display:flex; gap:10px; margin:15px 0;">
                        <input type="text" id="bookSearch" class="form-control" placeholder="Search books by title, author, ISBN...">
                        <select id="bookFilter" class="form-control" style="max-width:200px;">
                            <option value="all">All</option>
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                        </select>
                        <button class="btn btn-primary" onclick="loadBooks()">Search</button>
                    </div>

                    <div class="table-responsive">
                        <table id="booksTable">
                            <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>Status</th></tr></thead>
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

    const res = await apiCall('get_users', {filter:filter, search:search});

    if(res.success) {
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
                        <button onclick="editUser(${u.id}, '${u.username}', '${u.role}', '${u.email || ''}')">✏️</button>
                        <button onclick="deleteUser(${u.id})">❌</button>
                    </td>
                </tr>
            `;
        });
    }
}

        async function loadBooks() {
            const search = document.getElementById('bookSearch').value;
            const filter = document.getElementById('bookFilter').value;

            const res = await apiCall('get_books', {search, filter});

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
                    </tr>`;
                });
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
            if(!id) {
                data.password = document.getElementById('u_password').value;
                res = await apiCall('add_user', data);
            } else {
                res = await apiCall('update_user', {id: id, role: data.role, email: data.email});
            }

           if(res.success) {
                closeModal('userModal');
                loadUsers();
                loadDashboard(); // 🔥 refresh stats after add/update/delete
                document.getElementById('userForm').reset();

                // 🔥 make password field visible again after edit mode
                document.getElementById('passGroup').style.display = "block";
            } else {
                alert(res.message);
            }
        });
        async function deleteUser(id) {
                if(confirm('Are you sure you want to delete this user?')) {
                    const res = await apiCall('delete_user', {id});
                    if(res.success) {
                        loadUsers();
                        loadDashboard(); // refresh totals
                    }
                    else alert(res.message);
                }
            }
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
    </script>
</body>
</html>