/**
 * Modal Helper Functions for Qnnect
 * 
 * Contains utility functions for managing modals in the Qnnect application
 */

/**
 * Closes the error attendance modal
 */
function closeErrorAttendanceModal() {
    const modal = $('#errorAttendanceModal');
    modal.addClass('fade-out');
    
    // Hide modal after animation completes
    setTimeout(() => {
        modal.hide();
    }, 300); // 300ms matches the CSS transition duration
}

/**
 * Shows the error attendance modal with specified content
 * 
 * @param {string} title - The title of the error message
 * @param {string} message - The main error message
 * @param {string} details - Optional details about the error
 */
function showErrorAttendanceModal(title, message, details) {
    console.log('showErrorAttendanceModal called with:', { title, message, details });
    
    const modal = $('#errorAttendanceModal');
    console.log('Modal element found:', modal.length > 0);
    
    const titleElement = modal.find('.error-title');
    const messageElement = modal.find('.error-message');
    const detailsElement = modal.find('.error-details');
    
    console.log('Modal elements found:', {
        title: titleElement.length > 0,
        message: messageElement.length > 0,
        details: detailsElement.length > 0
    });
    
    // Update modal content
    titleElement.text(title || 'QR Code Error');
    messageElement.text(message || 'Invalid QR Code');
    
    if (details) {
        detailsElement.text(details);
        detailsElement.show();
    } else {
        detailsElement.hide();
    }
    
    // Show modal with animation
    modal.show();
    modal.removeClass('fade-out');
    
    console.log('Modal should be visible now');
    
    // Auto-close the modal after 5 seconds
    setTimeout(closeErrorAttendanceModal, 5000);
}
