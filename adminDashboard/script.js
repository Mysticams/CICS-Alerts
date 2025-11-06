
// Shared functionality for the admin dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new Tooltip(tooltipTriggerEl);
    });

    // Real-time updates simulation
    setInterval(updateRealTimeData, 10000);

    // Toggle sidebar on mobile
    const mobileMenuButton = document.createElement('button');
    mobileMenuButton.innerHTML = '<i data-feather="menu"></i>';
    mobileMenuButton.className = 'md:hidden p-2 rounded-md text-gray-700 hover:text-red-600 hover:bg-gray-100 fixed top-2 left-2 z-50';
    mobileMenuButton.addEventListener('click', toggleSidebar);
    document.body.appendChild(mobileMenuButton);
    feather.replace();
});

function toggleSidebar() {
    const sidebar = document.querySelector('custom-sidebar').shadowRoot.querySelector('aside');
    sidebar.classList.toggle('active');
}
function updateRealTimeData() {
    // Simulate real-time updates
    const notificationBadge = document.querySelector('custom-navbar').shadowRoot.querySelector('.notification-badge');
    const currentCount = parseInt(notificationBadge.textContent);
    const newCount = Math.min(9, currentCount + Math.floor(Math.random() * 2));
    notificationBadge.textContent = newCount;
    
    if (newCount > currentCount) {
        notificationBadge.parentElement.classList.add('animate-pulse');
        setTimeout(() => {
            notificationBadge.parentElement.classList.remove('animate-pulse');
        }, 3000);
    }
}