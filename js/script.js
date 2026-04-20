// PaperVista JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // File upload preview
    if (document.getElementById('paper-upload')) {
        document.getElementById('paper-upload').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var preview = document.getElementById('file-preview');
                if (preview) {
                    preview.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-file-alt me-2"></i>
                            Selected: ${file.name} (${formatFileSize(file.size)})
                        </div>
                    `;
                }
            }
        });
    }

    // Search form enhancement
    var searchForm = document.querySelector('form[action="search.php"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            var searchInput = this.querySelector('input[name="q"]');
            if (searchInput && searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
                showAlert('Please enter a search term', 'warning');
                return false;
            }
        });
    }

    // Password strength indicator
    var passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(function(input) {
        if (input.id === 'password' || input.id === 'confirm_password') {
            input.addEventListener('input', function() {
                if (this.id === 'password' && this.value.length > 0) {
                    updatePasswordStrength(this.value);
                }
            });
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Loading state for forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner me-2"></span>Processing...';
                submitBtn.disabled = true;
            }
        });
    });
});

// Utility Functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024,
        sizes = ['Bytes', 'KB', 'MB', 'GB'],
        i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function updatePasswordStrength(password) {
    var strength = calculatePasswordStrength(password);
    var indicator = document.getElementById('password-strength');

    if (!indicator) {
        var passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.parentNode.insertAdjacentHTML('afterend',
                '<div id="password-strength" class="mt-2"></div>');
            indicator = document.getElementById('password-strength');
        }
    }

    if (indicator) {
        var strengthText = '';
        var strengthClass = '';

        if (password.length === 0) {
            strengthText = '';
        } else if (strength < 30) {
            strengthText = 'Weak';
            strengthClass = 'text-danger';
        } else if (strength < 70) {
            strengthText = 'Medium';
            strengthClass = 'text-warning';
        } else {
            strengthText = 'Strong';
            strengthClass = 'text-success';
        }

        indicator.innerHTML = strengthText ? `Password strength: <span class="${strengthClass}">${strengthText}</span>` : '';
    }
}

function calculatePasswordStrength(password) {
    var strength = 0;

    // Length check
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 15;

    // Character variety
    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^A-Za-z0-9]/.test(password)) strength += 10;

    return Math.min(strength, 100);
}

function showAlert(message, type = 'info') {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    var container = document.querySelector('.container');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            var alert = container.querySelector('.alert');
            if (alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
}

// Drag and drop file upload enhancement
if (document.getElementById('upload-area')) {
    var uploadArea = document.getElementById('upload-area');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        uploadArea.classList.add('drag-over');
    }

    function unhighlight(e) {
        uploadArea.classList.remove('drag-over');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        var dt = e.dataTransfer;
        var files = dt.files;

        if (files.length > 0) {
            document.getElementById('paper-upload').files = files;
            document.getElementById('paper-upload').dispatchEvent(new Event('change'));
        }
    }
}

// Form validation enhancements
document.querySelectorAll('input[required]').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    input.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            this.classList.remove('is-invalid');
        }
    });
});

// Mobile menu enhancement
var mobileMenuToggle = document.querySelector('.navbar-toggler');
if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', function() {
        document.body.classList.toggle('mobile-menu-open');
    });
}

// Search suggestions (placeholder for future implementation)
function showSearchSuggestions(query) {
    // This would typically make an AJAX call to get suggestions
    console.log('Search suggestions for:', query);
}

// Theme toggle (for future dark mode implementation)
function toggleTheme() {
    document.body.classList.toggle('dark-theme');
    localStorage.setItem('theme',
        document.body.classList.contains('dark-theme') ? 'dark' : 'light');
}

// Initialize theme from localStorage
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-theme');
}
