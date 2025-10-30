// Customer dashboard functionality - PHP backend integration
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    checkAuth();
    
    // Initialize dashboard
    initializeCustomerDashboard();
    loadCustomerDocuments();
    updateCustomerStats();

    // Navigation functionality
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
            document.getElementById(sectionId).classList.add('active');
        });
    });

    // File upload functionality
    const fileInput = document.getElementById('file-input');
    const uploadArea = document.getElementById('upload-area');
    const uploadQueue = document.getElementById('upload-queue');
    const queueList = document.getElementById('queue-list');
    const chooseFilesBtn = document.getElementById('choose-files-btn');

    // Button click to upload (prevent event bubbling)
    chooseFilesBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent bubbling to uploadArea
        fileInput.click();
    });

    // Click anywhere in upload area (except button) to upload
    uploadArea.addEventListener('click', (e) => {
        // Only trigger if not clicking the button
        if (e.target !== chooseFilesBtn && !chooseFilesBtn.contains(e.target)) {
            fileInput.click();
        }
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.background = '#f0f8ff';
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.background = '';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.background = '';
        handleFileSelection(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFileSelection(e.target.files);
    });

    // Tab functionality for profile section
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Profile form submissions
    document.querySelector('.profile-form').addEventListener('submit', function(e) {
        e.preventDefault();
        handleProfileUpdate(this);
    });

    document.querySelector('.password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        handlePasswordChange(this);
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
});

// Authentication check
function checkAuth() {
    const userType = sessionStorage.getItem('user_type');
    const userId = sessionStorage.getItem('user_id');
    
    if (!userId || userType !== 'customer') {
        window.location.href = 'login.php';
        return;
    }
}

// Handle logout
async function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            // Show loading message
            showMessage('Logging out...', 'info');
            
            const response = await fetch('api/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();
            
            if (result.success) {
                // Clear all stored data
                sessionStorage.clear();
                localStorage.clear();
                
                // Redirect to login page immediately
                window.location.href = 'login.php';
            } else {
                showMessage('Logout failed. Redirecting...', 'error');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1000);
            }
        } catch (error) {
            console.error('Logout error:', error);
            // Clear storage and redirect anyway
            sessionStorage.clear();
            localStorage.clear();
            window.location.href = 'login.php';
        }
    }
}

// Load customer documents from API
async function loadCustomerDocuments() {
    try {
        const response = await fetch('api/documents.php');
        const result = await response.json();
        
        if (result.success) {
            renderCustomerDocuments(result.documents);
        } else {
            console.error('Failed to load documents:', result.message);
        }
    } catch (error) {
        console.error('Error loading documents:', error);
    }
}

// Handle profile update
async function handleProfileUpdate(form) {
    const formData = new FormData(form);
    const profileData = {
        full_name: formData.get('full_name'),
        email: formData.get('email'),
        phone: formData.get('phone')
    };

    // Validate inputs
    if (!profileData.full_name || !profileData.email) {
        showMessage('Please fill in all required fields!', 'error');
        return;
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(profileData.email)) {
        showMessage('Please enter a valid email address!', 'error');
        return;
    }

    try {
        const response = await fetch('api/profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(profileData)
        });

        const text = await response.text();
        console.log('Profile update response:', text);
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid response');
        }
        
        if (result.success) {
            showMessage('Profile updated successfully!', 'success');
            
            // Update the profile display section immediately
            const profileName = document.getElementById('profile-name');
            const profileEmail = document.getElementById('profile-email');
            const profilePhone = document.getElementById('profile-phone');
            const customerName = document.getElementById('customer-name');
            const welcomeElement = document.querySelector('.welcome-card h2');
            
            if (profileName) profileName.textContent = profileData.full_name;
            if (profileEmail) profileEmail.textContent = profileData.email;
            if (profilePhone) profilePhone.textContent = `Phone: ${profileData.phone || 'Not set'}`;
            if (customerName) customerName.textContent = profileData.full_name;
            
            // Update welcome message
            if (welcomeElement) {
                const now = new Date();
                const hour = now.getHours();
                let greeting;
                if (hour < 12) greeting = 'Good morning';
                else if (hour < 18) greeting = 'Good afternoon';
                else greeting = 'Good evening';
                welcomeElement.textContent = `${greeting}, ${profileData.full_name}!`;
            }
        } else {
            showMessage(result.message || 'Failed to update profile', 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showMessage('Error updating profile: ' + error.message, 'error');
    }
}

