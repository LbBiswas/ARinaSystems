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
    <title>Payment Reports - Billing Portal</title>
    <link rel="icon" type="image/png" sizes="32x32" href="logo.png">
    <link rel="shortcut icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" href="logo.png">
    <link rel="stylesheet" href="css/admin-enhanced.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
            margin-bottom: 30px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #667eea;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .payment-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
        }

        .summary-card.paid {
            border-left-color: #10b981;
        }

        .summary-card.unpaid {
            border-left-color: #ef4444;
        }

        .summary-card.pending {
            border-left-color: #f59e0b;
        }

        .summary-card.overdue {
            border-left-color: #ef4444;
        }

        .summary-card.total {
            border-left-color: #667eea;
        }

        .summary-card h4 {
            margin: 0 0 10px 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }

        .summary-card.paid .summary-value {
            color: #10b981;
        }

        .summary-card.unpaid .summary-value {
            color: #ef4444;
        }

        .summary-card.pending .summary-value {
            color: #f59e0b;
        }

        .summary-card.overdue .summary-value {
            color: #ef4444;
        }

        .summary-card.total .summary-value {
            color: #667eea;
        }

        .summary-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }

        .payments-table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
            overflow-x: auto;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payments-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .payments-table th {
            padding: 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            white-space: nowrap;
        }

        .payments-table tbody tr {
            border-bottom: 1px solid #e0e7ff;
            transition: all 0.2s ease;
        }

        .payments-table tbody tr:hover {
            background: #f8f9ff;
        }

        .payments-table td {
            padding: 15px;
            color: #334155;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-badge.paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge.cancelled {
            background: #e5e7eb;
            color: #374151;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .action-btn.view {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-btn.view:hover {
            background: #3b82f6;
            color: white;
        }

        .action-btn.edit {
            background: #fef3c7;
            color: #92400e;
        }

        .action-btn.edit:hover {
            background: #f59e0b;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .no-data i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
        }

        .loading i {
            font-size: 48px;
            color: #667eea;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .export-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
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
                    <a href="user_management.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                    <a href="document_management.php" class="nav-link"><i class="fas fa-folder-open"></i> Documents</a>
                    <a href="payment_reports.php" class="nav-link active"><i class="fas fa-chart-line"></i> Reports</a>
                    <a href="payment_management.php" class="nav-link"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="#" class="nav-link" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="page-title">
            <h2><i class="fas fa-chart-line"></i> Payment Reports & Analytics</h2>
            <p>Track customer payment status and generate reports</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filters</h3>
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="status-filter">Payment Status</label>
                    <select id="status-filter">
                        <option value="all">All Statuses</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date-from">Date From</label>
                    <input type="date" id="date-from">
                </div>
                <div class="filter-group">
                    <label for="date-to">Date To</label>
                    <input type="date" id="date-to">
                </div>
                <div class="filter-group">
                    <label for="customer-filter">Customer</label>
                    <select id="customer-filter">
                        <option value="all">All Customers</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-redo"></i> Reset
                </button>
                <button class="export-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="payment-summary">
            <div class="summary-card total">
                <h4>Total Revenue</h4>
                <div class="summary-value" id="total-revenue">$0</div>
                <div class="summary-subtitle" id="total-count">0 transactions</div>
            </div>
            <div class="summary-card paid">
                <h4>Paid</h4>
                <div class="summary-value" id="paid-amount">$0</div>
                <div class="summary-subtitle" id="paid-count">0 payments</div>
            </div>
            <div class="summary-card unpaid">
                <h4>Unpaid</h4>
                <div class="summary-value" id="unpaid-amount">$0</div>
                <div class="summary-subtitle" id="unpaid-count">0 payments</div>
            </div>
            <div class="summary-card pending">
                <h4>Pending</h4>
                <div class="summary-value" id="pending-amount">$0</div>
                <div class="summary-subtitle" id="pending-count">0 payments</div>
            </div>
            <div class="summary-card overdue">
                <h4>Overdue</h4>
                <div class="summary-value" id="overdue-amount">$0</div>
                <div class="summary-subtitle" id="overdue-count">0 payments</div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="payments-table-container">
            <h3><i class="fas fa-table"></i> Payment Transactions</h3>
            <div id="table-content">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading payment data...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        let allPayments = [];
        let filteredPayments = [];

        // Load payment data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPaymentData();
            loadCustomers();
            setupScrollToTop();
        });

        async function loadCustomers() {
            try {
                const response = await fetch('api/customers.php');
                const result = await response.json();
                
                if (result.success && result.customers) {
                    const customerFilter = document.getElementById('customer-filter');
                    result.customers.forEach(customer => {
                        const option = document.createElement('option');
                        option.value = customer.id;
                        option.textContent = customer.full_name || customer.username;
                        customerFilter.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading customers:', error);
            }
        }

        async function loadPaymentData() {
            try {
                const response = await fetch('api/payments.php');
                const result = await response.json();
                
                if (result.success) {
                    allPayments = result.payments || [];
                    filteredPayments = [...allPayments];
                    updateSummary();
                    renderTable();
                } else {
                    showError(result.message || 'Failed to load payment data');
                }
            } catch (error) {
                console.error('Error loading payments:', error);
                showError('Error loading payment data');
            }
        }

        function updateSummary() {
            const summary = {
                total: 0,
                totalCount: 0,
                paid: 0,
                paidCount: 0,
                unpaid: 0,
                unpaidCount: 0,
                pending: 0,
                pendingCount: 0,
                overdue: 0,
                overdueCount: 0
            };

            filteredPayments.forEach(payment => {
                const amount = parseFloat(payment.amount) || 0;
                summary.total += amount;
                summary.totalCount++;

                switch(payment.status.toLowerCase()) {
                    case 'paid':
                        summary.paid += amount;
                        summary.paidCount++;
                        break;
                    case 'unpaid':
                        summary.unpaid += amount;
                        summary.unpaidCount++;
                        break;
                    case 'pending':
                        summary.pending += amount;
                        summary.pendingCount++;
                        break;
                    case 'overdue':
                        summary.overdue += amount;
                        summary.overdueCount++;
                        break;
                }
            });

            document.getElementById('total-revenue').textContent = '$' + summary.total.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('total-count').textContent = summary.totalCount + ' transactions';
            
            document.getElementById('paid-amount').textContent = '$' + summary.paid.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('paid-count').textContent = summary.paidCount + ' payments';
            
            document.getElementById('unpaid-amount').textContent = '$' + summary.unpaid.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('unpaid-count').textContent = summary.unpaidCount + ' payments';
            
            document.getElementById('pending-amount').textContent = '$' + summary.pending.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('pending-count').textContent = summary.pendingCount + ' payments';
            
            document.getElementById('overdue-amount').textContent = '$' + summary.overdue.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('overdue-count').textContent = summary.overdueCount + ' payments';
        }

        function renderTable() {
            const tableContent = document.getElementById('table-content');

            if (filteredPayments.length === 0) {
                tableContent.innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-inbox"></i>
                        <h3>No Payment Data</h3>
                        <p>No payment records match your current filters.</p>
                    </div>
                `;
                return;
            }

            let tableHTML = `
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Invoice #</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            filteredPayments.forEach(payment => {
                const statusClass = payment.status.toLowerCase();
                const formattedAmount = '$' + parseFloat(payment.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
                
                tableHTML += `
                    <tr>
                        <td>#${payment.id}</td>
                        <td>${payment.customer_name}</td>
                        <td>${payment.invoice_number}</td>
                        <td><strong>${formattedAmount}</strong></td>
                        <td>${formatDate(payment.due_date)}</td>
                        <td>${payment.payment_date ? formatDate(payment.payment_date) : '-'}</td>
                        <td><span class="status-badge ${statusClass}">${payment.status}</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn view" onclick="viewPayment(${payment.id})">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="action-btn edit" onclick="editPayment(${payment.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete" onclick="deletePayment(${payment.id}, '${payment.invoice_number}')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tableHTML += `
                    </tbody>
                </table>
            `;

            tableContent.innerHTML = tableHTML;
        }

        function applyFilters() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const customerFilter = document.getElementById('customer-filter').value;

            filteredPayments = allPayments.filter(payment => {
                // Status filter
                if (statusFilter !== 'all' && payment.status.toLowerCase() !== statusFilter) {
                    return false;
                }

                // Date range filter
                if (dateFrom && new Date(payment.due_date) < new Date(dateFrom)) {
                    return false;
                }
                if (dateTo && new Date(payment.due_date) > new Date(dateTo)) {
                    return false;
                }

                // Customer filter (dropdown)
                if (customerFilter !== 'all' && payment.user_id != customerFilter) {
                    return false;
                }

                return true;
            });

            updateSummary();
            renderTable();
        }

        function resetFilters() {
            document.getElementById('status-filter').value = 'all';
            document.getElementById('date-from').value = '';
            document.getElementById('date-to').value = '';
            document.getElementById('customer-filter').value = 'all';
            
            filteredPayments = [...allPayments];
            updateSummary();
            renderTable();
        }

        function exportReport() {
            if (filteredPayments.length === 0) {
                alert('No data to export');
                return;
            }

            let csv = 'ID,Customer,Invoice Number,Amount,Due Date,Payment Date,Status\n';
            
            filteredPayments.forEach(payment => {
                csv += `${payment.id},`;
                csv += `"${payment.customer_name}",`;
                csv += `${payment.invoice_number},`;
                csv += `${payment.amount},`;
                csv += `${payment.due_date},`;
                csv += `${payment.payment_date || 'N/A'},`;
                csv += `${payment.status}\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'payment_report_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function viewPayment(id) {
            const payment = allPayments.find(p => p.id === id);
            if (payment) {
                alert('View Payment Details:\n\n' + 
                      'Customer: ' + payment.customer_name + '\n' +
                      'Invoice: ' + payment.invoice_number + '\n' +
                      'Amount: $' + parseFloat(payment.amount).toFixed(2) + '\n' +
                      'Status: ' + payment.status + '\n' +
                      'Due Date: ' + payment.due_date);
            }
        }

        function editPayment(id) {
            // Find the payment data
            const payment = allPayments.find(p => p.id === id);
            if (!payment) {
                alert('Payment not found');
                return;
            }
            
            // Redirect to Payment Management page with focus on this payment
            const paymentManagementUrl = `payment_management.php?highlight=${id}`;
            
            if (confirm(`Do you want to edit payment ${payment.invoice_number}? This will redirect you to Payment Management.`)) {
                window.location.href = paymentManagementUrl;
            }
        }

        function deletePayment(id, invoiceNumber) {
            if (confirm(`Are you sure you want to delete payment record for Invoice ${invoiceNumber}? This action cannot be undone.`)) {
                // Show loading state
                const deleteBtn = event.target.closest('.action-btn');
                const originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                deleteBtn.disabled = true;

                fetch('api/delete_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ payment_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showSuccessMessage(`Payment record for Invoice ${invoiceNumber} deleted successfully!`);
                        // Reload payments data
                        loadPayments();
                    } else {
                        alert('Error deleting payment: ' + (data.message || 'Unknown error'));
                        // Restore button
                        deleteBtn.innerHTML = originalText;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    alert('Error deleting payment: ' + error.message);
                    // Restore button
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                });
            }
        }

        function showSuccessMessage(message) {
            // Create and show success notification
            const notification = document.createElement('div');
            notification.className = 'success-notification';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #d4edda;
                color: #155724;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1001;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideInRight 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        function showError(message) {
            document.getElementById('table-content').innerHTML = `
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `;
        }

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

        function setupScrollToTop() {
            const scrollBtn = document.getElementById('scrollToTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollBtn.classList.add('show');
                } else {
                    scrollBtn.classList.remove('show');
                }
            });
            
            scrollBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    </script>
</body>
</html>
