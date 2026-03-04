/**
 * MediCore HMS - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });

    // Table row hover effects
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'var(--gray-50)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Form validation enhancement
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--danger-500)';
                    
                    setTimeout(() => {
                        field.style.borderColor = '';
                    }, 3000);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'error');
            }
        });
    });

    // Datetime input default value for appointments
    const datetimeInputs = document.querySelectorAll('input[type="datetime-local"]');
    if (datetimeInputs.length > 0) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const defaultDateTime = now.toISOString().slice(0, 16);
        
        datetimeInputs.forEach(input => {
            if (!input.value && input.name === 'appt_start') {
                input.value = defaultDateTime;
                
                // Set end time 30 minutes after start
                const endInput = input.form?.querySelector('[name="appt_end"]');
                if (endInput && !endInput.value) {
                    const endTime = new Date(now.getTime() + 30 * 60000);
                    endTime.setMinutes(endTime.getMinutes() - endTime.getTimezoneOffset());
                    endInput.value = endTime.toISOString().slice(0, 16);
                }
            }
        });
        
        // Auto-update end time when start time changes
        const startInput = document.querySelector('[name="appt_start"]');
        const endInput = document.querySelector('[name="appt_end"]');
        
        if (startInput && endInput) {
            startInput.addEventListener('change', function() {
                const startTime = new Date(this.value);
                const endTime = new Date(startTime.getTime() + 30 * 60000);
                endTime.setMinutes(endTime.getMinutes() - endTime.getTimezoneOffset());
                endInput.value = endTime.toISOString().slice(0, 16);
            });
        }
    }

    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="text"][name="phone"], input[type="text"][name="emergency_contact"]');
    phoneInputs.forEach(input => {
        input.addEventListener('blur', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length === 11 && value.startsWith('0')) {
                // Format: +880-1XXX-XXXXXX
                this.value = `+880-${value.substring(1, 5)}-${value.substring(5)}`;
            }
        });
    });

    // Status badge color animation
    const badges = document.querySelectorAll('.badge');
    badges.forEach((badge, index) => {
        badge.style.animationDelay = `${index * 0.05}s`;
    });

    // Stat cards animation
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Search highlighting
    function highlightSearch(input, targetRows) {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            targetRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (searchTerm && text.includes(searchTerm)) {
                    row.style.backgroundColor = 'var(--primary-100)';
                } else {
                    row.style.backgroundColor = '';
                }
            });
        });
    }
});

// Utility Functions
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `<strong>${type === 'error' ? 'Error!' : 'Success!'}</strong> ${message}`;
    
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alertDiv.remove();
            }, 500);
        }, 5000);
    }
}

function confirmDelete(message = 'Are you sure you want to delete this record?') {
    return confirm(message);
}

function printReport() {
    window.print();
}

// Export data to CSV
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Console welcome message
console.log('%c🏥 MediCore HMS', 'color: #1565c0; font-size: 24px; font-weight: bold;');
console.log('%cHospital Management System', 'color: #666; font-size: 14px;');
console.log('%cDeveloped with ❤️ for healthcare professionals', 'color: #999; font-size: 12px;');