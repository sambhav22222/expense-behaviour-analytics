/**
 * Expense Behaviour Analytical Platform (EBAP)
 * Client-side Interactions and UI Utilities
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('ebap-sidebar-toggle');
    const sidebar = document.getElementById('ebap-sidebar');
    const sidebarBackdrop = document.getElementById('ebap-sidebar-backdrop');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (sidebarBackdrop) {
                sidebarBackdrop.classList.toggle('show');
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            sidebar.classList.remove('show');
            sidebarBackdrop.classList.remove('show');
        });
    }

    // 2. Generic Action Confirmation Dialogs
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed with this action?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });

    // 3. Auto-dismiss alerts after 6 seconds
    setTimeout(function () {
        document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        });
    }, 6000);

    // 4. Dynamic Date Filter Submission
    const periodSelect = document.getElementById('filter-period');
    const customDateContainer = document.getElementById('custom-date-container');
    if (periodSelect && customDateContainer) {
        periodSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                customDateContainer.classList.remove('d-none');
            } else {
                customDateContainer.classList.add('d-none');
                if (this.form) {
                    this.form.submit();
                }
            }
        });
    }
});
