// js/login_php.js - Updated login JavaScript for PHP backend
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const loginForms = document.querySelectorAll('.login-form');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabType = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and forms
            tabButtons.forEach(btn => btn.classList.remove('active'));
            loginForms.forEach(form => form.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding form
            this.classList.add('active');
            document.getElementById(`${tabType}-form`).classList.add('active');
        });
    });

    // Form submission handlers
    document.getElementById('customer-form').addEventListener('submit', function(e) {
        e.preventDefault();
        handleLogin(this, 'customer');
    });

    document.getElementById('admin-form').addEventListener('submit', function(e) {
        e.preventDefault();
        handleLogin(this, 'admin');
    });
});

async function handleLogin(form, userType) {
    const formData = new FormData(form);
    const username = formData.get('username');
    const password = formData.get('password');
    const csrfToken = formData.get('csrf_token');
    
    if (!username || !password) {
        showMessage('Please fill in all fields', 'error');
        return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
    submitBtn.disabled = true;

    try {
        // Use the main login endpoint instead of simple
        const response = await fetch('api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                password: password,
                csrf_token: csrfToken
            })
        });

        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Get response text first to debug
        const responseText = await response.text();
        console.log('Raw response:', responseText);

        // Try to parse JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response text:', responseText);
            throw new Error('Invalid response format from server');
        }

        if (result.success) {
            showMessage(`${userType === 'admin' ? 'Admin' : 'Customer'} login successful! Redirecting...`, 'success');
            
            // Store user session data
            sessionStorage.setItem('user_id', result.user_id || username);
            sessionStorage.setItem('user_type', result.user_type);
            sessionStorage.setItem('username', username);
            
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 1500);
        } else {
            showMessage(result.message || 'Login failed', 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showMessage(`Login failed: ${error.message}`, 'error');
    } finally {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Logout function
async function logout() {
    try {
        const response = await fetch('api/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        const result = await response.json();
        
        if (result.success) {
            // Clear session storage
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

// Password toggle functionality
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Fill demo credentials function
function fillDemoCredentials(type) {
    if (type === 'customer') {
        document.getElementById('customer-username').value = 'demo';
        document.getElementById('customer-password').value = 'demo123';
        showMessage('Demo customer credentials filled!', 'info');
    } else if (type === 'admin') {
        document.getElementById('admin-username').value = 'admin';
        document.getElementById('admin-password').value = 'admin123';
        showMessage('Demo admin credentials filled!', 'info');
    }
}

// Message display function
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
    } else {
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

// Add slide-in animation CSS
const style = document.createElement('style');
style.textContent = `
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