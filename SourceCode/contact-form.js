/**
 * Enhanced Contact Form Handler with AJAX
 * Provides smooth form submission experience without page reload
 * Includes validation, loading states, and user feedback
 */

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const submitButton = contactForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Form submission handler
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous messages
        clearMessages();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        setLoadingState(true);
        
        try {
            // Prepare form data
            const formData = new FormData(contactForm);
            
            // Send AJAX request
            const response = await fetch('contact_handler.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccessMessage(result.message);
                contactForm.reset();
                
                // Optional: Track successful submission
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submit', {
                        event_category: 'Contact',
                        event_label: 'Contact Form Success'
                    });
                }
            } else {
                showErrorMessage(result.message);
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            showErrorMessage('Network error. Please check your connection and try again.');
        } finally {
            setLoadingState(false);
        }
    });
    
    /**
     * Form Validation
     */
    function validateForm() {
        let isValid = true;
        const errors = [];
        
        // Get form fields
        const name = contactForm.querySelector('input[name="name"]').value.trim();
        const email = contactForm.querySelector('input[name="email"]').value.trim();
        const phone = contactForm.querySelector('input[name="phone"]').value.trim();
        const message = contactForm.querySelector('textarea[name="message"]').value.trim();
        
        // Validate name
        if (!name) {
            errors.push('Name is required.');
            highlightField('name');
            isValid = false;
        } else if (name.length < 2) {
            errors.push('Name must be at least 2 characters long.');
            highlightField('name');
            isValid = false;
        } else {
            clearFieldHighlight('name');
        }
        
        // Validate email
        if (!email) {
            errors.push('Email is required.');
            highlightField('email');
            isValid = false;
        } else if (!isValidEmail(email)) {
            errors.push('Please enter a valid email address.');
            highlightField('email');
            isValid = false;
        } else {
            clearFieldHighlight('email');
        }
        
        // Validate phone (optional but format check if provided)
        if (phone && !isValidPhone(phone)) {
            errors.push('Please enter a valid phone number.');
            highlightField('phone');
            isValid = false;
        } else {
            clearFieldHighlight('phone');
        }
        
        // Validate message
        if (!message) {
            errors.push('Message is required.');
            highlightField('message');
            isValid = false;
        } else if (message.length < 10) {
            errors.push('Message must be at least 10 characters long.');
            highlightField('message');
            isValid = false;
        } else {
            clearFieldHighlight('message');
        }
        
        // Show validation errors
        if (!isValid) {
            showErrorMessage('Please fix the following errors: ' + errors.join(' '));
        }
        
        return isValid;
    }
    
    /**
     * Validation Helper Functions
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidPhone(phone) {
        const phoneRegex = /^[+]?[\d\s\-\(\)]{10,20}$/;
        return phoneRegex.test(phone);
    }
    
    /**
     * Field Highlighting Functions
     */
    function highlightField(fieldName) {
        const field = contactForm.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.style.borderColor = '#ff4757';
            field.style.backgroundColor = 'rgba(255, 71, 87, 0.1)';
        }
    }
    
    function clearFieldHighlight(fieldName) {
        const field = contactForm.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.style.borderColor = '';
            field.style.backgroundColor = '';
        }
    }
    
    /**
     * UI State Management
     */
    function setLoadingState(loading) {
        if (loading) {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <div class="loading-spinner"></div>
                    Sending...
                </div>
            `;
            
            // Add loading spinner styles if not already present
            if (!document.querySelector('#loading-spinner-styles')) {
                const style = document.createElement('style');
                style.id = 'loading-spinner-styles';
                style.textContent = `
                    .loading-spinner {
                        width: 16px;
                        height: 16px;
                        border: 2px solid rgba(255, 255, 255, 0.3);
                        border-radius: 50%;
                        border-top-color: #fff;
                        animation: spin 1s ease-in-out infinite;
                    }
                    @keyframes spin {
                        to { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    }
    
    function showSuccessMessage(message) {
        const messageDiv = createMessageDiv(message, 'success');
        contactForm.insertBefore(messageDiv, contactForm.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
    
    function showErrorMessage(message) {
        const messageDiv = createMessageDiv(message, 'error');
        contactForm.insertBefore(messageDiv, contactForm.firstChild);
        
        // Auto-hide after 8 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 8000);
    }
    
    function createMessageDiv(message, type) {
        const div = document.createElement('div');
        div.className = `form-message form-message-${type}`;
        div.innerHTML = `
            <div style="
                padding: 15px 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                border: 1px solid;
                display: flex;
                align-items: center;
                gap: 10px;
                ${type === 'success' 
                    ? 'background: rgba(67, 233, 123, 0.15); border-color: #43e97b; color: #2ed573;' 
                    : 'background: rgba(255, 71, 87, 0.15); border-color: #ff4757; color: #ff3742;'
                }
            ">
                <span style="font-size: 1.2rem;">
                    ${type === 'success' ? '✅' : '❌'}
                </span>
                <span>${message}</span>
                <button type="button" onclick="this.parentElement.parentElement.remove()" style="
                    background: none;
                    border: none;
                    color: inherit;
                    font-size: 1.2rem;
                    cursor: pointer;
                    margin-left: auto;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">×</button>
            </div>
        `;
        return div;
    }
    
    function clearMessages() {
        const messages = contactForm.querySelectorAll('.form-message');
        messages.forEach(msg => msg.remove());
    }
    
    /**
     * Real-time Validation (Optional)
     */
    function addRealTimeValidation() {
        const fields = contactForm.querySelectorAll('input, textarea');
        
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                const fieldName = this.name;
                const value = this.value.trim();
                
                switch (fieldName) {
                    case 'name':
                        if (value && value.length >= 2) {
                            clearFieldHighlight(fieldName);
                        }
                        break;
                    case 'email':
                        if (value && isValidEmail(value)) {
                            clearFieldHighlight(fieldName);
                        }
                        break;
                    case 'phone':
                        if (!value || isValidPhone(value)) {
                            clearFieldHighlight(fieldName);
                        }
                        break;
                    case 'message':
                        if (value && value.length >= 10) {
                            clearFieldHighlight(fieldName);
                        }
                        break;
                }
            });
            
            // Clear highlight on focus
            field.addEventListener('focus', function() {
                clearFieldHighlight(this.name);
            });
        });
    }
    
    // Initialize real-time validation
    addRealTimeValidation();
    
    /**
     * Character Counter for Message Field
     */
    function addCharacterCounter() {
        const messageField = contactForm.querySelector('textarea[name="message"]');
        const label = messageField.parentElement.querySelector('label');
        
        const counter = document.createElement('span');
        counter.style.cssText = `
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            float: right;
        `;
        label.appendChild(counter);
        
        function updateCounter() {
            const length = messageField.value.length;
            counter.textContent = `${length} characters`;
            
            if (length < 10) {
                counter.style.color = '#ff4757';
            } else if (length < 50) {
                counter.style.color = '#ffa502';
            } else {
                counter.style.color = '#2ed573';
            }
        }
        
        messageField.addEventListener('input', updateCounter);
        updateCounter(); // Initial count
    }
    
    // Initialize character counter
    addCharacterCounter();
});

/**
 * Fallback for browsers without fetch support
 */
if (!window.fetch) {
    console.warn('Fetch API not supported. Form will use standard submission.');
}