/**
 * Enhanced Schedule Call Form Handler with AJAX
 * Provides smooth consultation booking experience without page reload
 * Includes validation, loading states, and user feedback for appointment scheduling
 */

document.addEventListener('DOMContentLoaded', function() {
    const scheduleForm = document.querySelector('.schedule-call-form-modern');
    const submitButton = scheduleForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Initialize form enhancements
    initializeFormFeatures();
    
    // Form submission handler
    scheduleForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous messages
        clearMessages();
        
        // Validate form
        if (!validateScheduleForm()) {
            return;
        }
        
        // Show loading state
        setLoadingState(true);
        
        try {
            // Prepare form data
            const formData = new FormData(scheduleForm);
            
            // Send AJAX request
            const response = await fetch('schedule_handler.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccessMessage(result.message);
                scheduleForm.reset();
                
                // Reset country code dropdown to default
                const countrySelect = scheduleForm.querySelector('select[name="country_code"]');
                if (countrySelect) {
                    countrySelect.selectedIndex = 0;
                }
                
                // Optional: Track successful booking
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submit', {
                        event_category: 'Consultation',
                        event_label: 'Schedule Call Success'
                    });
                }
                
                // Scroll to success message
                scrollToMessage();
                
            } else {
                showErrorMessage(result.message);
                scrollToMessage();
            }
            
        } catch (error) {
            console.error('Schedule form submission error:', error);
            showErrorMessage('Network error. Please check your connection and try again.');
            scrollToMessage();
        } finally {
            setLoadingState(false);
        }
    });
    
    /**
     * Form Validation for Schedule Call
     */
    function validateScheduleForm() {
        let isValid = true;
        const errors = [];
        
        // Get form fields
        const firstName = scheduleForm.querySelector('input[name="first_name"]').value.trim();
        const lastName = scheduleForm.querySelector('input[name="last_name"]').value.trim();
        const email = scheduleForm.querySelector('input[name="email"]').value.trim();
        const phone = scheduleForm.querySelector('input[name="phone"]').value.trim();
        const countryCode = scheduleForm.querySelector('select[name="country_code"]').value;
        const callTime = scheduleForm.querySelector('input[name="call_time"]').value;
        const website = scheduleForm.querySelector('input[name="website"]').value.trim();
        
        // Validate first name
        if (!firstName) {
            errors.push('First name is required.');
            highlightField('first_name');
            isValid = false;
        } else if (firstName.length < 2) {
            errors.push('First name must be at least 2 characters long.');
            highlightField('first_name');
            isValid = false;
        } else {
            clearFieldHighlight('first_name');
        }
        
        // Validate last name
        if (!lastName) {
            errors.push('Last name is required.');
            highlightField('last_name');
            isValid = false;
        } else if (lastName.length < 2) {
            errors.push('Last name must be at least 2 characters long.');
            highlightField('last_name');
            isValid = false;
        } else {
            clearFieldHighlight('last_name');
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
        
        // Validate phone
        if (!phone) {
            errors.push('Phone number is required.');
            highlightField('phone');
            isValid = false;
        } else if (!isValidPhone(phone)) {
            errors.push('Please enter a valid phone number (7-15 digits).');
            highlightField('phone');
            isValid = false;
        } else {
            clearFieldHighlight('phone');
        }
        
        // Validate country code
        if (!countryCode) {
            errors.push('Please select a country code.');
            highlightField('country_code');
            isValid = false;
        } else {
            clearFieldHighlight('country_code');
        }
        
        // Validate call time
        if (!callTime) {
            errors.push('Please select your preferred call time.');
            highlightField('call_time');
            isValid = false;
        } else if (!isValidDateTime(callTime)) {
            errors.push('Please select a future date and time.');
            highlightField('call_time');
            isValid = false;
        } else {
            clearFieldHighlight('call_time');
        }
        
        // Validate website (optional but check format if provided)
        if (website && !isValidWebsite(website)) {
            errors.push('Please enter a valid website URL.');
            highlightField('website');
            isValid = false;
        } else {
            clearFieldHighlight('website');
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
        const phoneRegex = /^[0-9]{7,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
    }
    
    function isValidDateTime(datetime) {
        const selectedDate = new Date(datetime);
        const now = new Date();
        return selectedDate > now;
    }
    
    function isValidWebsite(website) {
        try {
            // Add protocol if missing
            let url = website;
            if (!url.match(/^https?:\/\//)) {
                url = 'http://' + url;
            }
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    /**
     * Field Highlighting Functions
     */
    function highlightField(fieldName) {
        const field = scheduleForm.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.style.borderColor = '#ff4757';
            field.style.backgroundColor = 'rgba(255, 71, 87, 0.1)';
        }
    }
    
    function clearFieldHighlight(fieldName) {
        const field = scheduleForm.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.style.borderColor = '';
            field.style.backgroundColor = '';
        }
    }
    
    /**
     * Initialize Form Features
     */
    function initializeFormFeatures() {
        // Set minimum date/time to current time + 1 hour
        setMinimumDateTime();
        
        // Add real-time validation
        addRealTimeValidation();
        
        // Format phone input
        addPhoneFormatting();
        
        // Enhance date/time picker
        enhanceDateTimePicker();
    }
    
    function setMinimumDateTime() {
        const callTimeInput = scheduleForm.querySelector('input[name="call_time"]');
        if (callTimeInput) {
            const now = new Date();
            now.setHours(now.getHours() + 1); // Minimum 1 hour from now
            
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            callTimeInput.min = minDateTime;
        }
    }
    
    function addRealTimeValidation() {
        const fields = scheduleForm.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                validateSingleField(this);
            });
            
            field.addEventListener('focus', function() {
                clearFieldHighlight(this.name);
            });
        });
    }
    
    function validateSingleField(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        
        switch (fieldName) {
            case 'first_name':
            case 'last_name':
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
                if (value && isValidPhone(value)) {
                    clearFieldHighlight(fieldName);
                }
                break;
            case 'call_time':
                if (value && isValidDateTime(value)) {
                    clearFieldHighlight(fieldName);
                }
                break;
            case 'website':
                if (!value || isValidWebsite(value)) {
                    clearFieldHighlight(fieldName);
                }
                break;
            case 'country_code':
                if (value) {
                    clearFieldHighlight(fieldName);
                }
                break;
        }
    }
    
    function addPhoneFormatting() {
        const phoneInput = scheduleForm.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                // Remove any non-digit characters for validation
                let value = e.target.value.replace(/\D/g, '');
                
                // Limit to 15 digits
                if (value.length > 15) {
                    value = value.slice(0, 15);
                }
                
                e.target.value = value;
            });
        }
    }
    
    function enhanceDateTimePicker() {
        const callTimeInput = scheduleForm.querySelector('input[name="call_time"]');
        if (callTimeInput) {
            // Add helper text
            const helperText = document.createElement('small');
            helperText.style.cssText = `
                color: #666;
                font-size: 0.85rem;
                margin-top: 5px;
                display: block;
            `;
            helperText.textContent = 'Please select a time at least 1 hour from now (UTC timezone)';
            
            callTimeInput.parentElement.appendChild(helperText);
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
                    Scheduling...
                </div>
            `;
            
            // Add loading spinner styles if not already present
            addLoadingSpinnerStyles();
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    }
    
    function addLoadingSpinnerStyles() {
        if (!document.querySelector('#schedule-loading-spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'schedule-loading-spinner-styles';
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
    }
    
    function showSuccessMessage(message) {
        const messageDiv = createMessageDiv(message, 'success');
        scheduleForm.insertBefore(messageDiv, scheduleForm.firstChild);
        
        // Auto-hide after 8 seconds
        setTimeout(() => {
            if (messageDiv.parentElement) {
                messageDiv.remove();
            }
        }, 8000);
    }
    
    function showErrorMessage(message) {
        const messageDiv = createMessageDiv(message, 'error');
        scheduleForm.insertBefore(messageDiv, scheduleForm.firstChild);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (messageDiv.parentElement) {
                messageDiv.remove();
            }
        }, 10000);
    }
    
    function createMessageDiv(message, type) {
        const div = document.createElement('div');
        div.className = `schedule-form-message schedule-form-message-${type}`;
        div.innerHTML = `
            <div style="
                padding: 15px 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                border: 1px solid;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                ${type === 'success' 
                    ? 'background: linear-gradient(135deg, rgba(19, 183, 122, 0.1) 0%, rgba(28, 207, 207, 0.1) 100%); border-color: #13b77a; color: #0d8f5f;' 
                    : 'background: rgba(255, 71, 87, 0.1); border-color: #ff4757; color: #d63031;'
                }
            ">
                <span style="font-size: 1.3rem; flex-shrink: 0;">
                    ${type === 'success' ? '🎉' : '⚠️'}
                </span>
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 5px;">
                        ${type === 'success' ? 'Consultation Scheduled!' : 'Booking Error'}
                    </div>
                    <div>${message}</div>
                </div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" style="
                    background: none;
                    border: none;
                    color: inherit;
                    font-size: 1.4rem;
                    cursor: pointer;
                    padding: 0;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    opacity: 0.7;
                    flex-shrink: 0;
                " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">×</button>
            </div>
        `;
        return div;
    }
    
    function clearMessages() {
        const messages = scheduleForm.querySelectorAll('.schedule-form-message');
        messages.forEach(msg => msg.remove());
    }
    
    function scrollToMessage() {
        const firstMessage = scheduleForm.querySelector('.schedule-form-message');
        if (firstMessage) {
            firstMessage.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }
});

/**
 * Country Code Population (from existing script)
 * Enhanced to work with the new form handler
 */
window.addEventListener('DOMContentLoaded', function() {
    const codeSelect = document.querySelector('select[name="country_code"]');
    if (codeSelect && typeof countryCodes !== 'undefined') {
        // Clear existing options except the first one
        codeSelect.innerHTML = '<option value="">Code</option>';
        
        // Populate with country codes
        countryCodes.forEach(function(country) {
            const option = document.createElement('option');
            option.value = country.dial_code;
            option.textContent = `${country.dial_code} ${country.name}`;
            codeSelect.appendChild(option);
        });
        
        // Set default to a common country (optional)
        // Uncomment the line below to set a default country
        // codeSelect.value = '+1'; // Default to US/Canada
    }
});

/**
 * Fallback for browsers without fetch support
 */
if (!window.fetch) {
    console.warn('Fetch API not supported. Schedule form will use standard submission.');
}