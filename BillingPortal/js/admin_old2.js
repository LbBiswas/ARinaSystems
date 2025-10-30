// Admin dashboard functionality - PHP backend integration
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    checkAuth();
    
    // Initialize dashboard
    initializeDashboard();
    loadCustomers();
    loadDocuments();
    updateStats();

    // Navigation functionality
    setupNavigation();
    
    // Event listeners
    setupEventListeners();
});

// Load customers from API - Updated version with better error handling
async function loadCustomers() {
    try {
        console.log('Loading customers...');
        
        const response = await fetch('api/users.php?type=customers', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        console.log('Customers API response:', result);
        
        if (result.success) {
            console.log('Found customers:', result.users.length);
            renderCustomers(result.users || []);
            updateCustomerStats(result.users || []);
        } else {
            console.error('Failed to load customers:', result.message);
            showMessage('Failed to load customers: ' + (result.message || 'Unknown error'), 'error');
            
            // Show debug info if available
            if (result.debug_info) {
                console.error('Debug info:', result.debug_info);
            }
        }
    } catch (error) {
        console.error('Error loading customers:', error);
        showMessage('Error loading customers: ' + error.message, 'error');
        
        // Show empty state
        renderCustomers([]);
    }
}

// Render customers - Updated version with better error handling
function renderCustomers(customers) {
    console.log('Rendering customers:', customers);
    let tbody = document.querySelector('#customers-tbody');
    
    if (!tbody) {
        console.warn('Main customers tbody not found - checking for alternatives');
        
        // Try alternative selectors
        const alternativeSelectors = [
            '#customers-table tbody',
            '.customers-table tbody',
            '#customers .table tbody',
            '[id*="customer"] tbody'
        ];
        
        for (const selector of alternativeSelectors) {
            tbody = document.querySelector(selector);
            if (tbody) {
                console.log('Found table with selector:', selector);
                break;
            }
        }
        
        if (!tbody) {
            console.error('Could not find customers table. Available elements:');
            console.log('Tables:', document.querySelectorAll('table'));
            console.log('TBodies:', document.querySelectorAll('tbody'));
            console.log('Customer section:', document.querySelector('#customers'));
            return;
        }
    }
    
    if (!customers || customers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #666;">
                    <i class="fas fa-users" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                    No customers found
                    <br><small>Add customers using the "Add New Customer" button</small>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = customers.map((customer, index) => {
        // Ensure we have required fields
        const id = customer.id || index;
        const fullName = customer.full_name || customer.username || 'N/A';
        const email = customer.email || 'N/A';
        const username = customer.username || 'N/A';
        const status = customer.status || 'active';
        const createdAt = customer.created_at ? 
            new Date(customer.created_at).toLocaleDateString() : 'N/A';
        
        return `
            <tr data-customer-id="${id}">
                <td>${id}</td>
                <td>${escapeHtml(fullName)}</td>
                <td>${escapeHtml(email)}</td>
                <td>${escapeHtml(username)}</td>
                <td><span class="status-badge ${status}">${status}</span></td>
                <td>${createdAt}</td>
                <td class="actions">
                    <button class="btn btn-sm btn-primary" onclick="editCustomer(${id})" title="Edit Customer">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${id})" title="Delete Customer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    console.log('Successfully rendered', customers.length, 'customers');
}

// Update customer statistics
function updateCustomerStats(customers) {
    if (!customers) return;
    
    const totalCustomers = customers.length;
    const activeCustomers = customers.filter(c => c.status === 'active').length;
    const inactiveCustomers = customers.filter(c => c.status === 'inactive').length;
    
    // Update stats display
    const statElements = {
        'total-customers': totalCustomers,
        'active-customers': activeCustomers,
        'inactive-customers': inactiveCustomers
    };
    
    Object.entries(statElements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    });
}

// Utility function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Navigation setup
function setupNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    const contentSections = document.querySelectorAll('.content-section');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('data-section');
            
            // Update active navigation
            document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
            this.parentElement.classList.add('active');
            
            // Show corresponding section
            contentSections.forEach(section => section.classList.remove('active'));
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
}

// Event listeners setup
function setupEventListeners() {

    // Modal functionality
    const addCustomerBtn = document.getElementById('add-customer-btn');
    const addCustomerModal = document.getElementById('add-customer-modal');
    const uploadDocumentBtn = document.getElementById('upload-document-btn');
    const uploadDocumentModal = document.getElementById('upload-document-modal');
    const closeButtons = document.querySelectorAll('.close, #cancel-customer, #cancel-upload');

    addCustomerBtn.addEventListener('click', () => {
        addCustomerModal.style.display = 'block';
    });

    uploadDocumentBtn.addEventListener('click', () => {
        uploadDocumentModal.style.display = 'block';
        populateCustomerSelect();
    });

    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            addCustomerModal.style.display = 'none';
            uploadDocumentModal.style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === addCustomerModal) {
            addCustomerModal.style.display = 'none';
        }
        if (e.target === uploadDocumentModal) {
            uploadDocumentModal.style.display = 'none';
        }
    });

    // Add customer form submission
    document.getElementById('add-customer-form').addEventListener('submit', function(e) {
        e.preventDefault();
        handleAddCustomer(this);
    });

    // Upload document form submission
    document.getElementById('upload-document-form').addEventListener('submit', function(e) {
        e.preventDefault();
        handleUploadDocument(this);
    });

    // Logout functionality
    const logoutBtns = document.querySelectorAll('.logout-btn');
    logoutBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            handleLogout();
        });
    });

    // Sidebar toggle for mobile
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });
    }
}

// Authentication check
function checkAuth() {
    const userType = sessionStorage.getItem('user_type');
    const userId = sessionStorage.getItem('user_id');
    
    if (!userId || userType !== 'admin') {
        window.location.href = 'login.php';
        return;
    }
}

// Handle logout
async function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            const response = await fetch('api/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();
            
            if (result.success) {
                sessionStorage.clear();
                localStorage.clear();
                showMessage('Logged out successfully', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1000);
            }
        } catch (error) {
            console.error('Logout error:', error);
            // Redirect anyway
            window.location.href = 'login.php';
        }
    }
}

// Load customers from API
async function loadCustomers() {
    try {
        const response = await fetch('api/users.php?type=customers');
        const result = await response.json();
        
        if (result.success) {
            renderCustomers(result.users);
        } else {
            console.error('Failed to load customers:', result.message);
        }
    } catch (error) {
        console.error('Error loading customers:', error);
    }
}

// Load documents from API
async function loadDocuments() {
    try {
        const response = await fetch('api/documents.php');
        const result = await response.json();
        
        if (result.success) {
            renderDocuments(result.documents);
        } else {
            console.error('Failed to load documents:', result.message);
        }
    } catch (error) {
        console.error('Error loading documents:', error);
    }
}

// Handle add customer
async function handleAddCustomer(form) {
    const formData = new FormData(form);
    const customerData = {
        username: formData.get('username'),
        email: formData.get('email'),
        full_name: formData.get('full_name'),
        password: formData.get('password'),
        phone: formData.get('phone'),
        user_type: 'customer',
        status: 'active'
    };

    try {
        const response = await fetch('api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(customerData)
        });

        const result = await response.json();
        
        if (result.success) {
            showMessage('Customer added successfully!', 'success');
            form.reset();
            document.getElementById('add-customer-modal').style.display = 'none';
            loadCustomers(); // Reload customers
            updateStats();
        } else {
            showMessage(result.message || 'Failed to add customer', 'error');
        }
    } catch (error) {
        console.error('Error adding customer:', error);
        showMessage('Error adding customer', 'error');
    }
}

// Handle upload document
async function handleUploadDocument(form) {
    const formData = new FormData(form);
    
    if (!formData.get('file').name) {
        showMessage('Please select a file!', 'error');
        return;
    }

    // Add title and customer_id if provided
    const title = formData.get('title') || formData.get('file').name;
    const customerId = formData.get('customer_id');
    
    formData.set('title', title);
    if (customerId) {
        formData.set('customer_id', customerId);
    }

    try {
        const response = await fetch('api/upload.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            showMessage('Document uploaded successfully!', 'success');
            form.reset();
            document.getElementById('upload-document-modal').style.display = 'none';
            loadDocuments(); // Reload documents
            updateStats();
        } else {
            showMessage(result.message || 'Failed to upload document', 'error');
        }
    } catch (error) {
        console.error('Error uploading document:', error);
        showMessage('Error uploading document', 'error');
    }
}

// Render customers
function renderCustomers(customers) {
    const tbody = document.querySelector('#customers-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td>${customer.id}</td>
            <td>${customer.full_name}</td>
            <td>${customer.email}</td>
            <td>${customer.username}</td>
            <td><span class="status-badge ${customer.status}">${customer.status}</span></td>
            <td>${new Date(customer.created_at).toLocaleDateString()}</td>
            <td class="actions">
                <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${customer.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Render documents
function renderDocuments(documents) {
    const grid = document.getElementById('documents-grid');
    if (!grid) return;
    
    if (documents.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #666; padding: 40px;">No documents uploaded yet.</div>';
        return;
    }
    
    grid.innerHTML = documents.map(doc => {
        const fileIcon = doc.file_icon || getFileIcon(doc.file_type || doc.file_name);
        const customerDisplay = doc.customer_display || doc.customer_name || 'Unknown Customer';
        const uploaderDisplay = doc.uploader_display || doc.uploader_name || 'Unknown';
        const uploadDate = doc.upload_date_formatted || new Date(doc.upload_date).toLocaleDateString();
        const fileSize = doc.file_size_formatted || formatFileSize(doc.file_size);
        const status = doc.status || 'pending';
        
        return `
            <div class="document-card">
                <div class="document-icon">
                    <i class="fas ${fileIcon}"></i>
                </div>
                <div class="document-info">
                    <h4>${escapeHtml(doc.title)}</h4>
                    <p><strong>Customer:</strong> ${escapeHtml(customerDisplay)}</p>
                    <p><strong>Uploaded by:</strong> ${escapeHtml(uploaderDisplay)}</p>
                    <p><strong>Date:</strong> ${uploadDate}</p>
                    <p><strong>Size:</strong> ${fileSize}</p>
                    <p><strong>Status:</strong> <span class="status-badge ${status}">${status}</span></p>
                </div>
                <div class="document-actions">
                    <button class="btn btn-sm btn-primary" onclick="downloadDocument(${doc.id})" title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="editDocument(${doc.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Populate customer select dropdown
async function populateCustomerSelect() {
    try {
        const response = await fetch('api/users.php?type=customers');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('document-customer');
            select.innerHTML = '<option value="">Select Customer</option>' +
                result.users.map(customer => 
                    `<option value="${customer.id}">${customer.full_name} (${customer.username})</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading customers for select:', error);
    }
}

// Edit customer
async function editCustomer(customerId) {
    try {
        // Get customer data
        const response = await fetch(`api/users.php?type=customers`, {
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
        const result = await response.json();
        
        if (result.success) {
            const customer = result.users.find(u => u.id == customerId);
            
            if (!customer) {
                showMessage('Customer not found', 'error');
                return;
            }
            
            // Create edit modal
            const modalHtml = `
                <div class="modal" id="edit-customer-modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Edit Customer</h3>
                            <span class="close" onclick="closeEditCustomerModal()">&times;</span>
                        </div>
                        <form id="edit-customer-form">
                            <input type="hidden" id="edit-customer-id" value="${customer.id}">
                            <div class="form-group">
                                <label for="edit-customer-name">Full Name</label>
                                <input type="text" id="edit-customer-name" value="${escapeHtml(customer.full_name)}" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-customer-email">Email</label>
                                <input type="email" id="edit-customer-email" value="${escapeHtml(customer.email)}" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-customer-username">Username</label>
                                <input type="text" id="edit-customer-username" value="${escapeHtml(customer.username)}" required readonly>
                                <small>Username cannot be changed</small>
                            </div>
                            <div class="form-group">
                                <label for="edit-customer-phone">Phone</label>
                                <input type="text" id="edit-customer-phone" value="${escapeHtml(customer.phone || '')}">
                            </div>
                            <div class="form-group">
                                <label for="edit-customer-status">Status</label>
                                <select id="edit-customer-status">
                                    <option value="active" ${customer.status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="inactive" ${customer.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit-customer-password">New Password (leave blank to keep current)</label>
                                <input type="password" id="edit-customer-password" placeholder="Enter new password">
                                <small>Minimum 6 characters</small>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn btn-secondary" onclick="closeEditCustomerModal()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Customer</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Handle form submission
            document.getElementById('edit-customer-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                await handleUpdateCustomer();
            });
        }
    } catch (error) {
        console.error('Error loading customer for edit:', error);
        showMessage('Error loading customer details', 'error');
    }
}

// Handle customer update
async function handleUpdateCustomer() {
    const customerId = document.getElementById('edit-customer-id').value;
    const full_name = document.getElementById('edit-customer-name').value.trim();
    const email = document.getElementById('edit-customer-email').value.trim();
    const username = document.getElementById('edit-customer-username').value.trim();
    const phone = document.getElementById('edit-customer-phone').value.trim();
    const status = document.getElementById('edit-customer-status').value;
    const password = document.getElementById('edit-customer-password').value.trim();
    
    if (!full_name || !email) {
        showMessage('Name and email are required', 'error');
        return;
    }
    
    const customerData = {
        id: customerId,
        username: username,
        email: email,
        full_name: full_name,
        phone: phone,
        user_type: 'customer',
        status: status
    };
    
    if (password) {
        if (password.length < 6) {
            showMessage('Password must be at least 6 characters long', 'error');
            return;
        }
        customerData.password = password;
    }
    
    try {
        const response = await fetch('api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            },
            body: JSON.stringify(customerData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Customer updated successfully!', 'success');
            closeEditCustomerModal();
            loadCustomers();
            updateStats();
        } else {
            showMessage(result.message || 'Failed to update customer', 'error');
        }
    } catch (error) {
        console.error('Error updating customer:', error);
        showMessage('Error updating customer', 'error');
    }
}

// Close edit customer modal
function closeEditCustomerModal() {
    const modal = document.getElementById('edit-customer-modal');
    if (modal) {
        modal.remove();
    }
}

// Delete customer
async function deleteCustomer(customerId) {
    if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
        try {
            const response = await fetch('api/users.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: customerId })
            });

            const result = await response.json();
            
            if (result.success) {
                showMessage('Customer deleted successfully!', 'success');
                loadCustomers(); // Reload customers
                updateStats();
            } else {
                showMessage(result.message || 'Failed to delete customer', 'error');
            }
        } catch (error) {
            console.error('Error deleting customer:', error);
            showMessage('Error deleting customer', 'error');
        }
    }
}

// Delete document
async function deleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document?')) {
        try {
            const response = await fetch('api/documents.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ document_id: documentId })
            });

            const result = await response.json();
            
            if (result.success) {
                showMessage('Document deleted successfully!', 'success');
                loadDocuments(); // Reload documents
                updateStats();
            } else {
                showMessage(result.message || 'Failed to delete document', 'error');
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            showMessage('Error deleting document', 'error');
        }
    }
}

// Edit document
async function editDocument(documentId) {
    try {
        // First, get the document details
        const response = await fetch(`api/documents.php?id=${documentId}`);
        const result = await response.json();
        
        if (result.success && result.document) {
            const doc = result.document;
            
            // Create edit modal HTML
            const modalHtml = `
                <div class="modal" id="editDocumentModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Edit Document</h3>
                            <span class="close" onclick="closeEditDocumentModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="editDocumentForm">
                                <div class="form-group">
                                    <label for="edit-document-title">Document Title:</label>
                                    <input type="text" id="edit-document-title" value="${escapeHtml(doc.title)}" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit-document-customer">Assign to Customer:</label>
                                    <select id="edit-document-customer" required>
                                        <option value="">Select Customer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit-document-status">Status:</label>
                                    <select id="edit-document-status">
                                        <option value="pending" ${doc.status === 'pending' ? 'selected' : ''}>Pending</option>
                                        <option value="approved" ${doc.status === 'approved' ? 'selected' : ''}>Approved</option>
                                        <option value="rejected" ${doc.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" onclick="closeEditDocumentModal()">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Document</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Populate customer dropdown
            await populateEditCustomerSelect(doc.customer_id);
            
            // Show modal
            document.getElementById('editDocumentModal').style.display = 'block';
            
            // Handle form submission
            document.getElementById('editDocumentForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                await handleUpdateDocument(documentId);
            });
            
        } else {
            showMessage('Failed to load document details', 'error');
        }
    } catch (error) {
        console.error('Error loading document for edit:', error);
        showMessage('Error loading document', 'error');
    }
}

// Handle document update
async function handleUpdateDocument(documentId) {
    const title = document.getElementById('edit-document-title').value.trim();
    const customerId = document.getElementById('edit-document-customer').value;
    const status = document.getElementById('edit-document-status').value;
    
    if (!title || !customerId) {
        showMessage('Please fill in all required fields', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/documents.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                document_id: documentId,
                title: title,
                customer_id: customerId,
                status: status
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Document updated successfully!', 'success');
            closeEditDocumentModal();
            loadDocuments();
        } else {
            showMessage(result.message || 'Failed to update document', 'error');
        }
    } catch (error) {
        console.error('Error updating document:', error);
        showMessage('Error updating document', 'error');
    }
}

// Populate customer select for edit modal
async function populateEditCustomerSelect(selectedCustomerId) {
    try {
        const response = await fetch('api/users.php?type=customers');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('edit-document-customer');
            select.innerHTML = '<option value="">Select Customer</option>' +
                result.users.map(customer => 
                    `<option value="${customer.id}" ${customer.id == selectedCustomerId ? 'selected' : ''}>${customer.full_name} (${customer.username})</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading customers for edit select:', error);
    }
}

// Close edit document modal
function closeEditDocumentModal() {
    const modal = document.getElementById('editDocumentModal');
    if (modal) {
        modal.remove();
    }
}

// Download document
async function downloadDocument(documentId) {
    try {
        const response = await fetch(`api/download.php?id=${documentId}`);
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = ''; // Server should set proper filename
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } else {
            showMessage('Failed to download document', 'error');
        }
    } catch (error) {
        console.error('Error downloading document:', error);
        showMessage('Error downloading document', 'error');
    }
}

// Update statistics
async function updateStats() {
    try {
        const [customersResponse, documentsResponse] = await Promise.all([
            fetch('api/users.php?type=customers'),
            fetch('api/documents.php')
        ]);

        const [customersResult, documentsResult] = await Promise.all([
            customersResponse.json(),
            documentsResponse.json()
        ]);

        if (customersResult.success && documentsResult.success) {
            const totalCustomers = customersResult.users.length;
            const totalDocuments = documentsResult.documents.length;
            
            // Calculate recent uploads (last 7 days)
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            const recentUploads = documentsResult.documents.filter(doc => 
                new Date(doc.upload_date) >= weekAgo
            ).length;

            // Update UI
            const statNumbers = document.querySelectorAll('.stat-number');
            if (statNumbers[0]) statNumbers[0].textContent = totalCustomers;
            if (statNumbers[1]) statNumbers[1].textContent = totalDocuments;
            if (statNumbers[2]) statNumbers[2].textContent = recentUploads;
        }
    } catch (error) {
        console.error('Error updating stats:', error);
    }
}

// Initialize dashboard
function initializeDashboard() {
    // Set welcome message
    const now = new Date();
    const hour = now.getHours();
    let greeting;
    
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 18) greeting = 'Good afternoon';
    else greeting = 'Good evening';
    
    const username = sessionStorage.getItem('username') || 'Admin';
    const userInfoSpan = document.querySelector('.user-info span');
    if (userInfoSpan) {
        userInfoSpan.textContent = `${greeting}, ${username}`;
    }
}

// Utility functions
function getFileIcon(fileName) {
    const extension = fileName.split('.').pop().toLowerCase();
    
    switch (extension) {
        case 'pdf':
            return '<i class="fas fa-file-pdf"></i>';
        case 'doc':
        case 'docx':
            return '<i class="fas fa-file-word"></i>';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return '<i class="fas fa-file-image"></i>';
        default:
            return '<i class="fas fa-file"></i>';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
    } else if (type === 'info') {
        messageDiv.style.background = '#17a2b8';
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

// Add CSS for status badge and mobile sidebar
const style = document.createElement('style');
style.textContent = `
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .sidebar.mobile-open {
        transform: translateX(0) !important;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);