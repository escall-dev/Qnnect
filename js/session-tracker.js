/**
 * Session Tracker - Handles intelligent profile storage across the application
 */

// Track when user navigates away from the application
window.addEventListener('beforeunload', function() {
    // Get current user from session or DOM
    const currentUser = getCurrentUser();
    if (currentUser) {
        // Store profile data for later retrieval
        storeUserProfileOnExit(currentUser);
    }
});

// Function to get current user from various sources
function getCurrentUser() {
    // Try multiple methods to get current username
    
    // Method 1: Check if there's a username in session storage
    const sessionUser = sessionStorage.getItem('currentUsername');
    if (sessionUser) return sessionUser;
    
    // Method 2: Check for username in page meta or hidden elements
    const userMeta = document.querySelector('meta[name="current-user"]');
    if (userMeta) return userMeta.content;
    
    // Method 3: Check for username in any forms or inputs
    const usernameInput = document.querySelector('input[name="username"]');
    if (usernameInput && usernameInput.value) return usernameInput.value;
    
    // Method 4: Check for username in navigation or profile areas
    const profileElement = document.querySelector('[data-username]');
    if (profileElement) return profileElement.getAttribute('data-username');
    
    return null;
}

// Store user profile when they exit
function storeUserProfileOnExit(username) {
    // Use beacon API for reliable delivery even when page is unloading
    if (navigator.sendBeacon) {
        const formData = new FormData();
        formData.append('store_profile_after_login', '1');
        formData.append('username', username);
        
        navigator.sendBeacon('admin/login.php', formData);
    } else {
        // Fallback for browsers without beacon support
        fetch('admin/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'store_profile_after_login=1&username=' + encodeURIComponent(username),
            keepalive: true
        }).catch(() => {
            // Ignore errors during page unload
        });
    }
}

// Set current user in session storage when they log in
function setCurrentUser(username) {
    sessionStorage.setItem('currentUsername', username);
}

// Clear current user from session storage
function clearCurrentUser() {
    sessionStorage.removeItem('currentUsername');
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        getCurrentUser,
        setCurrentUser,
        clearCurrentUser,
        storeUserProfileOnExit
    };
} 