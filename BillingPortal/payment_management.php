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
    <title>Payment Management - Billing Portal</title>
    <link rel="icon" type="image/png" sizes="32x32" href="logo.png">
    <link rel="shortcut icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" href="logo.png">
    <link rel="stylesheet" href="css/admin-enhanced.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-management-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
            margin-bottom: 30px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .payment-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .payment-table th {
            padding: 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }

        .payment-table tbody tr {
            border-bottom: 1px solid #e0e7ff;
            transition: all 0.2s ease;
        }

        .payment-table tbody tr:hover {
            background: #f8f9ff;
        }

        .payment-table td {
            padding: 15px;
            color: #334155;
            font-size: 14px;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .customer-details h4 {
            margin: 0;
            font-size: 14px;
            color: #334155;
        }

        .customer-details p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }

        .invoice-number {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #667eea;
        }

        .amount {
            font-weight: 700;
            font-size: 16px;
            color: #334155;
        }

        .status-select {
            padding: 8px 12px;
            border: 2px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .status-select.paid {
            background: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }

        .status-select.unpaid {
            background: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }

        .status-select.pending {
            background: #fef3c7;
            color: #92400e;
            border-color: #f59e0b;
        }

        .invoice-input,
        .amount-input,
        .date-input {
            padding: 8px 12px;
            border: 2px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .invoice-input {
            width: 100%;
            max-width: 150px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .amount-input {
            width: 100%;
            max-width: 120px;
            text-align: right;
            font-weight: 600;
        }

        .invoice-input:focus,
        .amount-input:focus,
        .date-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .date-input:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-save {
            padding: 8px 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-reset {
            padding: 8px 16px;
            background: #e5e7eb;
            color: #374151;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background: #d1d5db;
        }

        .btn-delete {
            padding: 8px 16px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-delete:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading-row {
            text-align: center;
            padding: 40px;
        }

        .loading-row i {
            font-size: 48px;
            color: #667eea;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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

        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            border: 2px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn-bulk {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-bulk:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .success-message,
        .error-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .success-message.show,
        .error-message.show {
            display: flex;
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
                    <a href="payment_reports.php" class="nav-link"><i class="fas fa-chart-line"></i> Reports</a>
                    <a href="payment_management.php" class="nav-link active"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="#" class="nav-link" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="page-title">
            <h2><i class="fas fa-credit-card"></i> Payment Management</h2>
            <p>Manage customer payments and update payment statuses</p>
        </div>

        <!-- Success/Error Messages -->
        <div id="success-message" class="success-message">
            <i class="fas fa-check-circle"></i>
            <span id="success-text"></span>
        </div>
        <div id="error-message" class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span id="error-text"></span>
        </div>

        <!-- Search and Filters -->
        <div class="payment-management-container">
            <div class="search-filter">
                <input type="text" id="search-customer" name="search_customer" class="search-input" placeholder="🔍 Search by customer name or email..." autocomplete="off">
                <select id="filter-status" name="filter_status" class="filter-select" autocomplete="off">
                    <option value="all">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="pending">Pending</option>
                </select>
                <button class="btn-bulk" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <button class="btn-bulk" onclick="saveAllChanges()">
                    <i class="fas fa-save"></i> Save All Changes
                </button>
                <button class="btn-bulk" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);" onclick="exportToCSV()">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Payment Table -->
        <div class="payment-management-container">
            <h3><i class="fas fa-table"></i> Customer Payments</h3>
            <div style="overflow-x: auto;">
                <table class="payment-table" id="payment-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Invoice ID</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="payment-tbody">
                        <tr class="loading-row">
                            <td colspan="6">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading payment data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        let paymentsData = [];
        let modifiedPayments = new Set();

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPaymentData();
            setupEventListeners();
            setupScrollToTop();
            
            // Check for highlight parameter
            const urlParams = new URLSearchParams(window.location.search);
            const highlightId = urlParams.get('highlight');
            if (highlightId) {
                setTimeout(() => highlightPayment(highlightId), 1000); // Wait for data to load
            }
        });

        async function loadPaymentData() {
            try {
                const response = await fetch('api/payments.php');
                const result = await response.json();
                
                if (result.success) {
                    paymentsData = result.payments || [];
                    renderPaymentTable();
                } else {
                    showError(result.message || 'Failed to load payment data');
                    showNoData();
                }
            } catch (error) {
                console.error('Error loading payments:', error);
                showError('Error loading payment data');
                showNoData();
            }
        }

        function renderPaymentTable() {
            const tbody = document.getElementById('payment-tbody');
            
            if (paymentsData.length === 0) {
                showNoData();
                return;
            }

            // Apply filters
            const searchTerm = document.getElementById('search-customer').value.toLowerCase();
            const statusFilter = document.getElementById('filter-status').value;

            const filteredData = paymentsData.filter(payment => {
                const matchesSearch = !searchTerm || 
                    payment.customer_name.toLowerCase().includes(searchTerm) ||
                    payment.email.toLowerCase().includes(searchTerm);
                
                const matchesStatus = statusFilter === 'all' || 
                    payment.status.toLowerCase() === statusFilter;

                return matchesSearch && matchesStatus;
            });

            if (filteredData.length === 0) {
                showNoData('No payments match your filters');
                return;
            }

            let html = '';
            filteredData.forEach(payment => {
                const initials = payment.customer_name.split(' ').map(n => n[0]).join('').substring(0, 2);
                const statusClass = payment.status.toLowerCase();
                const paymentDate = payment.payment_date || '';
                const isDisabled = payment.status.toLowerCase() !== 'paid';
                const disabledAttr = isDisabled ? 'disabled' : '';
                const dateStyle = isDisabled ? 'background: #f1f5f9; cursor: not-allowed;' : '';
                
                html += `
                    <tr data-payment-id="${payment.id}">
                        <td>
                            <div class="customer-info">
                                <div class="customer-avatar">${initials}</div>
                                <div class="customer-details">
                                    <h4>${payment.customer_name}</h4>
                                    <p>${payment.email}</p>
                                </div>
                            </div>
                        </td>
                        <td><span class="invoice-number">
                            <input type="text" 
                                   id="invoice-${payment.id}"
                                   name="invoice_${payment.id}"
                                   class="invoice-input" 
                                   data-payment-id="${payment.id}"
                                   value="${payment.invoice_number}"
                                   onchange="handleInvoiceChange(${payment.id}, this.value)"
                                   placeholder="INV-001"
                                   autocomplete="off">
                        </span></td>
                        <td><span class="amount">
                            <input type="number" 
                                   id="amount-${payment.id}"
                                   name="amount_${payment.id}"
                                   class="amount-input" 
                                   data-payment-id="${payment.id}"
                                   value="${parseFloat(payment.amount).toFixed(2)}"
                                   step="0.01"
                                   min="0"
                                   onchange="handleAmountChange(${payment.id}, this.value)"
                                   placeholder="0.00"
                                   autocomplete="off">
                        </span></td>
                        <td>
                            <select id="status-${payment.id}"
                                    name="status_${payment.id}"
                                    class="status-select ${statusClass}" 
                                    data-payment-id="${payment.id}" 
                                    onchange="handleStatusChange(${payment.id}, this.value)"
                                    autocomplete="off">
                                <option value="paid" ${payment.status === 'paid' ? 'selected' : ''}>✓ Paid</option>
                                <option value="unpaid" ${payment.status === 'unpaid' ? 'selected' : ''}>✗ Unpaid</option>
                                <option value="pending" ${payment.status === 'pending' ? 'selected' : ''}>⏳ Pending</option>
                            </select>
                        </td>
                        <td>
                            <input type="date" 
                                   id="date-${payment.id}"
                                   name="date_${payment.id}"
                                   class="date-input" 
                                   data-payment-id="${payment.id}"
                                   value="${paymentDate}"
                                   ${disabledAttr}
                                   style="${dateStyle}"
                                   onchange="handleDateChange(${payment.id}, this.value)"
                                   autocomplete="off">
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-save" onclick="savePayment(${payment.id})">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button class="btn-reset" onclick="resetPayment(${payment.id})">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button class="btn-delete" onclick="deletePaymentRecord(${payment.id}, '${payment.invoice_number}')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        function handleInvoiceChange(paymentId, newInvoice) {
            const payment = paymentsData.find(p => p.id === paymentId);
            if (payment) {
                payment.invoice_number = newInvoice;
                modifiedPayments.add(paymentId);
            }
        }

        function handleAmountChange(paymentId, newAmount) {
            const payment = paymentsData.find(p => p.id === paymentId);
            if (payment) {
                payment.amount = parseFloat(newAmount) || 0;
                modifiedPayments.add(paymentId);
            }
        }

        function handleStatusChange(paymentId, newStatus) {
            const payment = paymentsData.find(p => p.id === paymentId);
            if (payment) {
                payment.status = newStatus;
                modifiedPayments.add(paymentId);
                
                // Update select styling
                const select = document.querySelector(`select[data-payment-id="${paymentId}"]`);
                if (select) {
                    select.className = `status-select ${newStatus.toLowerCase()}`;
                }
                
                // Enable/disable date input
                const dateInput = document.querySelector(`input.date-input[data-payment-id="${paymentId}"]`);
                if (dateInput) {
                    if (newStatus === 'paid') {
                        dateInput.disabled = false;
                        dateInput.style.background = '';
                        dateInput.style.cursor = '';
                        if (!dateInput.value) {
                            dateInput.value = new Date().toISOString().split('T')[0];
                            payment.payment_date = dateInput.value;
                        }
                    } else {
                        dateInput.disabled = true;
                        dateInput.style.background = '#f1f5f9';
                        dateInput.style.cursor = 'not-allowed';
                        dateInput.value = '';
                        payment.payment_date = null;
                    }
                } else {
                    console.error('Date input not found for payment ID:', paymentId);
                }
            }
        }

        function handleDateChange(paymentId, newDate) {
            const payment = paymentsData.find(p => p.id === paymentId);
            if (payment) {
                payment.payment_date = newDate;
                modifiedPayments.add(paymentId);
            }
        }

        async function savePayment(paymentId) {
            const payment = paymentsData.find(p => p.id === paymentId);
            if (!payment) return;

            try {
                const response = await fetch('api/payments/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        invoice_number: payment.invoice_number,
                        amount: payment.amount,
                        status: payment.status,
                        payment_date: payment.payment_date
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    modifiedPayments.delete(paymentId);
                    showSuccess('Payment updated successfully');
                } else {
                    showError(result.message || 'Failed to update payment');
                }
            } catch (error) {
                console.error('Error saving payment:', error);
                showError('Error saving payment');
            }
        }

        async function saveAllChanges() {
            if (modifiedPayments.size === 0) {
                showError('No changes to save');
                return;
            }

            const updates = Array.from(modifiedPayments).map(id => {
                const payment = paymentsData.find(p => p.id === id);
                return {
                    payment_id: id,
                    invoice_number: payment.invoice_number,
                    amount: payment.amount,
                    status: payment.status,
                    payment_date: payment.payment_date
                };
            });

            try {
                const response = await fetch('api/payments/bulk-update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ updates })
                });

                const result = await response.json();
                
                if (result.success) {
                    modifiedPayments.clear();
                    showSuccess(`Successfully updated ${updates.length} payment(s)`);
                    await loadPaymentData();
                } else {
                    showError(result.message || 'Failed to update payments');
                }
            } catch (error) {
                console.error('Error saving payments:', error);
                showError('Error saving payments');
            }
        }

        function resetPayment(paymentId) {
            loadPaymentData();
            modifiedPayments.delete(paymentId);
        }

        function deletePaymentRecord(paymentId, invoiceNumber) {
            if (confirm(`Are you sure you want to delete payment record for Invoice ${invoiceNumber}? This action cannot be undone.`)) {
                // Show loading state
                const deleteBtn = event.target.closest('.btn-delete');
                const originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                deleteBtn.disabled = true;

                fetch('api/delete_payment_simple_clean.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ payment_id: paymentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showSuccess(`Payment record for Invoice ${invoiceNumber} deleted successfully!`);
                        // Remove from local data and refresh table
                        paymentsData = paymentsData.filter(p => p.id !== paymentId);
                        modifiedPayments.delete(paymentId);
                        renderPaymentTable();
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

        function refreshData() {
            loadPaymentData();
            modifiedPayments.clear();
        }

        function setupEventListeners() {
            document.getElementById('search-customer').addEventListener('input', renderPaymentTable);
            document.getElementById('filter-status').addEventListener('change', renderPaymentTable);
        }

        function showNoData(message = 'No payment data available') {
            const tbody = document.getElementById('payment-tbody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <h3>${message}</h3>
                            <p>Payment records will appear here once available.</p>
                        </div>
                    </td>
                </tr>
            `;
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('success-message');
            const successText = document.getElementById('success-text');
            successText.textContent = message;
            successDiv.classList.add('show');
            
            setTimeout(() => {
                successDiv.classList.remove('show');
            }, 5000);
        }

        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            errorText.textContent = message;
            errorDiv.classList.add('show');
            
            setTimeout(() => {
                errorDiv.classList.remove('show');
            }, 5000);
        }

        function exportToCSV() {
            if (paymentsData.length === 0) {
                showError('No data to export');
                return;
            }

            let csv = 'Customer Name,Email,Invoice ID,Amount,Status,Payment Date\n';
            
            paymentsData.forEach(payment => {
                csv += `"${payment.customer_name}",`;
                csv += `"${payment.email}",`;
                csv += `${payment.invoice_number},`;
                csv += `${payment.amount},`;
                csv += `${payment.status},`;
                csv += `${payment.payment_date || 'N/A'}\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'payments_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
            
            showSuccess('CSV exported successfully');
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

        function highlightPayment(paymentId) {
            const row = document.querySelector(`tr[data-payment-id="${paymentId}"]`);
            if (row) {
                // Scroll to the row
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Add highlight effect
                row.style.backgroundColor = '#fff3cd';
                row.style.border = '2px solid #ffc107';
                row.style.transition = 'all 0.3s ease';
                
                // Show a message
                showSuccess(`Payment record highlighted for editing`);
                
                // Remove highlight after 5 seconds
                setTimeout(() => {
                    row.style.backgroundColor = '';
                    row.style.border = '';
                }, 5000);
            }
        }
    </script>
</body>
</html>
