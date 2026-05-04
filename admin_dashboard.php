<?php
require_once __DIR__ . '/../auth.php';
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
                <a class="active" data-tab="users-tab" onclick="switchTab('users-tab'); loadUsers();">Manage Users</a>
                <a data-tab="books-tab" onclick="switchTab('books-tab'); loadBooks();">View Books</a>
            </nav>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            </div>

            <!-- Users Tab -->
            <div id="users-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Users & Librarians</h3>
                        <button class="btn btn-primary btn-sm" onclick="openModal('userModal')">+ Add User</button>
                    </div>
                    <div class="table-responsive">
                        <table id="usersTable">
                            <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Actions</th></tr></thead>
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
        async function loadUsers() {
            const res = await apiCall('get_users');
            if(res.success) {
                const tbody = document.querySelector('#usersTable tbody');
                tbody.innerHTML = '';
                res.data.forEach(u => {
                    tbody.innerHTML += `<tr>
                        <td>${u.id}</td>
                        <td>${u.username}</td>
                        <td><span class="badge ${u.role === 'admin' ? 'returned' : (u.role === 'librarian' ? 'available' : 'borrowed')}">${u.role}</span></td>
                        <td>${u.email || '-'}</td>
                        <td>
                            <button class="action-btn" onclick="deleteUser(${u.id})">❌</button>
                        </td>
                    </tr>`;
                });
            }
        }
        
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
                document.getElementById('userForm').reset();
            } else {
                alert(res.message);
            }
        });

        async function deleteUser(id) {
            if(confirm('Are you sure you want to delete this user?')) {
                const res = await apiCall('delete_user', {id});
                if(res.success) loadUsers();
                else alert(res.message);
            }
        }

        // Initialize
        loadUsers();
    </script>
</body>
</html>
