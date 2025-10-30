// Admin dashboard functionality - Fixed version
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin dashboard loaded');
    
    // Initialize dashboard
    checkAuth();
    initializeDashboard();
    setupNavigation();
    setupEventListeners();
    
    // Load data
    loadCustomers();
    loadDocuments();
    updateStats();
});

// Authentication check
function checkAuth() {
    // You can implement session check here
    console.log('Checking authentication...');
}

// Initialize dashboard
function initializeDashboard() {
    console.log('Initializing dashboard...');
    
    // Set dashboard as default active section
    const dashboardSection = document.getElementById('dashboard');
    if (dashboardSection) {
        dashboardSection.classList.add('active');
    }
    
    // Set dashboard nav as active
    const dashboardNav = document.querySelector('[data-section="dashboard"]');
    if (dashboardNav) {
        dashboardNav.parentElement.classList.add('active');
    }
}

// Load customers from API
async function loadCustomers() {
    try {
        console.log('Loading customers...');
        
        const response = await fetch('api/users.php?type=customer', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        console.log('Response status:', response.status);
        
        // Get response text even if not OK to see error details
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        if (!response.ok) {
            console.error('Error response body:', responseText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
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
            
            if (result.debug_info) {
                console.error('Debug info:', result.debug_info);
            }
            
            // Show empty state
            renderCustomers([]);
        }
    } catch (error) {
        console.error('Error loading customers:', error);
        showMessage('Error loading customers: ' + error.message, 'error');
        renderCustomers([]);
    }
}

// Render customers table
function renderCustomers(customers) {
    console.log('Rendering customers:', customers);
    const tbody = document.querySelector('#customers-tbody');
    
    if (!tbody) {
        console.error('Customer table tbody not found with ID: customers-tbody');
        console.log('Available tbody elements:', document.querySelectorAll('tbody'));
        return;
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
        const id = customer.user_id || customer.id || index;
        const fullName = customer.full_name || customer.username || 'N/A';
        const email = customer.email || 'N/A';
        const username = customer.username || 'N/A';
        const status = customer.account_status || customer.status || 'active';
        const createdAt = customer.created_at ? 
            new Date(customer.created_at).toLocaleDateString() : 'N/A';
        
        return `
            <tr data-customer-id="${id}">
                <td>${id}</td>
                <td>${escapeHtml(fullName)}</td>
                <td>${escapeHtml(email)}</td>
                <td>${escapeHtml(username)}</td>
                <td>${createdAt}</td>
                <td><span class="status-badge ${status}">${status}</span></td>
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
    
    // Update stats if elements exist
    const totalElement = document.querySelector('.stat-number');
    if (totalElement) {
        totalElement.textContent = totalCustomers;
    }
    
    console.log(`Stats updated: ${totalCustomers} total, ${activeCustomers} active`);
}

// Navigation setup
function setupNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    const contentSections = document.querySelectorAll('.content-section');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('data-section');
            
            if (!sectionId) return;
            
            // Update active navigation
            document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
            this.parentElement.classList.add('active');
            
            // Show corresponding section
            contentSections.forEach(section => section.classList.remove('active'));
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
                
                // Reload customers when customers section is shown
                if (sectionId === 'customers') {
                    loadCustomers();
                }
            }
        });
    });
}

// Event listeners setup
function setupEventListeners() {
    // Add customer button
    const addCustomerBtn = document.getElementById('add-customer-btn');
    if (addCustomerBtn) {
        addCustomerBtn.addEventListener('click', function() {
            showAddCustomerModal();
        });
    }
    
    // Refresh customers button (if exists)
    const refreshBtn = document.querySelector('[data-action="refresh-customers"]');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadCustomers();
        });
    }
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showMessage(message, type = 'info') {
    console.log(`Message (${type}):`, message);
    
    // Create or update message display
    let messageDiv = document.getElementById('message-display');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.id = 'message-display';
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 4px;
            z-index: 1000;
            max-width: 300px;
        `;
        document.body.appendChild(messageDiv);
    }
    
    messageDiv.className = `alert alert-${type}`;
    messageDiv.textContent = message;
    messageDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.style.display = 'none';
        }
    }, 5000);
}

// Placeholder functions for customer management
function editCustomer(id) {
    console.log('Edit customer:', id);
    showMessage('Edit customer functionality not implemented yet', 'info');
}

async function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer?')) {
        return;
    }
    
    try {
        console.log('Deleting customer with ID:', id, 'Type:', typeof id);
        
        // First attempt - check for documents
        const requestBody = { user_id: parseInt(id) };
        console.log('Request body:', JSON.stringify(requestBody));
        
        const response = await fetch('api/users.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody)
        });
        
        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Delete response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON:', responseText);
            showMessage('Server error: Invalid response', 'error');
            return;
        }
        
        if (result.warning) {
            // User has documents, ask for confirmation
            if (confirm(result.message)) {
                // Force delete
                const forceResponse = await fetch('api/users.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: id, force: true })
                });
                
                const forceResult = await forceResponse.json();
                
                if (forceResult.success) {
                    showMessage(forceResult.message || 'Customer deleted successfully', 'success');
                    loadCustomers(); // Reload the list
                    updateStats(); // Update statistics
                } else {
                    showMessage(forceResult.error || 'Failed to delete customer', 'error');
                }
            }
        } else if (result.success) {
            showMessage(result.message || 'Customer deleted successfully', 'success');
            loadCustomers(); // Reload the list
            updateStats(); // Update statistics
        } else {
            showMessage(result.error || 'Failed to delete customer', 'error');
        }
        
    } catch (error) {
        console.error('Error deleting customer:', error);
        showMessage('Error: ' + error.message, 'error');
    }
}

function showAddCustomerModal() {
    console.log('Show add customer modal');
    showMessage('Add customer modal not implemented yet', 'info');
}

// Load documents (placeholder)
function loadDocuments() {
    console.log('Loading documents...');
    // Implement document loading
}

// Update stats (placeholder)
function updateStats() {
    console.log('Updating stats...');
    // Implement stats updating
}