<?php
// Disable caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

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
    <title>Admin Dashboard - Billing Portal</title>
    <link rel="icon" type="image/png" sizes="32x32" href="logo.png">
    <link rel="shortcut icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" href="logo.png">
    <link rel="stylesheet" href="css/admin-enhanced.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-file-invoice-dollar"></i> Billing Portal</h1>
                </div>
                <nav class="nav-menu">
                    <a href="admin.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="user_management.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                    <a href="document_management.php" class="nav-link"><i class="fas fa-folder-open"></i> Documents</a>
                    <a href="payment_management.php" class="nav-link"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="#" class="nav-link" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>Manage your billing portal from this admin dashboard</p>
        </div>

        <!-- Statistics Section -->
        <div class="stats-section">
            <h3><i class="fas fa-chart-bar"></i> Quick Stats</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number" id="total-users">0</div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="total-documents">0</div>
                    <div class="stat-label">Documents</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="active-sessions">1</div>
                    <div class="stat-label">Active Sessions</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="total-activities">0</div>
                    <div class="stat-label">Recent Activities</div>
                </div>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="card-title">User Management</h3>
                <p class="card-description">
                    Manage customer accounts, view user profiles, and handle user permissions.
                </p>
                <a href="user_management.php" class="card-button">
                    <i class="fas fa-users-cog"></i> Manage Users
                </a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3 class="card-title">Document Management</h3>
                <p class="card-description">
                    Upload, organize, and manage billing documents and customer files.
                </p>
                <a href="document_management.php" class="card-button">
                    <i class="fas fa-folder-open"></i> Manage Documents
                </a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="card-title">Reports & Analytics</h3>
                <p class="card-description">
                    Track customer payment status, generate reports, and view analytics.
                </p>
                <a href="payment_reports.php" class="card-button">
                    <i class="fas fa-chart-pie"></i> View Payment Reports
                </a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3 class="card-title">System Settings</h3>
                <p class="card-description">
                    Configure system settings, manage permissions, and update preferences.
                </p>
                <button class="card-button" onclick="openSettings()">
                    <i class="fas fa-sliders-h"></i> Settings
                </button>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3 class="card-title">Payment Status</h3>
                <p class="card-description">
                    Manage customer payments and update payment statuses.
                </p>
                <a href="payment_management.php" class="card-button">
                    <i class="fas fa-money-check-alt"></i> Manage Payments
                </a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="card-title">Activity Log</h3>
                <p class="card-description">
                    Monitor system activities and track user actions.
                </p>
                <button class="card-button" onclick="openActivityLog()">
                    <i class="fas fa-list-alt"></i> View Log
                </button>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity">
            <h3><i class="fas fa-clock"></i> Recent Activity</h3>
            <div id="activity-list">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Admin login successful</div>
                        <div class="activity-time">Just now</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">System initialized</div>
                        <div class="activity-time">Today</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Removed admin.js - not needed for simple dashboard -->
    <script>
        // Load dashboard data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            // loadRecentActivity(); // Disabled - causes redirect loop
        });

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('api/logout.php', {
                    method: 'POST'
                }).then(() => {
                    sessionStorage.clear();
                    window.location.href = 'login.html';
                }).catch(() => {
                    // Fallback logout
                    sessionStorage.clear();
                    window.location.href = 'login.html';
                });
            }
        }

        // Dashboard functions (placeholders for now)
        function openUserManagement() {
            alert('User Management feature coming soon!');
        }

        function openDocumentManagement() {
            alert('Document Management feature coming soon!');
        }

        function openReports() {
            window.location.href = 'payment_reports.php';
        }

        function openSettings() {
            alert('System Settings feature coming soon!');
        }

        function openPaymentManagement() {
            window.location.href = 'payment_management.php';
        }

        function openActivityLog() {
            alert('Activity Log feature coming soon!');
        }

        // Load dashboard statistics
        async function loadDashboardStats() {
            try {
                const response = await fetch('api/admin_stats.php');
                if (response.ok) {
                    const result = await response.json();
                    console.log('Stats API response:', result);
                    
                    // Check if response has stats object (nested) or direct properties
                    const stats = result.stats || result;
                    
                    document.getElementById('total-users').textContent = stats.total_users || 0;
                    document.getElementById('total-documents').textContent = stats.total_documents || 0;
                    document.getElementById('total-activities').textContent = result.recent_activity ? result.recent_activity.length : 0;
                } else {
                    console.error('Stats loading failed with status:', response.status);
                }
            } catch (error) {
                console.error('Stats loading error:', error);
                // Keep default values
            }
        }

        // Load recent activity - Removed to prevent redirect loop
        async function loadRecentActivity() {
            // Disabled - no recent_activity.php endpoint exists yet
            console.log('Recent activity feature not implemented yet');
        }

        // Scroll to Top Button Functionality
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });
        
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>