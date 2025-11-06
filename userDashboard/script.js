// Shared JavaScript across all pages
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle for sidebar
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileMenuButton && sidebar) {
        mobileMenuButton.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    
    // Active menu item highlighting
    const currentPath = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('[data-menu-item]');
    
    menuItems.forEach(item => {
        const itemPath = item.getAttribute('href').split('/').pop();
        if (itemPath === currentPath) {
            item.classList.add('text-primary', 'font-bold');
            item.classList.remove('text-gray-600');
        }
    });
});