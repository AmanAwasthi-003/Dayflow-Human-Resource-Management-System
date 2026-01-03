// assets/js/validation.js
// Client-side form validation

document.addEventListener('DOMContentLoaded', function() {
    // Password validation for signup form
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        signupForm.addEventListener('submit', function(e) {
            let isValid = true;
            let errors = [];
            
            // Password strength validation
            const passwordValue = password.value;
            
            if (passwordValue.length < 8) {
                errors.push('Password must be at least 8 characters long');
                isValid = false;
            }
            
            if (!/[A-Z]/.test(passwordValue)) {
                errors.push('Password must contain at least one uppercase letter');
                isValid = false;
            }
            
            if (!/[a-z]/.test(passwordValue)) {
                errors.push('Password must contain at least one lowercase letter');
                isValid = false;
            }
            
            if (!/[0-9]/.test(passwordValue)) {
                errors.push('Password must contain at least one number');
                isValid = false;
            }
            
            if (!/[^A-Za-z0-9]/.test(passwordValue)) {
                errors.push('Password must contain at least one special character');
                isValid = false;
            }
            
            // Password match validation
            if (passwordValue !== confirmPassword.value) {
                errors.push('Passwords do not match');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }
        });
        
        // Real-time password strength indicator
        password.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            
            if (value.length >= 8) strength++;
            if (/[A-Z]/.test(value)) strength++;
            if (/[a-z]/.test(value)) strength++;
            if (/[0-9]/.test(value)) strength++;
            if (/[^A-Za-z0-9]/.test(value)) strength++;
            
            const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const strengthColor = ['#EF4444', '#F59E0B', '#FCD34D', '#10B981', '#059669'];
            
            // Show strength indicator (if you add UI element for it)
            console.log('Password strength:', strengthText[strength - 1] || 'Very Weak');
        });
    }
    
    // Date validation for leave form
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
            if (endDate.value && endDate.value < this.value) {
                endDate.value = this.value;
            }
        });
        
        endDate.addEventListener('change', function() {
            if (this.value < startDate.value) {
                alert('End date cannot be before start date');
                this.value = startDate.value;
            }
        });
    }
    
    // File upload validation
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file size (5MB max)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Check file type for images
                if (this.accept && this.accept.includes('image')) {
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPG, JPEG, PNG, or GIF)');
                        this.value = '';
                        return;
                    }
                }
            }
        });
    });
    
    // Form confirmation for important actions
    const confirmForms = document.querySelectorAll('form[data-confirm]');
    confirmForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
});