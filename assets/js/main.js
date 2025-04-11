/**
 * Main JavaScript file for the Tuition Management System
 */

// Global constants and configurations
const API_BASE_URL = '/api';

// Utility functions
const api = {
    /**
     * Make an API request
     * 
     * @param {string} endpoint - API endpoint
     * @param {string} method - HTTP method (GET, POST, PUT, DELETE)
     * @param {object} data - Request data
     * @returns {Promise} - Promise resolving to the API response
     */
    request: async (endpoint, method = 'GET', data = null) => {
        const url = `${API_BASE_URL}/${endpoint}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (response.status === 401) {
                // Unauthorized, redirect to login
                window.location.href = '/login.php';
                return null;
            }
            
            return result;
        } catch (error) {
            console.error('API request failed:', error);
            return { success: false, message: 'Network error. Please try again.' };
        }
    },
    
    /**
     * GET request
     * 
     * @param {string} endpoint - API endpoint
     * @returns {Promise} - Promise resolving to the API response
     */
    get: (endpoint) => api.request(endpoint, 'GET'),
    
    /**
     * POST request
     * 
     * @param {string} endpoint - API endpoint
     * @param {object} data - Request data
     * @returns {Promise} - Promise resolving to the API response
     */
    post: (endpoint, data) => api.request(endpoint, 'POST', data),
    
    /**
     * PUT request
     * 
     * @param {string} endpoint - API endpoint
     * @param {object} data - Request data
     * @returns {Promise} - Promise resolving to the API response
     */
    put: (endpoint, data) => api.request(endpoint, 'PUT', data),
    
    /**
     * DELETE request
     * 
     * @param {string} endpoint - API endpoint
     * @returns {Promise} - Promise resolving to the API response
     */
    delete: (endpoint) => api.request(endpoint, 'DELETE')
};

// UI utilities
const ui = {
    /**
     * Show a notification message
     * 
     * @param {string} message - Message to display
     * @param {string} type - Message type (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds
     */
    showNotification: (message, type = 'info', duration = 5000) => {
        // Check if notification container exists
        let container = document.getElementById('notification-container');
        
        if (!container) {
            // Create container
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add notification to container
        container.appendChild(notification);
        
        // Initialize Bootstrap alert
        const bsAlert = new bootstrap.Alert(notification);
        
        // Auto-dismiss after duration
        setTimeout(() => {
            bsAlert.close();
        }, duration);
    },
    
    /**
     * Format a date string to a human-readable format
     * 
     * @param {string} dateString - Date string from the server
     * @param {string} format - Format (short, medium, long)
     * @returns {string} - Formatted date string
     */
    formatDate: (dateString, format = 'medium') => {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        
        // Check if date is valid
        if (isNaN(date.getTime())) return dateString;
        
        switch (format) {
            case 'short':
                return date.toLocaleDateString();
            case 'long':
                return date.toLocaleDateString(undefined, { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            case 'medium':
            default:
                return date.toLocaleDateString(undefined, { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
        }
    },
    
    /**
     * Format a number as currency
     * 
     * @param {number} amount - Amount to format
     * @param {string} currency - Currency code
     * @returns {string} - Formatted currency string
     */
    formatCurrency: (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
};

// Initialize the application when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap components
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    const popoverTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="popover"]')
    );
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Setup mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    }
    
    // Handle forms with AJAX submission
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitButton = form.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            }
            
            const formData = new FormData(form);
            const data = {};
            
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            const method = form.dataset.method || 'POST';
            const endpoint = form.dataset.endpoint;
            
            try {
                let response;
                
                switch (method.toUpperCase()) {
                    case 'PUT':
                        response = await api.put(endpoint, data);
                        break;
                    case 'DELETE':
                        response = await api.delete(endpoint);
                        break;
                    case 'POST':
                    default:
                        response = await api.post(endpoint, data);
                        break;
                }
                
                if (response.success) {
                    ui.showNotification(
                        form.dataset.successMessage || 'Operation completed successfully', 
                        'success'
                    );
                    
                    // Handle redirect if specified
                    if (form.dataset.redirect) {
                        window.location.href = form.dataset.redirect;
                        return;
                    }
                    
                    // Handle callback if specified
                    if (form.dataset.callback && window[form.dataset.callback]) {
                        window[form.dataset.callback](response);
                    }
                    
                    // Reset form if specified
                    if (form.dataset.reset === 'true') {
                        form.reset();
                    }
                } else {
                    ui.showNotification(response.message || 'Operation failed', 'danger');
                }
            } catch (error) {
                ui.showNotification('An unexpected error occurred', 'danger');
                console.error('Form submission error:', error);
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.dataset.originalText || 'Submit';
                }
            }
        });
        
        // Save original button text for restoration
        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.dataset.originalText = submitButton.innerHTML;
        }
    });
    
    // Handle delete confirmation
    const deleteButtons = document.querySelectorAll('[data-action="delete"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                return;
            }
            
            const endpoint = button.dataset.endpoint;
            const redirectUrl = button.dataset.redirect;
            
            try {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                
                const response = await api.delete(endpoint);
                
                if (response.success) {
                    ui.showNotification('Item deleted successfully', 'success');
                    
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        // If no redirect, remove the element from the DOM
                        const container = button.closest('[data-item-container]');
                        if (container) {
                            container.remove();
                        }
                    }
                } else {
                    ui.showNotification(response.message || 'Failed to delete item', 'danger');
                    button.disabled = false;
                    button.innerHTML = button.dataset.originalText || 'Delete';
                }
            } catch (error) {
                ui.showNotification('An unexpected error occurred', 'danger');
                console.error('Delete operation error:', error);
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || 'Delete';
            }
        });
        
        // Save original button text
        button.dataset.originalText = button.innerHTML;
    });
});

// Export utilities for use in other scripts
window.api = api;
window.ui = ui;