// Handle password change
async function handlePasswordChange(form) {
    const formData = new FormData(form);
    const currentPassword = formData.get('current_password');
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    // Validate all fields are filled
    if (!currentPassword || !newPassword || !confirmPassword) {
        showMessage('Please fill in all password fields!', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showMessage('New passwords do not match!', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showMessage('Password must be at least 6 characters long!', 'error');
        return;
    }

    try {
        const response = await fetch('api/change_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });

        // Get response text first to check if it's valid JSON
        const text = await response.text();
        console.log('Server response:', text); // Debug log
        
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid response: ' + text.substring(0, 100));
        }
        
        // Check if response is OK after parsing
        if (!response.ok) {
            throw new Error(result.message || `HTTP error! status: ${response.status}`);
        }
        
        if (result.success) {
            showMessage('Password changed successfully!', 'success');
            form.reset();
        } else {
            showMessage(result.message || 'Failed to change password', 'error');
        }
    } catch (error) {
        console.error('Error changing password:', error);
        showMessage('Error changing password: ' + error.message, 'error');
    }
}

// Handle file selection
function handleFileSelection(files) {
    const uploadQueue = document.getElementById('upload-queue');
    const queueList = document.getElementById('queue-list');
    
    Array.from(files).forEach(file => {
        if (validateFile(file)) {
            addFileToQueue(file, queueList);
            uploadQueue.style.display = 'block';
        }
    });
}

// Validate file
function validateFile(file) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/jpg',
        'image/png'
    ];
    
    if (file.size > maxSize) {
        showMessage(`File "${file.name}" is too large. Maximum size is 10MB.`, 'error');
        return false;
    }
    
    if (!allowedTypes.includes(file.type)) {
        showMessage(`File "${file.name}" has an unsupported format.`, 'error');
        return false;
    }
    
    return true;
}

