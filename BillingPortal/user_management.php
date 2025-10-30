<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Billing Portal</title>
    <link rel="icon" type="image/png" sizes="32x32" href="logo.png">
    <link rel="shortcut icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" href="logo.png">
    <link rel="stylesheet" href="css/admin-enhanced.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Style for disabled username field */
        #username:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }
        
        #username:disabled::placeholder {
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-file-invoice-dollar"></i> Billing Portal</h1>
                </div>
                <nav class="nav-menu">
                    <a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="user_management.php" class="nav-link active"><i class="fas fa-users"></i> Users</a>
                    <a href="document_management.php" class="nav-link"><i class="fas fa-folder-open"></i> Documents</a>
                    <a href="payment_management.php" class="nav-link"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="#" class="nav-link" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="page-title">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>✨ Manage customer accounts, view user profiles, and handle user permissions</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number" id="total-users">0</div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="active-users">0</div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="admin-users">0</div>
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="customer-users">0</div>
                <div class="stat-label">Customers</div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Search users by name, email, or username...">
            </div>
            <div class="actions">
                <button class="btn btn-success" onclick="openAddUserModal()">
                    <i class="fas fa-plus"></i> Add New User
                </button>
                <button class="btn btn-secondary" onclick="exportUsers()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table">
            <div class="table-header">
                <h3><i class="fas fa-table"></i> All Users</h3>
                <div class="filters" style="display: flex; gap: 12px;">
                    <select id="role-filter" onchange="filterUsers()" class="form-control form-select" style="width: auto; min-width: 150px;">
                        <option value="">🎭 All Roles</option>
                        <option value="admin">👑 Admin</option>
                        <option value="customer">👤 Customer</option>
                    </select>
                    <select id="status-filter" onchange="filterUsers()" class="form-control form-select" style="width: auto; min-width: 150px;">
                        <option value="">📊 All Status</option>
                        <option value="active">✅ Active</option>
                        <option value="inactive">❌ Inactive</option>
                        <option value="pending">⏳ Pending</option>
                    </select>
                </div>
            </div>
            <div id="users-table-content">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading users...
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Add New User</h3>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <input type="hidden" id="user-id">
                    <div class="form-group">
                        <label class="form-label" for="full-name">Full Name *</label>
                        <input type="text" class="form-control" id="full-name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="username">Username *</label>
                        <input type="text" class="form-control" id="username" required>
                        <small id="username-feedback" class="form-text"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" class="form-control" id="email" required>
                        <small id="email-feedback" class="form-text"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="user-type">User Role *</label>
                        <select class="form-control form-select" id="user-type" required>
                            <option value="">Select Role</option>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status">Status *</label>
                        <select class="form-control form-select" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="form-group" id="password-group">
                        <label class="form-label" for="password">Password *</label>
                        <input type="password" class="form-control" id="password">
                        <small class="text-muted">Leave blank to keep current password (for edit)</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">
                    <i class="fas fa-save"></i> Save User
                </button>
            </div>
        </div>
    </div>

    <script>
        let allUsers = [];
        let currentEditingUser = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            loadStats();
            setupSearch();
            
            // Add real-time validation listeners
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            
            if (usernameInput) {
                usernameInput.addEventListener('input', function(e) {
                    checkUsernameAvailability(e.target.value);
                });
            }
            
            if (emailInput) {
                emailInput.addEventListener('input', function(e) {
                    checkEmailAvailability(e.target.value);
                });
            }
        });

        // Load all users
        async function loadUsers() {
            try {
                const response = await fetch('api/users.php');
                const result = await response.json();
                
                if (result.success) {
                    allUsers = result.users;
                    displayUsers(allUsers);
                } else {
                    showError('Failed to load users: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading users:', error);
                showError('Failed to load users. Please try again.');
            }
        }

        // Display users in table
        function displayUsers(users) {
            const tableContent = document.getElementById('users-table-content');
            
            if (users.length === 0) {
                tableContent.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h4>No Users Found</h4>
                        <p>No users match your current filters.</p>
                    </div>
                `;
                return;
            }

            tableContent.innerHTML = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${users.map(user => `
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            ${(user.full_name || user.username).charAt(0).toUpperCase()}
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name">${user.full_name || user.username}</div>
                                            <div class="user-email">${user.email}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-${user.user_type}">
                                        ${user.user_type.charAt(0).toUpperCase() + user.user_type.slice(1)}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-${user.account_status || 'active'}">
                                        ${(user.account_status || 'active').charAt(0).toUpperCase() + (user.account_status || 'active').slice(1)}
                                    </span>
                                </td>
                                <td>${formatDate(user.created_at)}</td>
                                <td>${user.last_login ? formatDate(user.last_login) : 'Never'}</td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-sm btn-primary" onclick="editUser(${user.user_id})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="resetPassword(${user.user_id})">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        ${user.user_type !== 'admin' ? `
                                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.user_id})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        // Load statistics
        async function loadStats() {
            try {
                const response = await fetch('api/user_stats.php');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('total-users').textContent = result.stats.total || 0;
                    document.getElementById('active-users').textContent = result.stats.active || 0;
                    document.getElementById('admin-users').textContent = result.stats.admins || 0;
                    document.getElementById('customer-users').textContent = result.stats.customers || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Setup search functionality
        function setupSearch() {
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const filteredUsers = allUsers.filter(user => 
                    user.username.toLowerCase().includes(searchTerm) ||
                    user.email.toLowerCase().includes(searchTerm) ||
                    (user.full_name && user.full_name.toLowerCase().includes(searchTerm))
                );
                displayUsers(filteredUsers);
            });
        }

        // Filter users
        function filterUsers() {
            const roleFilter = document.getElementById('role-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            
            let filteredUsers = allUsers.filter(user => {
                const matchesRole = !roleFilter || user.user_type === roleFilter;
                const matchesStatus = !statusFilter || (user.account_status || 'active') === statusFilter;
                const matchesSearch = !searchTerm || 
                    user.username.toLowerCase().includes(searchTerm) ||
                    user.email.toLowerCase().includes(searchTerm) ||
                    (user.full_name && user.full_name.toLowerCase().includes(searchTerm));
                
                return matchesRole && matchesStatus && matchesSearch;
            });
            
            displayUsers(filteredUsers);
        }

        // Modal functions
        function openAddUserModal() {
            currentEditingUser = null;
            document.getElementById('modal-title').textContent = 'Add New User';
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('username').disabled = false; // Enable username for new users
            document.getElementById('username-feedback').textContent = ''; // Clear feedback message
            document.getElementById('username-feedback').style.color = '';
            document.getElementById('password').required = true;
            document.getElementById('userModal').style.display = 'block';
        }

        function editUser(userId) {
            const user = allUsers.find(u => u.user_id === userId);
            if (!user) {
                console.error('User not found for editing:', userId);
                return;
            }
            
            currentEditingUser = user;
            document.getElementById('modal-title').textContent = 'Edit User';
            document.getElementById('user-id').value = user.user_id;
            document.getElementById('full-name').value = user.full_name || '';
            document.getElementById('username').value = user.username;
            document.getElementById('username').disabled = true; // Disable username editing
            document.getElementById('username-feedback').textContent = '🔒 Username cannot be changed after creation';
            document.getElementById('username-feedback').style.color = '#6c757d';
            document.getElementById('email').value = user.email;
            document.getElementById('user-type').value = user.user_type;
            document.getElementById('status').value = user.account_status || 'active';
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('userModal').style.display = 'block';
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
            currentEditingUser = null;
            // Clear feedback messages
            document.getElementById('username-feedback').textContent = '';
            document.getElementById('email-feedback').textContent = '';
        }

        // Real-time username validation
        let usernameCheckTimeout = null;
        async function checkUsernameAvailability(username) {
            const feedback = document.getElementById('username-feedback');
            const currentUserId = document.getElementById('user-id').value;
            
            // Clear previous timeout
            if (usernameCheckTimeout) {
                clearTimeout(usernameCheckTimeout);
            }
            
            if (!username || username.length < 3) {
                feedback.textContent = '';
                feedback.className = 'form-text';
                return;
            }
            
            // Show checking message
            feedback.textContent = '🔍 Checking availability...';
            feedback.className = 'form-text text-muted';
            
            // Debounce the API call
            usernameCheckTimeout = setTimeout(async () => {
                try {
                    // Fetch all users (not just customers) to check duplicates
                    const response = await fetch('api/users.php?type=all', {
                        method: 'GET'
                    });
                    const result = await response.json();
                    
                    if (result.success && result.users) {
                        // Check if username exists (excluding current user if editing)
                        const userExists = result.users.some(u => 
                            u.username.toLowerCase() === username.toLowerCase() && 
                            u.user_id != currentUserId
                        );
                        
                        if (userExists) {
                            feedback.textContent = '❌ Username already taken';
                            feedback.className = 'form-text text-danger';
                        } else {
                            feedback.textContent = '✓ Username available';
                            feedback.className = 'form-text text-success';
                        }
                    }
                } catch (error) {
                    console.error('Error checking username:', error);
                    feedback.textContent = '';
                }
            }, 500); // Wait 500ms after user stops typing
        }
        
        // Real-time email validation
        let emailCheckTimeout = null;
        async function checkEmailAvailability(email) {
            const feedback = document.getElementById('email-feedback');
            const currentUserId = document.getElementById('user-id').value;
            
            // Clear previous timeout
            if (emailCheckTimeout) {
                clearTimeout(emailCheckTimeout);
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                feedback.textContent = '';
                feedback.className = 'form-text';
                return;
            }
            
            // Show checking message
            feedback.textContent = '🔍 Checking availability...';
            feedback.className = 'form-text text-muted';
            
            // Debounce the API call
            emailCheckTimeout = setTimeout(async () => {
                try {
                    // Fetch all users (not just customers) to check duplicates
                    const response = await fetch('api/users.php?type=all', {
                        method: 'GET'
                    });
                    const result = await response.json();
                    
                    if (result.success && result.users) {
                        // Check if email exists (excluding current user if editing)
                        const emailExists = result.users.some(u => 
                            u.email.toLowerCase() === email.toLowerCase() && 
                            u.user_id != currentUserId
                        );
                        
                        if (emailExists) {
                            feedback.textContent = '❌ Email already registered';
                            feedback.className = 'form-text text-danger';
                        } else {
                            feedback.textContent = '✓ Email available';
                            feedback.className = 'form-text text-success';
                        }
                    }
                } catch (error) {
                    console.error('Error checking email:', error);
                    feedback.textContent = '';
                }
            }, 500); // Wait 500ms after user stops typing
        }

        // Save user
        async function saveUser() {
            const formData = {
                id: document.getElementById('user-id').value,
                full_name: document.getElementById('full-name').value,
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                user_type: document.getElementById('user-type').value,
                status: document.getElementById('status').value,
                password: document.getElementById('password').value
            };

            if (!formData.full_name || !formData.username || !formData.email || !formData.user_type) {
                showError('Please fill in all required fields.');
                return;
            }

            if (!formData.id && !formData.password) {
                showError('Password is required for new users.');
                return;
            }

            try {
                const url = formData.id ? 'api/users.php' : 'api/users.php';
                const method = formData.id ? 'PUT' : 'POST';
                
                // For PUT requests, include user_id
                if (formData.id) {
                    formData.user_id = parseInt(formData.id);
                }
                
                console.log('Saving user:', method, formData);
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response data:', result);
                
                if (result.success) {
                    showSuccess(formData.id ? 'User updated successfully!' : 'User created successfully!');
                    closeUserModal();
                    loadUsers();
                    loadStats();
                } else {
                    // Show specific error message from API
                    const errorMsg = result.error || result.message || 'Unknown error';
                    
                    // Customize error messages
                    if (errorMsg.toLowerCase().includes('username already exists')) {
                        showError('⚠️ Username already exists! Please choose a different username.');
                    } else if (errorMsg.toLowerCase().includes('email already exists')) {
                        showError('⚠️ Email already exists! Please use a different email address.');
                    } else if (errorMsg.toLowerCase().includes('invalid email')) {
                        showError('⚠️ Invalid email format! Please enter a valid email address.');
                    } else {
                        showError('Failed to save user: ' + errorMsg);
                    }
                }
            } catch (error) {
                console.error('Error saving user:', error);
                showError('❌ Failed to save user. Please check your connection and try again.');
            }
        }

        // Delete user
        async function deleteUser(userId, force = false) {
            const user = allUsers.find(u => u.user_id === userId);
            if (!user) {
                console.error('User not found:', userId);
                return;
            }

            // Initial confirmation if not forced
            if (!force && !confirm(`Are you sure you want to delete user "${user.username}"? This action cannot be undone.`)) {
                return;
            }

            try {
                console.log('Deleting user:', userId, 'Force:', force);
                const response = await fetch('api/users.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId, force: force })
                });

                console.log('Delete response status:', response.status);
                const result = await response.json();
                console.log('Delete response:', result);
                
                if (result.success) {
                    let message = 'User deleted successfully!';
                    if (result.deleted_documents > 0 || result.deleted_payments > 0) {
                        message += `\n\nAlso deleted:`;
                        if (result.deleted_documents > 0) message += `\n• ${result.deleted_documents} document(s)`;
                        if (result.deleted_payments > 0) message += `\n• ${result.deleted_payments} payment record(s)`;
                    }
                    showSuccess(message);
                    loadUsers();
                    loadStats();
                } else if (result.warning && !force) {
                    // Show warning and ask for confirmation to force delete
                    const forceDelete = confirm(`⚠️ ${result.message}\n\nThis will permanently delete:\n• The user account\n• ${result.doc_count || 0} document(s) and files\n• ${result.payment_count || 0} payment record(s)\n\nProceed with deletion?`);
                    
                    if (forceDelete) {
                        // Recursively call with force = true
                        await deleteUser(userId, true);
                    }
                } else {
                    showError('Failed to delete user: ' + (result.message || result.error));
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                showError('Failed to delete user. Please try again.');
            }
        }

        // Reset password
        async function resetPassword(userId) {
            const user = allUsers.find(u => u.user_id === userId);
            if (!user) {
                console.error('User not found for password reset:', userId);
                return;
            }

            const newPassword = prompt(`Enter new password for "${user.username}":`);
            if (!newPassword) return;

            if (newPassword.length < 6) {
                showError('Password must be at least 6 characters long.');
                return;
            }

            try {
                const response = await fetch('api/reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        user_id: userId,
                        new_password: newPassword 
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Password reset successfully!');
                } else {
                    showError('Failed to reset password: ' + result.message);
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                showError('Failed to reset password. Please try again.');
            }
        }

        // Export users
        function exportUsers() {
            const csvContent = "data:text/csv;charset=utf-8," + 
                "ID,Username,Full Name,Email,Role,Status,Joined,Last Login\n" +
                allUsers.map(user => 
                    `${user.user_id},"${user.username}","${user.full_name || ''}","${user.email}","${user.user_type}","${user.account_status || 'active'}","${formatDate(user.created_at)}","${user.last_login ? formatDate(user.last_login) : 'Never'}"`
                ).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "users_export.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Utility functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        }

        function showSuccess(message) {
            showMessage(message, 'success');
        }

        function showError(message) {
            showMessage(message, 'error');
        }

        function showMessage(message, type) {
            // Remove existing messages
            const existingMessage = document.querySelector('.message');
            if (existingMessage) {
                existingMessage.remove();
            }

            // Create new message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                max-width: 300px;
            `;
            
            if (type === 'success') {
                messageDiv.style.background = '#28a745';
            } else if (type === 'error') {
                messageDiv.style.background = '#dc3545';
            }
            
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 3000);
        }

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('api/logout.php', {
                    method: 'POST'
                }).then(() => {
                    sessionStorage.clear();
                    window.location.href = 'login.html';
                }).catch(() => {
                    sessionStorage.clear();
                    window.location.href = 'login.html';
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                closeUserModal();
            }
        }

        // Initialize scroll to top functionality after DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            const scrollToTopBtn = document.getElementById('scrollToTop');
            
            if (scrollToTopBtn) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollToTopBtn.classList.add('visible');
                    } else {
                        scrollToTopBtn.classList.remove('visible');
                    }
                });
                
                scrollToTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </button>
</body>
</html>