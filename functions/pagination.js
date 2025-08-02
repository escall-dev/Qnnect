/**
 * Standardized Pagination JavaScript for QR Code Attendance System
 * This file provides consistent pagination styling and functionality
 * across all pages in the application.
 */

/**
 * Applies standardized styling to DataTables pagination elements
 */
function styleDataTablePagination() {
    // Clear any existing pagination
    $('.dataTables_wrapper .dataTables_paginate').empty();
    
    // Get pagination info from DataTable
    const table = $('.dataTable').DataTable();
    const info = table.page.info();
    const currentPage = info.page + 1; // DataTables is 0-indexed
    const totalPages = info.pages;
    
    // Create custom Bootstrap pagination
    let paginationHtml = `
    <ul class="pagination justify-content-center">
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="first">First</a>
        </li>
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="previous">Previous</a>
        </li>
    `;
    
    // Add page numbers
    const maxVisiblePages = 3;
    const startPage = Math.max(1, Math.min(currentPage - Math.floor(maxVisiblePages / 2), totalPages - maxVisiblePages + 1));
    const endPage = Math.min(startPage + maxVisiblePages - 1, totalPages);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i-1}">${i}</a>
            </li>
        `;
    }
    
    paginationHtml += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="next">Next</a>
        </li>
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="last">Last</a>
        </li>
    </ul>
    `;
    
    // Insert the custom pagination
    $('.dataTables_wrapper .dataTables_paginate').html(paginationHtml);
    
    // Ensure proper centering of pagination
    $('.dataTables_wrapper .dataTables_paginate').css({
        'display': 'flex',
        'justify-content': 'center',
        'width': '100%',
        'margin-top': '20px'
    });
    
    // Add event listeners
    $('.dataTables_wrapper .dataTables_paginate .page-link').on('click', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        
        if (page === 'first') {
            table.page('first').draw('page');
        } else if (page === 'previous') {
            table.page('previous').draw('page');
        } else if (page === 'next') {
            table.page('next').draw('page');
        } else if (page === 'last') {
            table.page('last').draw('page');
        } else {
            table.page(page).draw('page');
        }
    });
}

/**
 * Initialize DataTable with standardized options
 * @param {string} tableSelector - The jQuery selector for the table
 * @param {Object} options - Custom options to override defaults
 * @returns {Object} - The initialized DataTable instance
 */
function initializeStandardDataTable(selector, options = {}) {
    // Default options for a standardized DataTable
    const defaultOptions = {
        paging: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        info: true,
        searching: false,
        responsive: true,
        // Use custom Bootstrap pagination
        dom: "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 d-flex justify-content-center'i>>" +
             "<'row'<'col-sm-12 d-flex justify-content-center'p>>",
        language: {
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: '',
                previous: '',
                next: '',
                last: ''
            }
        },
        drawCallback: function() {
            styleDataTablePagination();
        },
        initComplete: function() {
            styleDataTablePagination();
        }
    };
    
    // Merge custom options with defaults
    const mergedOptions = $.extend(true, {}, defaultOptions, options);
    
    // Initialize the DataTable with the merged options
    return $(selector).DataTable(mergedOptions);
}

/**
 * Creates a standardized custom pagination (non-DataTables)
 * @param {number} currentPage - The current page number
 * @param {number} totalPages - The total number of pages
 * @param {string} baseUrl - The base URL for pagination links (without page parameter)
 * @param {string} containerId - The ID of the container to insert pagination into
 */
function createStandardPagination(currentPage, totalPages, baseUrl, containerId) {
    console.log('Creating pagination:', { currentPage, totalPages, baseUrl, containerId });
    
    const container = $('#' + containerId);
    if (!container.length) {
        console.error('Pagination container not found:', containerId);
        return;
    }
    
    // If there's only one page or no pages, don't show pagination
    if (totalPages <= 1) {
        container.empty();
        return;
    }
    
    let paginationHtml = `
    <div class="pagination-container">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="${baseUrl}page=1">First</a>
                </li>
                <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="${baseUrl}page=${currentPage - 1}">Previous</a>
                </li>
    `;
    
    // Add page numbers
    const maxVisiblePages = 3; // Show only 3 page numbers (1, 2, 3) like in the image
    const startPage = Math.max(1, Math.min(currentPage - Math.floor(maxVisiblePages / 2), totalPages - maxVisiblePages + 1));
    const endPage = Math.min(startPage + maxVisiblePages - 1, totalPages);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="${baseUrl}page=${i}">${i}</a>
            </li>
        `;
    }
    
    paginationHtml += `
                <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="${baseUrl}page=${currentPage + 1}">Next</a>
                </li>
                <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="${baseUrl}page=${totalPages}">Last</a>
                </li>
            </ul>
        </nav>
    </div>
    `;
    
    container.html(paginationHtml);
}

// Apply pagination styling when document is ready
$(document).ready(function() {
    // Apply styling to any existing DataTables
    if ($.fn.dataTable && $.fn.dataTable.tables().length > 0) {
        styleDataTablePagination();
    }
}); 