// Add file to upload queue
function addFileToQueue(file, queueList) {
    const queueItem = document.createElement('div');
    queueItem.className = 'queue-item';
    queueItem.innerHTML = `
        <div class="file-info">
            <div class="file-icon">${getFileIcon(file.name)}</div>
            <div class="file-details">
                <div class="file-name">${file.name}</div>
                <div class="file-size">${formatFileSize(file.size)}</div>
            </div>
        </div>
        <div class="upload-actions">
            <button class="btn btn-sm btn-primary upload-btn" onclick="uploadFile(this, '${file.name}', ${file.size})">
                <i class="fas fa-upload"></i> Upload
            </button>
            <button class="btn btn-sm btn-secondary" onclick="removeFromQueue(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Store file object for later upload
    queueItem._file = file;
    queueList.appendChild(queueItem);
}

// Upload file
async function uploadFile(button, fileName, fileSize) {
    const queueItem = button.closest('.queue-item');
    const file = queueItem._file;
    
    if (!file) {
        showMessage('File not found', 'error');
        return;
    }
    
    // Show uploading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    button.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('title', file.name.replace(/\.[^/.]+$/, "")); // Remove extension
        
        const response = await fetch('api/upload.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            showMessage(`File "${fileName}" uploaded successfully!`, 'success');
            
            // Remove from queue
            queueItem.remove();
            
            // Hide queue if empty
            const queueList = document.getElementById('queue-list');
            if (queueList.children.length === 0) {
                document.getElementById('upload-queue').style.display = 'none';
            }
            
            // Reload documents
            loadCustomerDocuments();
            updateCustomerStats();
        } else {
            showMessage(result.message || 'Failed to upload file', 'error');
            button.innerHTML = '<i class="fas fa-upload"></i> Upload';
            button.disabled = false;
        }
    } catch (error) {
        console.error('Error uploading file:', error);
        showMessage('Error uploading file', 'error');
        button.innerHTML = '<i class="fas fa-upload"></i> Upload';
        button.disabled = false;
    }
}

// Remove file from queue
function removeFromQueue(button) {
    const queueItem = button.closest('.queue-item');
    queueItem.remove();
    
    // Hide queue if empty
    const queueList = document.getElementById('queue-list');
    if (queueList.children.length === 0) {
        document.getElementById('upload-queue').style.display = 'none';
    }
}

// Render customer documents
function renderCustomerDocuments(documents) {
    const grid = document.getElementById('customer-documents-grid');
    if (!grid) return;
    
    if (documents.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #666; padding: 40px;">No documents uploaded yet. Start by uploading your first document!</div>';
        return;
    }
    
    grid.innerHTML = documents.map(doc => `
        <div class="document-card">
            <div class="document-icon">
                ${getFileIcon(doc.file_name)}
            </div>
            <div class="document-info">
                <h4>${doc.title}</h4>
                <p>Uploaded: ${new Date(doc.upload_date).toLocaleDateString()}</p>
                <p>Size: ${formatFileSize(doc.file_size)}</p>
            </div>
            <div class="document-actions">
                <button class="btn btn-sm btn-primary" onclick="downloadDocument(${doc.id})">
                    <i class="fas fa-download"></i> Download
                </button>
                <button class="btn btn-sm btn-secondary" onclick="viewDocument(${doc.id})">
                    <i class="fas fa-eye"></i> View
                </button>
            </div>
        </div>
    `).join('');
}

// Update customer statistics
async function updateCustomerStats() {
    try {
        const response = await fetch('api/documents.php');
        const result = await response.json();
        
        if (result.success) {
            const documents = result.documents;
            
            // Update stats
            const totalDocsElement = document.getElementById('total-documents');
            if (totalDocsElement) {
                totalDocsElement.textContent = documents.length;
            }
            
            // Calculate recent uploads (last 30 days)
            const monthAgo = new Date();
            monthAgo.setDate(monthAgo.getDate() - 30);
            const recentUploads = documents.filter(doc => 
                new Date(doc.upload_date) >= monthAgo
            ).length;
            
            const recentUploadsElement = document.getElementById('recent-uploads');
            if (recentUploadsElement) {
                recentUploadsElement.textContent = recentUploads;
            }
            
            // Mock download count (would need actual tracking)
            const totalDownloadsElement = document.getElementById('total-downloads');
            if (totalDownloadsElement) {
                totalDownloadsElement.textContent = Math.floor(documents.length * 1.5);
            }
        }
    } catch (error) {
        console.error('Error updating stats:', error);
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

// View document (placeholder - would open in new tab or modal)
function viewDocument(documentId) {
    // For now, just download the document
    downloadDocument(documentId);
}

// Initialize customer dashboard
async function initializeCustomerDashboard() {
    try {
        // Get user info
        const response = await fetch('api/profile.php');
        const result = await response.json();
        
        if (result.success && result.user) {
            const user = result.user;
            
            // Update customer name in header
            const customerNameElement = document.getElementById('customer-name');
            if (customerNameElement) {
                customerNameElement.textContent = user.full_name || 'User';
            }
            
            // Update profile display section
            const profileName = document.getElementById('profile-name');
            const profileEmail = document.getElementById('profile-email');
            const profileUsername = document.getElementById('profile-username');
            const profilePhone = document.getElementById('profile-phone');
            
            if (profileName) profileName.textContent = user.full_name || 'N/A';
            if (profileEmail) profileEmail.textContent = user.email || 'N/A';
            if (profileUsername) profileUsername.textContent = `Username: ${user.username || 'N/A'}`;
            if (profilePhone) profilePhone.textContent = `Phone: ${user.phone || 'Not set'}`;
            
            // Update profile form inputs
            const profileForm = document.querySelector('.profile-form');
            if (profileForm) {
                const fullNameInput = profileForm.querySelector('[name="full_name"]');
                const emailInput = profileForm.querySelector('[name="email"]');
                const phoneInput = profileForm.querySelector('[name="phone"]');
                
                if (fullNameInput) fullNameInput.value = user.full_name || '';
                if (emailInput) emailInput.value = user.email || '';
                if (phoneInput) phoneInput.value = user.phone || '';
            }
            
            // Set welcome message
            const now = new Date();
            const hour = now.getHours();
            let greeting;
            
            if (hour < 12) greeting = 'Good morning';
            else if (hour < 18) greeting = 'Good afternoon';
            else greeting = 'Good evening';
            
            const welcomeElement = document.querySelector('.welcome-card h2');
            if (welcomeElement) {
                welcomeElement.textContent = `${greeting}, ${user.full_name || 'User'}!`;
            }
        } else {
            console.error('Failed to load user info:', result.message);
            showMessage('Failed to load profile information', 'error');
        }
    } catch (error) {
        console.error('Error initializing dashboard:', error);
        // Use fallback data from session storage
        const username = sessionStorage.getItem('username') || 'Customer';
        const customerNameElement = document.getElementById('customer-name');
        if (customerNameElement) {
            customerNameElement.textContent = username;
        }
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

// Add CSS for mobile sidebar
const style = document.createElement('style');
style.textContent = `
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