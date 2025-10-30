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
    <title>Document Management - Billing Portal</title>
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
                    <a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="user_management.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                    <a href="document_management.php" class="nav-link active"><i class="fas fa-folder-open"></i> Documents</a>
                    <a href="payment_management.php" class="nav-link"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="#" class="nav-link" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="page-title">
            <h2><i class="fas fa-folder-open"></i> Document Management</h2>
            <p>Upload, organize, and manage billing documents and customer files</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number" id="total-documents">0</div>
                <div class="stat-label">Total Documents</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-size">0 MB</div>
                <div class="stat-label">Storage Used</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="documents-today">0</div>
                <div class="stat-label">Uploaded Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="unique-users">0</div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="upload-section">
            <h3><i class="fas fa-cloud-upload-alt"></i> Upload Documents</h3>
            
            <div class="user-selection-warning" id="user-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Please select a customer to assign documents to before uploading files.</span>
            </div>
            
            <div class="upload-area disabled" id="upload-area">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-text">Select a customer first, then drag & drop files here</div>
                <div class="upload-hint">or <strong>click to browse</strong></div>
                <div class="upload-hint">Supported: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max 50MB per file)</div>
                <input type="file" id="file-input" name="documents" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" style="display: none;" disabled>
            </div>
            
            <div class="upload-progress" id="upload-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div id="upload-status">Uploading files...</div>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Assign to Customer (Required):</label>
                <select id="assign-user" class="form-control" required onchange="validateUserSelection()">
                    <option value="">Select Customer to Assign Document</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Category:</label>
                <select id="document-category" class="form-control" onchange="toggleInvoiceFields()" required>
                    <option value="">-- Select Category --</option>
                    <option value="general">General</option>
                    <option value="invoice">Invoice</option>
                    <option value="receipt">Receipt</option>
                    <option value="contract">Contract</option>
                    <option value="report">Report</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <!-- Invoice-specific fields (hidden by default) -->
            <div id="invoice-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Bill Date From:</label>
                    <input type="date" id="bill-date-from" name="bill_date_from" class="form-control" autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Bill Date To:</label>
                    <input type="date" id="bill-date-to" name="bill_date_to" class="form-control" autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Bill Amount ($):</label>
                    <input type="number" id="bill-amount" name="bill_amount" class="form-control" step="0.01" min="0" placeholder="0.00" autocomplete="off">
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="search-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search-input" name="search_documents" placeholder="Search documents by name, type, or user..." autocomplete="off">
                </div>
                <select id="category-filter" name="category_filter" class="filter-select" onchange="filterDocuments()" autocomplete="off">
                    <option value="">All Categories</option>
                    <option value="general">General</option>
                    <option value="invoice">Invoice</option>
                    <option value="receipt">Receipt</option>
                    <option value="contract">Contract</option>
                    <option value="report">Report</option>
                    <option value="other">Other</option>
                </select>
                <select id="user-filter" class="filter-select" onchange="filterDocuments()">
                    <option value="">All Users</option>
                </select>
                <select id="type-filter" class="filter-select" onchange="filterDocuments()">
                    <option value="">All Types</option>
                    <option value="pdf">PDF</option>
                    <option value="doc">Document</option>
                    <option value="excel">Excel</option>
                    <option value="image">Image</option>
                </select>
            </div>
            <div class="actions">
                <div class="view-toggle">
                    <button class="view-btn active" onclick="toggleView('grid')">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn" onclick="toggleView('table')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
                <button class="btn btn-secondary" onclick="exportDocuments()">
                    <i class="fas fa-download"></i> Export List
                </button>
                <button class="btn btn-danger" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i> Bulk Delete
                </button>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="documents-section">
            <div class="documents-header">
                <h3><i class="fas fa-files"></i> All Documents</h3>
                <div>
                    <span id="document-count">0 documents</span>
                </div>
            </div>
            
            <!-- Grid View -->
            <div id="documents-grid" class="documents-grid">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading documents...
                </div>
            </div>
            
            <!-- Table View -->
            <div id="documents-table" class="table-view">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all" name="select_all_documents"></th>
                            <th>Document</th>
                            <th>Category</th>
                            <th>Owner</th>
                            <th>Bill Date</th>
                            <th>Bill Amount</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documents-table-body">
                        <!-- Table content will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Document Details Modal -->
    <div id="documentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Document Details</h3>
                <span class="close" onclick="closeDocumentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="document-details-content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDocumentModal()">Close</button>
                <button type="button" class="btn btn-primary" id="download-document">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>

    <script>
        let allDocuments = [];
        let selectedDocuments = [];
        let currentView = 'grid';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadDocuments();
            loadStats();
            loadUsers();
            setupUpload();
            setupSearch();
        });

        // Validate user selection and enable/disable upload area
        function validateUserSelection() {
            const assignUser = document.getElementById('assign-user');
            const uploadArea = document.getElementById('upload-area');
            const fileInput = document.getElementById('file-input');
            const userWarning = document.getElementById('user-warning');
            const uploadText = uploadArea.querySelector('.upload-text');
            
            if (assignUser.value) {
                // User selected - enable upload
                uploadArea.classList.remove('disabled');
                fileInput.disabled = false;
                userWarning.style.display = 'none';
                uploadText.textContent = 'Drag & Drop Files Here';
            } else {
                // No user selected - disable upload
                uploadArea.classList.add('disabled');
                fileInput.disabled = true;
                userWarning.style.display = 'flex';
                uploadText.textContent = 'Select a customer first, then drag & drop files here';
            }
        }

        // Toggle invoice fields visibility
        function toggleInvoiceFields() {
            const category = document.getElementById('document-category').value;
            const invoiceFields = document.getElementById('invoice-fields');
            
            if (category === 'invoice') {
                invoiceFields.style.display = 'block';
            } else {
                invoiceFields.style.display = 'none';
                // Clear invoice fields when hidden
                document.getElementById('bill-date-from').value = '';
                document.getElementById('bill-date-to').value = '';
                document.getElementById('bill-amount').value = '';
            }
        }

        // Setup file upload
        function setupUpload() {
            const uploadArea = document.getElementById('upload-area');
            const fileInput = document.getElementById('file-input');

            uploadArea.addEventListener('click', () => {
                if (!fileInput.disabled) {
                    fileInput.click();
                } else {
                    showError('Please select a customer to assign documents to before uploading.');
                }
            });
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('dragleave', handleDragLeave);
            uploadArea.addEventListener('drop', handleDrop);
            fileInput.addEventListener('change', handleFileSelect);
        }

        function handleDragOver(e) {
            e.preventDefault();
            const uploadArea = document.getElementById('upload-area');
            if (!uploadArea.classList.contains('disabled')) {
                uploadArea.classList.add('dragover');
            }
        }

        function handleDragLeave(e) {
            e.preventDefault();
            document.getElementById('upload-area').classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            const uploadArea = document.getElementById('upload-area');
            uploadArea.classList.remove('dragover');
            
            if (uploadArea.classList.contains('disabled')) {
                showError('Please select a customer to assign documents to before uploading.');
                return;
            }
            
            const files = e.dataTransfer.files;
            uploadFiles(files);
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            uploadFiles(files);
        }

        // Upload files
        async function uploadFiles(files) {
            if (files.length === 0) return;

            const assignedUserId = document.getElementById('assign-user').value;
            const category = document.getElementById('document-category').value;

            // Validate that a user is selected
            if (!assignedUserId) {
                showError('Please select a customer to assign the document(s) to before uploading.');
                return;
            }

            // Validate that a category is selected
            if (!category) {
                showError('Please select a category for the document(s) before uploading.');
                return;
            }

            const formData = new FormData();

            for (let file of files) {
                if (file.size > 50 * 1024 * 1024) { // 50MB limit
                    showError(`File ${file.name} is too large. Maximum size is 50MB.`);
                    continue;
                }
                formData.append('files[]', file);
            }

            formData.append('assigned_user_id', assignedUserId);
            formData.append('category', category);
            
            // Add bill data if category is invoice
            if (category === 'invoice') {
                const billDateFrom = document.getElementById('bill-date-from').value;
                const billDateTo = document.getElementById('bill-date-to').value;
                const billAmount = document.getElementById('bill-amount').value;
                
                if (billDateFrom) formData.append('bill_date_from', billDateFrom);
                if (billDateTo) formData.append('bill_date_to', billDateTo);
                if (billAmount) formData.append('bill_amount', billAmount);
            }

            const progressDiv = document.getElementById('upload-progress');
            const progressFill = document.getElementById('progress-fill');
            const statusText = document.getElementById('upload-status');

            progressDiv.style.display = 'block';
            statusText.textContent = 'Uploading files...';

            try {
                const xhr = new XMLHttpRequest();
                
                // Upload progress
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressFill.style.width = percent + '%';
                        statusText.textContent = `Uploading... ${Math.round(percent)}%`;
                    }
                });

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            statusText.textContent = 'Upload completed successfully!';
                            statusText.style.color = '#28a745';
                            setTimeout(() => {
                                progressDiv.style.display = 'none';
                                loadDocuments();
                                loadStats();
                                document.getElementById('file-input').value = '';
                            }, 2000);
                        } else {
                            throw new Error(result.message || 'Upload failed');
                        }
                    } else {
                        throw new Error('Upload failed');
                    }
                };

                xhr.onerror = function() {
                    throw new Error('Upload failed');
                };

                xhr.open('POST', 'api/upload_documents.php');
                xhr.send(formData);

            } catch (error) {
                statusText.textContent = 'Upload failed: ' + error.message;
                statusText.style.color = '#dc3545';
                progressDiv.style.display = 'none';
            }
        }

        // Load documents
        async function loadDocuments() {
            try {
                console.log('Fetching documents from API...');
                const response = await fetch('api/documents_admin.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Documents API response:', result);
                console.log('Number of documents:', result.documents ? result.documents.length : 0);

                if (result.success) {
                    allDocuments = result.documents || [];
                    console.log('Loaded documents:', allDocuments);
                    displayDocuments(allDocuments);
                    updateDocumentCount(allDocuments.length);
                } else {
                    console.error('API returned error:', result.message);
                    showError('Failed to load documents: ' + result.message);
                    allDocuments = [];
                    displayDocuments([]);
                }
            } catch (error) {
                console.error('Error loading documents:', error);
                showError('Failed to load documents. Please try again.');
                allDocuments = [];
                displayDocuments([]);
            }
        }

        // Display documents
        function displayDocuments(documents) {
            if (currentView === 'grid') {
                displayDocumentsGrid(documents);
            } else {
                displayDocumentsTable(documents);
            }
        }

        function displayDocumentsGrid(documents) {
            const gridContainer = document.getElementById('documents-grid');

            if (documents.length === 0) {
                gridContainer.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-file-alt"></i>
                        <h4>No Documents Found</h4>
                        <p>No documents match your current filters.</p>
                    </div>
                `;
                return;
            }

            gridContainer.innerHTML = documents.map(doc => `
                <div class="document-card" onclick="viewDocument(${doc.id})">
                    <div class="document-icon ${getFileTypeClass(doc.file_type)}">
                        <i class="fas fa-${getFileIcon(doc.file_type)}"></i>
                    </div>
                    <div class="document-name">${doc.original_name}</div>
                    <div class="document-meta">
                        <div><strong>Category:</strong> ${doc.category || 'General'}</div>
                        <div><strong>Owner:</strong> ${doc.owner_name || 'System'}</div>
                        <div><strong>Size:</strong> ${formatFileSize(doc.file_size)}</div>
                        <div><strong>Uploaded:</strong> ${formatDate(doc.upload_date)}</div>
                    </div>
                    <div class="document-actions">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); downloadDocument(${doc.id}, '${doc.original_name}')">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); editDocument(${doc.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteDocument(${doc.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function displayDocumentsTable(documents) {
            const tableBody = document.getElementById('documents-table-body');

            if (documents.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center">No documents found.</td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = documents.map(doc => `
                <tr>
                    <td><input type="checkbox" class="document-checkbox" name="document_${doc.id}" value="${doc.id}"></td>
                    <td>
                        <div class="file-info">
                            <div class="file-preview ${getFileTypeClass(doc.file_type)}">
                                <i class="fas fa-${getFileIcon(doc.file_type)}"></i>
                            </div>
                            <div class="file-details">
                                <div class="file-name">${doc.original_name}</div>
                                <div class="file-meta">${doc.file_type}</div>
                            </div>
                        </div>
                    </td>
                    <td>${doc.category || 'General'}</td>
                    <td>${doc.owner_name || 'System'}</td>
                    <td>${formatBillDateRange(doc)}</td>
                    <td>${doc.bill_amount ? '<span style="color: #667eea; font-weight: 600;">$' + parseFloat(doc.bill_amount).toFixed(2) + '</span>' : '<span style="color: #999;">Not set</span>'}</td>
                    <td>${formatFileSize(doc.file_size)}</td>
                    <td>${formatDate(doc.upload_date)}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary" onclick="downloadDocument(${doc.id}, '${doc.original_name}')">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="editDocument(${doc.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Load statistics
        async function loadStats() {
            try {
                console.log('Fetching document stats...');
                const response = await fetch('api/document_stats.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Document Stats API response:', result);

                if (result.success) {
                    document.getElementById('total-documents').textContent = result.stats.total_documents || 0;
                    document.getElementById('total-size').textContent = formatFileSize(result.stats.total_size || 0);
                    document.getElementById('documents-today').textContent = result.stats.documents_today || 0;
                    document.getElementById('unique-users').textContent = result.stats.unique_users || 0;
                    console.log('Stats updated successfully');
                } else {
                    console.error('Stats API returned error:', result.message);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load users for assignment
        async function loadUsers() {
            try {
                // Load customers for assignment dropdown
                const customersResponse = await fetch('api/users.php?type=customer');
                const customersResult = await customersResponse.json();
                
                console.log('Customers API Response:', customersResult);
                
                // Load all users for filter dropdown
                const allUsersResponse = await fetch('api/users.php');
                const allUsersResult = await allUsersResponse.json();

                console.log('All Users API Response:', allUsersResult);

                if (customersResult.success && allUsersResult.success) {
                    const assignSelect = document.getElementById('assign-user');
                    const userFilter = document.getElementById('user-filter');
                    
                    console.log('Number of customers found:', customersResult.users ? customersResult.users.length : 0);
                    
                    // Only customers for assignment
                    const customerOptions = customersResult.users.map(user => 
                        `<option value="${user.user_id}">${user.full_name || user.username} (${user.email})</option>`
                    ).join('');
                    
                    // All users for filtering
                    const allUserOptions = allUsersResult.users.map(user => 
                        `<option value="${user.user_id}">${user.full_name || user.username} (${user.email})</option>`
                    ).join('');
                    
                    assignSelect.innerHTML = '<option value="">Select Customer to Assign Document</option>' + customerOptions;
                    userFilter.innerHTML = '<option value="">All Users</option>' + allUserOptions;
                    
                    console.log('Customer dropdown updated with options:', customerOptions);
                    
                    // Show message if no customers found
                    if (customersResult.users.length === 0) {
                        assignSelect.innerHTML = '<option value="">No customers found - Create customers first</option>';
                        console.warn('No customers found in database');
                    }
                    
                    // Initial validation
                    validateUserSelection();
                } else {
                    console.error('API Error - Customers:', customersResult);
                    console.error('API Error - All Users:', allUsersResult);
                    
                    const assignSelect = document.getElementById('assign-user');
                    assignSelect.innerHTML = '<option value="">Error loading customers - Check console</option>';
                }
            } catch (error) {
                console.error('Error loading users:', error);
                const assignSelect = document.getElementById('assign-user');
                assignSelect.innerHTML = '<option value="">Error loading customers</option>';
            }
        }

        // Setup search
        function setupSearch() {
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', function() {
                filterDocuments();
            });
        }

        // Filter documents
        function filterDocuments() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const categoryFilter = document.getElementById('category-filter').value;
            const userFilter = document.getElementById('user-filter').value;
            const typeFilter = document.getElementById('type-filter').value;

            const filteredDocuments = allDocuments.filter(doc => {
                const matchesSearch = !searchTerm || 
                    doc.original_name.toLowerCase().includes(searchTerm) ||
                    (doc.owner_name && doc.owner_name.toLowerCase().includes(searchTerm)) ||
                    doc.file_type.toLowerCase().includes(searchTerm);
                
                const matchesCategory = !categoryFilter || doc.category === categoryFilter;
                const matchesUser = !userFilter || doc.uploaded_by == userFilter;
                const matchesType = !typeFilter || getFileTypeCategory(doc.file_type) === typeFilter;

                return matchesSearch && matchesCategory && matchesUser && matchesType;
            });

            displayDocuments(filteredDocuments);
            updateDocumentCount(filteredDocuments.length);
        }

        // Toggle view
        function toggleView(view) {
            currentView = view;
            const buttons = document.querySelectorAll('.view-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const gridView = document.getElementById('documents-grid');
            const tableView = document.getElementById('documents-table');

            if (view === 'grid') {
                gridView.style.display = 'grid';
                tableView.style.display = 'none';
            } else {
                gridView.style.display = 'none';
                tableView.style.display = 'block';
            }

            displayDocuments(allDocuments);
        }

        // Document actions
        function viewDocument(documentId) {
            const doc = allDocuments.find(d => d.id === documentId);
            if (!doc) return;

            document.getElementById('document-details-content').innerHTML = `
                <div class="form-group">
                    <strong>File Name:</strong> ${doc.original_name}
                </div>
                <div class="form-group">
                    <strong>Category:</strong> ${doc.category || 'General'}
                </div>
                <div class="form-group">
                    <strong>File Type:</strong> ${doc.file_type}
                </div>
                <div class="form-group">
                    <strong>File Size:</strong> ${formatFileSize(doc.file_size)}
                </div>
                <div class="form-group">
                    <strong>Uploaded By:</strong> ${doc.owner_name || 'System'}
                </div>
                <div class="form-group">
                    <strong>Upload Date:</strong> ${formatDate(doc.upload_date)}
                </div>
                <div class="form-group">
                    <strong>Description:</strong> ${doc.description || 'No description provided'}
                </div>
            `;

            document.getElementById('download-document').onclick = () => {
                downloadDocument(doc.id, doc.original_name);
                closeDocumentModal();
            };

            document.getElementById('documentModal').style.display = 'block';
        }

        function closeDocumentModal() {
            document.getElementById('documentModal').style.display = 'none';
        }

        function downloadDocument(documentId, originalName) {
            const link = document.createElement('a');
            link.href = `api/download_document.php?id=${documentId}`;
            link.download = originalName;
            link.click();
        }

        function editDocument(documentId) {
            const document = allDocuments.find(doc => doc.id === documentId);
            if (!document) return;

            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h3><i class="fas fa-edit"></i> Edit Document</h3>
                        <button class="close-btn" onclick="this.closest('.modal-overlay').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Document Name</label>
                            <input type="text" id="edit-doc-name" value="${document.original_name || ''}" readonly class="input-readonly">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select id="edit-category" class="form-control">
                                <option value="Invoice" ${document.category === 'Invoice' ? 'selected' : ''}>Invoice</option>
                                <option value="Receipt" ${document.category === 'Receipt' ? 'selected' : ''}>Receipt</option>
                                <option value="Contract" ${document.category === 'Contract' ? 'selected' : ''}>Contract</option>
                                <option value="Report" ${document.category === 'Report' ? 'selected' : ''}>Report</option>
                                <option value="General" ${document.category === 'General' ? 'selected' : ''}>General</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="edit-description" class="form-control" rows="3">${document.description || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Bill Date From</label>
                            <input type="date" id="edit-bill-date-from" name="edit_bill_date_from" class="form-control" value="${document.bill_date_from || ''}" autocomplete="off">
                            <small class="form-help">Start date for billing period</small>
                        </div>
                        <div class="form-group">
                            <label>Bill Date To (Due Date)</label>
                            <input type="date" id="edit-bill-date-to" name="edit_bill_date_to" class="form-control" value="${document.bill_date_to || document.bill_date || ''}" autocomplete="off">
                            <small class="form-help">End date / Due date - will sync with Payment Management</small>
                        </div>
                        <div class="form-group">
                            <label>Bill Amount ($)</label>
                            <input type="number" id="edit-bill-amount" name="edit_bill_amount" class="form-control" step="0.01" min="0" value="${document.bill_amount || ''}" placeholder="0.00" autocomplete="off">
                            <small class="form-help">This amount will sync with Payment Management</small>
                        </div>
                        <div class="form-group">
                            <label>Assign to Customer</label>
                            <select id="edit-assigned-user" name="edit_assigned_user" class="form-control" autocomplete="off">
                                <option value="">-- Select Customer --</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                        <button class="btn-primary" onclick="saveDocumentEdit(${documentId})">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Load customers for assignment
            loadCustomersForAssignment(document.uploaded_by);
        }

        async function loadCustomersForAssignment(currentUserId) {
            try {
                const response = await fetch('api/customers.php');
                const result = await response.json();

                if (result.success && result.customers) {
                    const select = document.getElementById('edit-assigned-user');
                    result.customers.forEach(customer => {
                        const option = document.createElement('option');
                        option.value = customer.id;
                        option.textContent = `${customer.full_name || customer.username} (${customer.email})`;
                        if (customer.id == currentUserId) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading customers:', error);
            }
        }

        async function saveDocumentEdit(documentId) {
            const category = document.getElementById('edit-category').value;
            const description = document.getElementById('edit-description').value;
            const billDateFrom = document.getElementById('edit-bill-date-from').value;
            const billDateTo = document.getElementById('edit-bill-date-to').value;
            const billAmount = document.getElementById('edit-bill-amount').value;
            const assignedUserId = document.getElementById('edit-assigned-user').value;

            try {
                const response = await fetch('api/documents_admin.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: documentId,
                        category: category,
                        description: description,
                        bill_date_from: billDateFrom || null,
                        bill_date_to: billDateTo || null,
                        bill_amount: billAmount || null,
                        assigned_user_id: assignedUserId || null
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Document updated successfully! Payment data synced.');
                    document.querySelector('.modal-overlay').remove();
                    loadDocuments();
                } else {
                    showError('Failed to update document: ' + result.message);
                }
            } catch (error) {
                console.error('Error updating document:', error);
                showError('Failed to update document. Please try again.');
            }
        }

        async function deleteDocument(documentId) {
            if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('api/documents_admin.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: documentId })
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Document deleted successfully!');
                    loadDocuments();
                    loadStats();
                } else {
                    showError('Failed to delete document: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting document:', error);
                showError('Failed to delete document. Please try again.');
            }
        }

        // Bulk operations
        function bulkDelete() {
            const checkboxes = document.querySelectorAll('.document-checkbox:checked');
            if (checkboxes.length === 0) {
                showError('Please select documents to delete.');
                return;
            }

            if (!confirm(`Are you sure you want to delete ${checkboxes.length} documents? This action cannot be undone.`)) {
                return;
            }

            // Implementation for bulk delete
            alert('Bulk delete feature - to be implemented');
        }

        function exportDocuments() {
            const csvContent = "data:text/csv;charset=utf-8," + 
                "ID,Name,Category,Owner,Type,Size,Upload Date\n" +
                allDocuments.map(doc => 
                    `${doc.id},"${doc.original_name}","${doc.category || 'General'}","${doc.owner_name || 'System'}","${doc.file_type}","${formatFileSize(doc.file_size)}","${formatDate(doc.upload_date)}"`
                ).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "documents_export.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Utility functions
        function getFileIcon(fileType) {
            if (fileType.includes('pdf')) return 'file-pdf';
            if (fileType.includes('word') || fileType.includes('document')) return 'file-word';
            if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'file-excel';
            if (fileType.includes('image')) return 'file-image';
            return 'file-alt';
        }

        function getFileTypeClass(fileType) {
            if (fileType.includes('pdf')) return 'file-type-pdf';
            if (fileType.includes('word') || fileType.includes('document')) return 'file-type-doc';
            if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'file-type-excel';
            if (fileType.includes('image')) return 'file-type-image';
            return 'file-type-default';
        }

        function getFileTypeCategory(fileType) {
            if (fileType.includes('pdf')) return 'pdf';
            if (fileType.includes('word') || fileType.includes('document')) return 'doc';
            if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'excel';
            if (fileType.includes('image')) return 'image';
            return 'other';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        }

        function formatBillDateRange(doc) {
            // For invoices, show date range if available
            if (doc.category === 'invoice' && (doc.bill_date_from || doc.bill_date_to)) {
                const fromDate = doc.bill_date_from ? formatDate(doc.bill_date_from) : '—';
                const toDate = doc.bill_date_to ? formatDate(doc.bill_date_to) : '—';
                return `<span style="color: #667eea;">${fromDate} to ${toDate}</span>`;
            }
            // Fallback to single bill_date if exists (for backwards compatibility)
            else if (doc.bill_date) {
                return formatDate(doc.bill_date);
            }
            return '<span style="color: #999;">Not set</span>';
        }

        function updateDocumentCount(count) {
            document.getElementById('document-count').textContent = `${count} documents`;
        }

        function showSuccess(message) {
            showMessage(message, 'success');
        }

        function showError(message) {
            showMessage(message, 'error');
        }

        function showMessage(message, type) {
            const existingMessage = document.querySelector('.message');
            if (existingMessage) {
                existingMessage.remove();
            }

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
            const modal = document.getElementById('documentModal');
            if (event.target === modal) {
                closeDocumentModal();
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