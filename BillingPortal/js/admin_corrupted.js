// Admin Panel JavaScript - Complete Implementation// Admin dashboard functionality - PHP backend integration

let currentUsers = [];document.addEventListener('DOMContentLoaded', function() {

let editingUserId = null;    // Check authentication

    checkAuth();

// Initialize on page load    

document.addEventListener('DOMContentLoaded', () => {    // Initialize dashboard

    initializeAdminDashboard();    initializeDashboard();

    loadCustomersForUpload();    loadCustomers();

});    loadDocuments();

    updateStats();

// Initialize admin dashboard

async function initializeAdminDashboard() {    // Navigation functionality

    await loadStats();    setupNavigation();

    await loadUsers();    

    setupEventListeners();    // Event listeners

}    setupEventListeners();

});

// Setup all event listeners

function setupEventListeners() {// Load customers from API - Updated version with better error handling

    // Searchasync function loadCustomers() {

    const searchInput = document.getElementById('searchUsers');    try {

    if (searchInput) {        console.log('Loading customers...');

        searchInput.addEventListener('input', debounce(filterUsers, 300));        

    }        const response = await fetch('api/users.php?type=customers', {

                method: 'GET',

    // Filters            headers: {

    const filterType = document.getElementById('filterType');                'Content-Type': 'application/json',

    if (filterType) {            }

        filterType.addEventListener('change', filterUsers);        });

    }        

            console.log('Response status:', response.status);

    const filterStatus = document.getElementById('filterStatus');        

    if (filterStatus) {        if (!response.ok) {

        filterStatus.addEventListener('change', filterUsers);            throw new Error(`HTTP error! status: ${response.status}`);

    }        }

            

    // Add user button        const responseText = await response.text();

    const addUserBtn = document.getElementById('addUserBtn');        console.log('Raw response:', responseText);

    if (addUserBtn) {        

        addUserBtn.addEventListener('click', () => {        let result;

            openUserModal();        try {

        });            result = JSON.parse(responseText);

    }        } catch (jsonError) {

                console.error('JSON parse error:', jsonError);

    // Close modal            console.error('Response text:', responseText);

    const closeModal = document.querySelector('.close-modal');            throw new Error('Invalid JSON response from server');

    if (closeModal) {        }

        closeModal.addEventListener('click', () => {        

            closeUserModal();        console.log('Customers API response:', result);

        });        

    }        if (result.success) {

                console.log('Found customers:', result.users.length);

    // Cancel button            renderCustomers(result.users || []);

    const cancelBtn = document.getElementById('cancelBtn');            updateCustomerStats(result.users || []);

    if (cancelBtn) {        } else {

        cancelBtn.addEventListener('click', () => {            console.error('Failed to load customers:', result.message);

            closeUserModal();            showMessage('Failed to load customers: ' + (result.message || 'Unknown error'), 'error');

        });            

    }            // Show debug info if available

                if (result.debug_info) {

    // User form submit                console.error('Debug info:', result.debug_info);

    const userForm = document.getElementById('userForm');            }

    if (userForm) {        }

        userForm.addEventListener('submit', handleSaveUser);    } catch (error) {

    }        console.error('Error loading customers:', error);

            showMessage('Error loading customers: ' + error.message, 'error');

    // Upload form        

    const uploadForm = document.getElementById('uploadForm');        // Show empty state

    if (uploadForm) {        renderCustomers([]);

        uploadForm.addEventListener('submit', handleUploadDocument);    }

    }}

    

    // Refresh buttons// Render customers - Updated version with better error handling

    document.querySelectorAll('.refresh-btn').forEach(btn => {function renderCustomers(customers) {

        btn.addEventListener('click', () => {    console.log('Rendering customers:', customers);

            loadStats();    let tbody = document.querySelector('#customers-tbody');

            loadUsers();    

        });    if (!tbody) {

    });        console.warn('Main customers tbody not found - checking for alternatives');

            

    // Navigation (if using sections)        // Try alternative selectors

    setupNavigation();        const alternativeSelectors = [

}            '#customers-table tbody',

            '.customers-table tbody',

// Setup navigation between sections            '#customers .table tbody',

function setupNavigation() {            '[id*="customer"] tbody'

    const navLinks = document.querySelectorAll('.sidebar-menu a[data-section]');        ];

    navLinks.forEach(link => {        

        link.addEventListener('click', (e) => {        for (const selector of alternativeSelectors) {

            e.preventDefault();            tbody = document.querySelector(selector);

            const sectionId = link.dataset.section;            if (tbody) {

            showSection(sectionId);                console.log('Found table with selector:', selector);

                            break;

            // Update active state            }

            navLinks.forEach(l => l.parentElement.classList.remove('active'));        }

            link.parentElement.classList.add('active');        

        });        if (!tbody) {

    });            console.error('Could not find customers table. Available elements:');

}            console.log('Tables:', document.querySelectorAll('table'));

            console.log('TBodies:', document.querySelectorAll('tbody'));

// Show specific section            console.log('Customer section:', document.querySelector('#customers'));

function showSection(sectionId) {            return;

    document.querySelectorAll('.content-section').forEach(section => {        }

        section.classList.remove('active');    }

        section.style.display = 'none';    

    });    if (!customers || customers.length === 0) {

            tbody.innerHTML = `

    const targetSection = document.getElementById(sectionId);            <tr>

    if (targetSection) {                <td colspan="7" style="text-align: center; padding: 20px; color: #666;">

        targetSection.classList.add('active');                    <i class="fas fa-users" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>

        targetSection.style.display = 'block';                    No customers found

    }                    <br><small>Add customers using the "Add New Customer" button</small>

}                </td>

            </tr>

// Load dashboard statistics        `;

async function loadStats() {        return;

    try {    }

        const response = await fetch('api/admin_stats.php');    

        const data = await response.json();    tbody.innerHTML = customers.map((customer, index) => {

                // Ensure we have required fields

        if (data.success) {        const id = customer.id || index;

            // Update stats cards        const fullName = customer.full_name || customer.username || 'N/A';

            updateElement('totalUsers', data.stats.total_users);        const email = customer.email || 'N/A';

            updateElement('activeUsers', data.stats.active_users);        const username = customer.username || 'N/A';

            updateElement('totalCustomers', data.stats.total_customers);        const status = customer.status || 'active';

            updateElement('totalDocuments', data.stats.total_documents);        const createdAt = customer.created_at ? 

                        new Date(customer.created_at).toLocaleDateString() : 'N/A';

            // Update recent lists        

            displayRecentUsers(data.recent_users || []);        return `

            displayRecentActivity(data.recent_activity || []);            <tr data-customer-id="${id}">

        }                <td>${id}</td>

    } catch (error) {                <td>${escapeHtml(fullName)}</td>

        console.error('Error loading stats:', error);                <td>${escapeHtml(email)}</td>

        showNotification('Failed to load statistics', 'error');                <td>${escapeHtml(username)}</td>

    }                <td><span class="status-badge ${status}">${status}</span></td>

}                <td>${createdAt}</td>

                <td class="actions">

// Display recent users                    <button class="btn btn-sm btn-primary" onclick="editCustomer(${id})" title="Edit Customer">

function displayRecentUsers(users) {                        <i class="fas fa-edit"></i>

    const container = document.getElementById('recentUsersList');                    </button>

    if (!container) return;                    <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${id})" title="Delete Customer">

                            <i class="fas fa-trash"></i>

    if (users.length === 0) {                    </button>

        container.innerHTML = '<p>No recent users</p>';                </td>

        return;            </tr>

    }        `;

        }).join('');

    container.innerHTML = users.map(user => `    

        <div class="recent-item" style="padding: 10px 0; border-bottom: 1px solid #eee;">    console.log('Successfully rendered', customers.length, 'customers');

            <div>}

                <strong>${escapeHtml(user.full_name || user.username)}</strong>

                <span class="badge ${user.user_type === 'admin' ? 'badge-danger' : 'badge-primary'}" style="margin-left: 8px;">${user.user_type}</span>// Update customer statistics

            </div>function updateCustomerStats(customers) {

            <small style="color: #666;">${formatDate(user.created_at)}</small>    if (!customers) return;

        </div>    

    `).join('');    const totalCustomers = customers.length;

}    const activeCustomers = customers.filter(c => c.status === 'active').length;

    const inactiveCustomers = customers.filter(c => c.status === 'inactive').length;

// Display recent activity    

function displayRecentActivity(activities) {    // Update stats display

    const container = document.getElementById('recentActivityList');    const statElements = {

    if (!container) return;        'total-customers': totalCustomers,

            'active-customers': activeCustomers,

    if (activities.length === 0) {        'inactive-customers': inactiveCustomers

        container.innerHTML = '<p>No recent activity</p>';    };

        return;    

    }    Object.entries(statElements).forEach(([id, value]) => {

            const element = document.getElementById(id);

    container.innerHTML = activities.map(activity => `        if (element) {

        <div class="activity-item" style="padding: 10px 0; border-bottom: 1px solid #eee;">            element.textContent = value;

            <div>        }

                <strong>${escapeHtml(activity.username || 'System')}</strong> - ${escapeHtml(activity.action)}    });

                ${activity.details ? `<br><small>${escapeHtml(activity.details)}</small>` : ''}}

            </div>

            <small style="color: #666;">${formatDate(activity.created_at)}</small>// Utility function to escape HTML

        </div>function escapeHtml(text) {

    `).join('');    if (!text) return '';

}    const div = document.createElement('div');

    div.textContent = text;

// Load all users    return div.innerHTML;

async function loadUsers(filters = {}) {}

    try {

        const params = new URLSearchParams(filters);// Navigation setup

        const response = await fetch(`api/users.php?${params}`);function setupNavigation() {

        const data = await response.json();    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');

            const contentSections = document.querySelectorAll('.content-section');

        if (data.success) {

            currentUsers = data.users;    sidebarLinks.forEach(link => {

            displayUsers(currentUsers);        link.addEventListener('click', function(e) {

        } else {            e.preventDefault();

            showNotification(data.error || 'Failed to load users', 'error');            const sectionId = this.getAttribute('data-section');

        }            

    } catch (error) {            // Update active navigation

        console.error('Error loading users:', error);            document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));

        showNotification('Failed to load users', 'error');            this.parentElement.classList.add('active');

    }            

}            // Show corresponding section

            contentSections.forEach(section => section.classList.remove('active'));

// Display users in table            const targetSection = document.getElementById(sectionId);

function displayUsers(users) {            if (targetSection) {

    const tbody = document.getElementById('usersTableBody');                targetSection.classList.add('active');

    if (!tbody) return;            }

            });

    if (users.length === 0) {    });

        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No users found</td></tr>';}

        return;

    }// Event listeners setup

    function setupEventListeners() {

    tbody.innerHTML = users.map(user => `

        <tr>    // Modal functionality

            <td>${user.user_id}</td>    const addCustomerBtn = document.getElementById('add-customer-btn');

            <td>${escapeHtml(user.username)}</td>    const addCustomerModal = document.getElementById('add-customer-modal');

            <td>${escapeHtml(user.full_name || '-')}</td>    const uploadDocumentBtn = document.getElementById('upload-document-btn');

            <td>${escapeHtml(user.email)}</td>    const uploadDocumentModal = document.getElementById('upload-document-modal');

            <td><span class="badge ${user.user_type === 'admin' ? 'badge-danger' : 'badge-primary'}">${user.user_type}</span></td>    const closeButtons = document.querySelectorAll('.close, #cancel-customer, #cancel-upload');

            <td><span class="badge ${user.account_status === 'active' ? 'badge-success' : 'badge-secondary'}">${user.account_status}</span></td>

            <td>    addCustomerBtn.addEventListener('click', () => {

                <button class="btn btn-sm btn-primary" onclick="editUser(${user.user_id})" title="Edit">        addCustomerModal.style.display = 'block';

                    <i class="fas fa-edit"></i>    });

                </button>

                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.user_id}, '${escapeHtml(user.username)}')" title="Delete">    uploadDocumentBtn.addEventListener('click', () => {

                    <i class="fas fa-trash"></i>        uploadDocumentModal.style.display = 'block';

                </button>        populateCustomerSelect();

            </td>    });

        </tr>

    `).join('');    closeButtons.forEach(btn => {

}        btn.addEventListener('click', () => {

            addCustomerModal.style.display = 'none';

// Filter users            uploadDocumentModal.style.display = 'none';

function filterUsers() {        });

    const search = document.getElementById('searchUsers')?.value.toLowerCase() || '';    });

    const type = document.getElementById('filterType')?.value || 'all';

    const status = document.getElementById('filterStatus')?.value || 'all';    // Close modal when clicking outside

        window.addEventListener('click', (e) => {

    let filtered = currentUsers;        if (e.target === addCustomerModal) {

                addCustomerModal.style.display = 'none';

    // Search filter        }

    if (search) {        if (e.target === uploadDocumentModal) {

        filtered = filtered.filter(user =>             uploadDocumentModal.style.display = 'none';

            user.username.toLowerCase().includes(search) ||        }

            (user.full_name && user.full_name.toLowerCase().includes(search)) ||    });

            user.email.toLowerCase().includes(search)

        );    // Add customer form submission

    }    document.getElementById('add-customer-form').addEventListener('submit', function(e) {

            e.preventDefault();

    // Type filter        handleAddCustomer(this);

    if (type !== 'all') {    });

        filtered = filtered.filter(user => user.user_type === type);

    }    // Upload document form submission

        document.getElementById('upload-document-form').addEventListener('submit', function(e) {

    // Status filter        e.preventDefault();

    if (status !== 'all') {        handleUploadDocument(this);

        filtered = filtered.filter(user => user.account_status === status);    });

    }

        // Logout functionality

    displayUsers(filtered);    const logoutBtns = document.querySelectorAll('.logout-btn');

}    logoutBtns.forEach(btn => {

        btn.addEventListener('click', function(e) {

// Open user modal for adding new user            e.preventDefault();

function openUserModal() {            handleLogout();

    editingUserId = null;        });

    const modalTitle = document.getElementById('modalTitle');    });

    if (modalTitle) modalTitle.textContent = 'Add New User';

        // Sidebar toggle for mobile

    const userForm = document.getElementById('userForm');    const sidebarToggle = document.querySelector('.sidebar-toggle');

    if (userForm) userForm.reset();    const sidebar = document.querySelector('.sidebar');

        

    const usernameInput = document.getElementById('username');    if (sidebarToggle) {

    if (usernameInput) usernameInput.disabled = false;        sidebarToggle.addEventListener('click', () => {

                sidebar.classList.toggle('mobile-open');

    const passwordField = document.getElementById('passwordField');        });

    if (passwordField) passwordField.style.display = 'block';    }

    }

    const passwordRequired = document.getElementById('passwordRequired');

    if (passwordRequired) passwordRequired.style.display = 'inline';// Authentication check

    function checkAuth() {

    const userModal = document.getElementById('userModal');    const userType = sessionStorage.getItem('user_type');

    if (userModal) userModal.style.display = 'block';    const userId = sessionStorage.getItem('user_id');

}    

    if (!userId || userType !== 'admin') {

// Edit user        window.location.href = 'login.php';

function editUser(userId) {        return;

    const user = currentUsers.find(u => u.user_id === userId);    }

    if (!user) return;}

    

    editingUserId = userId;// Handle logout

    async function handleLogout() {

    const modalTitle = document.getElementById('modalTitle');    if (confirm('Are you sure you want to logout?')) {

    if (modalTitle) modalTitle.textContent = 'Edit User';        try {

                const response = await fetch('api/logout.php', {

    const passwordField = document.getElementById('passwordField');                method: 'POST',

    if (passwordField) passwordField.style.display = 'none';                headers: {

                        'Content-Type': 'application/json',

    const passwordRequired = document.getElementById('passwordRequired');                }

    if (passwordRequired) passwordRequired.style.display = 'none';            });

    

    // Populate form            const result = await response.json();

    updateInputValue('username', user.username, true);            

    updateInputValue('email', user.email);            if (result.success) {

    updateInputValue('full_name', user.full_name || '');                sessionStorage.clear();

    updateInputValue('phone', user.phone || '');                localStorage.clear();

    updateInputValue('address', user.address || '');                showMessage('Logged out successfully', 'success');

    updateInputValue('user_type', user.user_type);                setTimeout(() => {

    updateInputValue('account_status', user.account_status);                    window.location.href = 'login.php';

                    }, 1000);

    const userModal = document.getElementById('userModal');            }

    if (userModal) userModal.style.display = 'block';        } catch (error) {

}            console.error('Logout error:', error);

            // Redirect anyway

// Handle save user (create or update)            window.location.href = 'login.php';

async function handleSaveUser(e) {        }

    e.preventDefault();    }

    }

    const formData = {

        username: document.getElementById('username')?.value,// Load customers from API

        email: document.getElementById('email')?.value,async function loadCustomers() {

        full_name: document.getElementById('full_name')?.value,    try {

        phone: document.getElementById('phone')?.value,        const response = await fetch('api/users.php?type=customers');

        address: document.getElementById('address')?.value,        const result = await response.json();

        user_type: document.getElementById('user_type')?.value,        

        account_status: document.getElementById('account_status')?.value        if (result.success) {

    };            renderCustomers(result.users);

            } else {

    const password = document.getElementById('password')?.value;            console.error('Failed to load customers:', result.message);

    if (!editingUserId && !password) {        }

        showNotification('Password is required for new users', 'error');    } catch (error) {

        return;        console.error('Error loading customers:', error);

    }    }

    if (password) {}

        formData.password = password;

    }// Load documents from API

    async function loadDocuments() {

    try {    try {

        let response;        const response = await fetch('api/documents.php');

        if (editingUserId) {        const result = await response.json();

            formData.user_id = editingUserId;        

            response = await fetch('api/users.php', {        if (result.success) {

                method: 'PUT',            renderDocuments(result.documents);

                headers: {'Content-Type': 'application/json'},        } else {

                body: JSON.stringify(formData)            console.error('Failed to load documents:', result.message);

            });        }

        } else {    } catch (error) {

            response = await fetch('api/users.php', {        console.error('Error loading documents:', error);

                method: 'POST',    }

                headers: {'Content-Type': 'application/json'},}

                body: JSON.stringify(formData)

            });// Handle add customer

        }async function handleAddCustomer(form) {

            const formData = new FormData(form);

        const data = await response.json();    const customerData = {

                username: formData.get('username'),

        if (data.success) {        email: formData.get('email'),

            showNotification(data.message, 'success');        full_name: formData.get('full_name'),

            closeUserModal();        password: formData.get('password'),

            await loadUsers();        phone: formData.get('phone'),

            await loadStats();        user_type: 'customer',

        } else {        status: 'active'

            showNotification(data.error || 'Failed to save user', 'error');    };

        }

    } catch (error) {    try {

        console.error('Error saving user:', error);        const response = await fetch('api/users.php', {

        showNotification('Failed to save user', 'error');            method: 'POST',

    }            headers: {

}                'Content-Type': 'application/json',

            },

// Delete user            body: JSON.stringify(customerData)

async function deleteUser(userId, username) {        });

    if (!confirm(`Are you sure you want to delete user "${username}"?`)) {

        return;        const result = await response.json();

    }        

            if (result.success) {

    try {            showMessage('Customer added successfully!', 'success');

        const response = await fetch('api/users.php', {            form.reset();

            method: 'DELETE',            document.getElementById('add-customer-modal').style.display = 'none';

            headers: {'Content-Type': 'application/json'},            loadCustomers(); // Reload customers

            body: JSON.stringify({ user_id: userId })            updateStats();

        });        } else {

                    showMessage(result.message || 'Failed to add customer', 'error');

        const data = await response.json();        }

            } catch (error) {

        if (data.warning) {        console.error('Error adding customer:', error);

            if (confirm(data.message)) {        showMessage('Error adding customer', 'error');

                const forceResponse = await fetch('api/users.php', {    }

                    method: 'DELETE',}

                    headers: {'Content-Type': 'application/json'},

                    body: JSON.stringify({ user_id: userId, force: true })// Handle upload document

                });async function handleUploadDocument(form) {

                    const formData = new FormData(form);

                const forceData = await forceResponse.json();    

                if (forceData.success) {    if (!formData.get('file').name) {

                    showNotification(forceData.message, 'success');        showMessage('Please select a file!', 'error');

                    await loadUsers();        return;

                    await loadStats();    }

                } else {

                    showNotification(forceData.error || 'Failed to delete user', 'error');    // Add title and customer_id if provided

                }    const title = formData.get('title') || formData.get('file').name;

            }    const customerId = formData.get('customer_id');

        } else if (data.success) {    

            showNotification(data.message, 'success');    formData.set('title', title);

            await loadUsers();    if (customerId) {

            await loadStats();        formData.set('customer_id', customerId);

        } else {    }

            showNotification(data.error || 'Failed to delete user', 'error');

        }    try {

    } catch (error) {        const response = await fetch('api/upload.php', {

        console.error('Error deleting user:', error);            method: 'POST',

        showNotification('Failed to delete user', 'error');            body: formData

    }        });

}

        const result = await response.json();

// Close user modal        

function closeUserModal() {        if (result.success) {

    const userModal = document.getElementById('userModal');            showMessage('Document uploaded successfully!', 'success');

    if (userModal) userModal.style.display = 'none';            form.reset();

                document.getElementById('upload-document-modal').style.display = 'none';

    const userForm = document.getElementById('userForm');            loadDocuments(); // Reload documents

    if (userForm) userForm.reset();            updateStats();

            } else {

    const usernameInput = document.getElementById('username');            showMessage(result.message || 'Failed to upload document', 'error');

    if (usernameInput) usernameInput.disabled = false;        }

        } catch (error) {

    editingUserId = null;        console.error('Error uploading document:', error);

}        showMessage('Error uploading document', 'error');

    }

// Load customers for upload dropdown}

async function loadCustomersForUpload() {

    try {// Render customers

        const response = await fetch('api/users.php?type=customer&status=active');function renderCustomers(customers) {

        const data = await response.json();    const tbody = document.querySelector('#customers-table tbody');

            if (!tbody) return;

        if (data.success) {    

            const select = document.getElementById('upload_user_id');    tbody.innerHTML = customers.map(customer => `

            if (select) {        <tr>

                select.innerHTML = '<option value="">Select Customer</option>' +            <td>${customer.id}</td>

                    data.users.map(user =>             <td>${customer.full_name}</td>

                        `<option value="${user.user_id}">${escapeHtml(user.full_name || user.username)} (${escapeHtml(user.email)})</option>`            <td>${customer.email}</td>

                    ).join('');            <td>${customer.username}</td>

            }            <td><span class="status-badge ${customer.status}">${customer.status}</span></td>

        }            <td>${new Date(customer.created_at).toLocaleDateString()}</td>

    } catch (error) {            <td class="actions">

        console.error('Error loading customers:', error);                <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.id})">

    }                    <i class="fas fa-edit"></i>

}                </button>

                <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${customer.id})">

// Handle document upload                    <i class="fas fa-trash"></i>

async function handleUploadDocument(e) {                </button>

    e.preventDefault();            </td>

            </tr>

    const formData = new FormData(e.target);    `).join('');

    }

    try {

        const response = await fetch('api/upload.php', {// Render documents

            method: 'POST',function renderDocuments(documents) {

            body: formData    const grid = document.getElementById('documents-grid');

        });    if (!grid) return;

            

        const data = await response.json();    if (documents.length === 0) {

                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #666; padding: 40px;">No documents uploaded yet.</div>';

        if (data.success) {        return;

            showNotification('Document uploaded successfully', 'success');    }

            e.target.reset();    

            await loadStats();    grid.innerHTML = documents.map(doc => {

        } else {        const fileIcon = doc.file_icon || getFileIcon(doc.file_type || doc.file_name);

            showNotification(data.error || 'Failed to upload document', 'error');        const customerDisplay = doc.customer_display || doc.customer_name || 'Unknown Customer';

        }        const uploaderDisplay = doc.uploader_display || doc.uploader_name || 'Unknown';

    } catch (error) {        const uploadDate = doc.upload_date_formatted || new Date(doc.upload_date).toLocaleDateString();

        console.error('Error uploading document:', error);        const fileSize = doc.file_size_formatted || formatFileSize(doc.file_size);

        showNotification('Failed to upload document', 'error');        const status = doc.status || 'pending';

    }        

}        return `

            <div class="document-card">

// Handle logout                <div class="document-icon">

async function handleLogout() {                    <i class="fas ${fileIcon}"></i>

    try {                </div>

        const response = await fetch('api/logout.php', { method: 'POST' });                <div class="document-info">

        const data = await response.json();                    <h4>${escapeHtml(doc.title)}</h4>

                            <p><strong>Customer:</strong> ${escapeHtml(customerDisplay)}</p>

        if (data.success) {                    <p><strong>Uploaded by:</strong> ${escapeHtml(uploaderDisplay)}</p>

            window.location.href = 'login.html';                    <p><strong>Date:</strong> ${uploadDate}</p>

        }                    <p><strong>Size:</strong> ${fileSize}</p>

    } catch (error) {                    <p><strong>Status:</strong> <span class="status-badge ${status}">${status}</span></p>

        console.error('Logout error:', error);                </div>

        window.location.href = 'login.html';                <div class="document-actions">

    }                    <button class="btn btn-sm btn-primary" onclick="downloadDocument(${doc.id})" title="Download">

}                        <i class="fas fa-download"></i>

                    </button>

// Utility functions                    <button class="btn btn-sm btn-secondary" onclick="editDocument(${doc.id})" title="Edit">

function updateElement(id, value) {                        <i class="fas fa-edit"></i>

    const element = document.getElementById(id);                    </button>

    if (element) element.textContent = value;                    <button class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.id})" title="Delete">

}                        <i class="fas fa-trash"></i>

                    </button>

function updateInputValue(id, value, disabled = false) {                </div>

    const input = document.getElementById(id);            </div>

    if (input) {        `;

        input.value = value;    }).join('');

        input.disabled = disabled;}

    }

}// Populate customer select dropdown

async function populateCustomerSelect() {

function escapeHtml(text) {    try {

    if (!text) return '';        const response = await fetch('api/users.php?type=customers');

    const div = document.createElement('div');        const result = await response.json();

    div.textContent = text;        

    return div.innerHTML;        if (result.success) {

}            const select = document.getElementById('document-customer');

            select.innerHTML = '<option value="">Select Customer</option>' +

function formatDate(dateString) {                result.users.map(customer => 

    if (!dateString) return '';                    `<option value="${customer.id}">${customer.full_name} (${customer.username})</option>`

    const date = new Date(dateString);                ).join('');

    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();        }

}    } catch (error) {

        console.error('Error loading customers for select:', error);

function debounce(func, wait) {    }

    let timeout;}

    return function executedFunction(...args) {

        const later = () => {// Edit customer

            clearTimeout(timeout);async function editCustomer(customerId) {

            func(...args);    try {

        };        // Get customer data

        clearTimeout(timeout);        const response = await fetch(`api/users.php?type=customers`, {

        timeout = setTimeout(later, wait);            headers: {

    };                'Cache-Control': 'no-cache',

}                'Pragma': 'no-cache'

            }

function showNotification(message, type = 'info') {        });

    const notification = document.createElement('div');        const result = await response.json();

    notification.className = `notification notification-${type}`;        

    notification.textContent = message;        if (result.success) {

    notification.style.cssText = `            const customer = result.users.find(u => u.id == customerId);

        position: fixed;            

        top: 20px;            if (!customer) {

        right: 20px;                showMessage('Customer not found', 'error');

        padding: 15px 20px;                return;

        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};            }

        color: white;            

        border-radius: 4px;            // Create edit modal

        box-shadow: 0 2px 5px rgba(0,0,0,0.2);            const modalHtml = `

        z-index: 10000;                <div class="modal" id="edit-customer-modal" style="display: block;">

        animation: slideIn 0.3s ease-out;                    <div class="modal-content">

    `;                        <div class="modal-header">

                                <h3>Edit Customer</h3>

    document.body.appendChild(notification);                            <span class="close" onclick="closeEditCustomerModal()">&times;</span>

                            </div>

    setTimeout(() => {                        <form id="edit-customer-form">

        notification.style.animation = 'slideOut 0.3s ease-out';                            <input type="hidden" id="edit-customer-id" value="${customer.id}">

        setTimeout(() => notification.remove(), 300);                            <div class="form-group">

    }, 3000);                                <label for="edit-customer-name">Full Name</label>

}                                <input type="text" id="edit-customer-name" value="${escapeHtml(customer.full_name)}" required>

                            </div>

// Add CSS animations for notifications                            <div class="form-group">

const style = document.createElement('style');                                <label for="edit-customer-email">Email</label>

style.textContent = `                                <input type="email" id="edit-customer-email" value="${escapeHtml(customer.email)}" required>

    @keyframes slideIn {                            </div>

        from {                            <div class="form-group">

            transform: translateX(100%);                                <label for="edit-customer-username">Username</label>

            opacity: 0;                                <input type="text" id="edit-customer-username" value="${escapeHtml(customer.username)}" required readonly>

        }                                <small>Username cannot be changed</small>

        to {                            </div>

            transform: translateX(0);                            <div class="form-group">

            opacity: 1;                                <label for="edit-customer-phone">Phone</label>

        }                                <input type="text" id="edit-customer-phone" value="${escapeHtml(customer.phone || '')}">

    }                            </div>

    @keyframes slideOut {                            <div class="form-group">

        from {                                <label for="edit-customer-status">Status</label>

            transform: translateX(0);                                <select id="edit-customer-status">

            opacity: 1;                                    <option value="active" ${customer.status === 'active' ? 'selected' : ''}>Active</option>

        }                                    <option value="inactive" ${customer.status === 'inactive' ? 'selected' : ''}>Inactive</option>

        to {                                </select>

            transform: translateX(100%);                            </div>

            opacity: 0;                            <div class="form-group">

        }                                <label for="edit-customer-password">New Password (leave blank to keep current)</label>

    }                                <input type="password" id="edit-customer-password" placeholder="Enter new password">

`;                                <small>Minimum 6 characters</small>

document.head.appendChild(style);                            </div>
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