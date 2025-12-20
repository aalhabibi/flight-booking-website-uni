// Flight Booking System - Main JavaScript

const API_BASE = 'services';
// Frontend-only mock mode: set true to disable backend calls and use sample data
const MOCK_MODE = true;

function _resolved(data) {
    return $.Deferred().resolve(data).promise();
}

function _rejected(err) {
    return $.Deferred().reject(err).promise();
}

function mockResponse(url, method = 'GET', data = null) {
    // simple route matching
    if (url.indexOf('auth/login.php') !== -1) {
        return _resolved({ success: true, message: 'Login (mock)', data: { user_type: 'passenger', email: data.email || 'mock@example.com' } });
    }
    if (url.indexOf('auth/register.php') !== -1) {
        return _resolved({ success: true, message: 'Registered (mock)', data: { user_type: data.user_type || 'passenger' } });
    }
    if (url.indexOf('profile/get.php') !== -1) {
        return _resolved({ success: true, data: { user_type: 'passenger', name: 'Mock User', email: 'mock@example.com' } });
    }
    if (url.indexOf('messages/get.php') !== -1) {
        return _resolved({ success: true, data: [ { id:1, message: 'Hello from company', is_me: false }, { id:2, message: 'Thanks, I will book', is_me: true } ] });
    }
    if (url.indexOf('company/getFlights.php') !== -1 || url.indexOf('passenger/searchFlights.php') !== -1) {
        return _resolved({ success: true, data: [ { flight_id: 1, flight_name: 'AC101', from_city: 'New York', to_city: 'Los Angeles', fees: 250 } ] });
    }
    if (url.indexOf('company/getFlightDetails.php') !== -1 || url.indexOf('passenger/getFlightInfo.php') !== -1) {
        return _resolved({ success: true, data: { flight_id: data && data.flight_id ? data.flight_id : 1, flight_name: 'AC101', fees: 250, company_id: 1, itinerary: [ { city: 'New York', start_datetime: '2025-01-01 08:00', end_datetime: '2025-01-01 09:30' } ] } });
    }
    // default mock
    return _resolved({ success: true, data: {} });
}

// Helper function to show alerts
function showAlert(message, type = 'info') {
    const alertDiv = $('<div>').addClass(`alert alert-${type}`).text(message);
    $('body').prepend(alertDiv);
    
    setTimeout(() => {
        alertDiv.fadeOut(() => alertDiv.remove());
    }, 5000);
}

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString();
}

// Helper function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// API Call Helper
function apiCall(url, method = 'GET', data = null) {
    if (MOCK_MODE) {
        // when mocking, pass the raw endpoint path so mockResponse can match
        return mockResponse(url, method, data);
    }

    const options = {
        url: `${API_BASE}/${url}`,
        method: method,
        dataType: 'json',
        contentType: 'application/json',
        xhrFields: {
            withCredentials: true
        }
    };
    
    if (data) {
        if (method === 'POST' || method === 'PUT') {
            options.data = JSON.stringify(data);
        } else {
            options.data = data;
        }
    }
    
    return $.ajax(options);
}

// Check if user is logged in
function checkAuth() {
    if (MOCK_MODE) return mockResponse('profile/get.php', 'GET');
    return $.ajax({
        url: `${API_BASE}/profile/get.php`,
        method: 'GET',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        }
    });
}

// Redirect if not authenticated
function requireAuth() {
    checkAuth().fail(() => {
        window.location.href = 'login.html';
    });
}

// Register function
function register(data, additionalData = null) {
    if (MOCK_MODE) {
        // return mocked successful registration
        return mockResponse('auth/register.php', 'POST', data);
    }

    const formData = new FormData();
    
    // Add basic fields
    formData.append('email', data.email);
    formData.append('username', data.username);
    formData.append('password', data.password);
    formData.append('name', data.name);
    formData.append('tel', data.tel);
    formData.append('user_type', data.user_type);
    
    // Add additional data based on user type
    if (additionalData) {
        if (data.user_type === 'company') {
            if (additionalData.bio) formData.append('bio', additionalData.bio);
            if (additionalData.address) formData.append('address', additionalData.address);
            if (additionalData.location) formData.append('location', additionalData.location);
            if (additionalData.logo && additionalData.logo.files[0]) {
                formData.append('logo', additionalData.logo.files[0]);
            }
        } else {
            if (additionalData.photo && additionalData.photo.files[0]) {
                formData.append('photo', additionalData.photo.files[0]);
            }
            if (additionalData.passport_img && additionalData.passport_img.files[0]) {
                formData.append('passport_img', additionalData.passport_img.files[0]);
            }
        }
    }
    // debug: list FormData keys and file names
    try {
        if (window.DEBUG_REGISTRATION) {
            console.group('DEBUG register FormData');
            for (let pair of formData.entries()) {
                const key = pair[0];
                const val = pair[1];
                if (val instanceof File) {
                    console.log(key + ': File —', val.name, val.type, val.size);
                } else {
                    console.log(key + ':', val);
                }
            }
            console.groupEnd();
        }
    } catch (e) {
        console.warn('FormData debug failed', e);
    }

    return $.ajax({
        url: `${API_BASE}/auth/register.php`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        }
    });
}

// Login function
function login(email, password) {
    return apiCall('auth/login.php', 'POST', {
        email: email,
        password: password
    });
}

// Logout function
function logout() {
    return apiCall('auth/logout.php', 'POST').done(() => {
        window.location.href = 'login.html';
    });
}

// Get company flights
function getCompanyFlights() {
    return apiCall('company/getFlights.php', 'GET');
}

// Get flight details
function getFlightDetails(flightId) {
    return apiCall('company/getFlightDetails.php', 'GET', { flight_id: flightId });
}

// Add flight
function addFlight(flightData) {
    return apiCall('company/addFlight.php', 'POST', flightData);
}

// Cancel flight
function cancelFlight(flightId) {
    return apiCall('company/cancelFlight.php', 'POST', { flight_id: flightId });
}

// Get profile
function getProfile() {
    return apiCall('profile/get.php', 'GET');
}

// Update profile
function updateProfile(data) {
    const formData = new FormData();
    
    Object.keys(data).forEach(key => {
        if (key === 'logo' || key === 'photo' || key === 'passport_img') {
            if (data[key] && data[key].files && data[key].files[0]) {
                formData.append(key, data[key].files[0]);
            }
        } else if (data[key] !== null && data[key] !== undefined) {
            formData.append(key, data[key]);
        }
    });
    
    return $.ajax({
        url: `${API_BASE}/profile/update.php`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        }
    });
}

// Get messages
function getMessages(withUserId = null) {
    const params = withUserId ? { with_user_id: withUserId } : {};
    return apiCall('messages/get.php', 'GET', params);
}

// Send message
function sendMessage(receiverId, message) {
    return apiCall('messages/send.php', 'POST', {
        receiver_id: receiverId,
        message: message
    });
}

// Initialize on page load
$(document).ready(function() {
    // Removed global form submit preventer to allow individual form handlers to work
    
    // Close modal on outside click
    $('.modal').on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            $(this).removeClass('active');
        }
    });
    
    // Close modal on close button
    $('.modal-close').on('click', function() {
        $(this).closest('.modal').removeClass('active');
    });
});

