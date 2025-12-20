// API Helper Functions

class API {
  static async request(endpoint, options = {}) {
    const url = API_CONFIG.BASE_URL + endpoint;

    const defaultOptions = {
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
    };

    const config = { ...defaultOptions, ...options };

    try {
      const response = await fetch(url, config);
      const data = await response.json();

      return {
        success: data.success,
        message: data.message,
        data: data.data || null,
        status: response.status,
      };
    } catch (error) {
      console.error("API Request Error:", error);
      return {
        success: false,
        message: "Network error. Please check your connection.",
        data: null,
        status: 0,
      };
    }
  }

  static async get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;

    return this.request(url, {
      method: "GET",
    });
  }

  static async post(endpoint, data) {
    return this.request(endpoint, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  static async postFormData(endpoint, formData) {
    const url = API_CONFIG.BASE_URL + endpoint;

    try {
      const response = await fetch(url, {
        method: "POST",
        body: formData,
        credentials: "include",
      });

      const data = await response.json();

      return {
        success: data.success,
        message: data.message,
        data: data.data || null,
        status: response.status,
      };
    } catch (error) {
      console.error("API Request Error:", error);
      return {
        success: false,
        message: "Network error. Please check your connection.",
        data: null,
        status: 0,
      };
    }
  }

  // Auth APIs
  static async login(email, password) {
    return this.post(API_CONFIG.ENDPOINTS.LOGIN, { email, password });
  }

  static async register(formData) {
    return this.postFormData(API_CONFIG.ENDPOINTS.REGISTER, formData);
  }

  static async logout() {
    return this.post(API_CONFIG.ENDPOINTS.LOGOUT, {});
  }

  // Passenger APIs
  static async searchFlights(from = null, to = null) {
    const params = {};
    if (from) params.from = from;
    if (to) params.to = to;
    return this.get(API_CONFIG.ENDPOINTS.SEARCH_FLIGHTS, params);
  }

  static async bookFlight(flightId, paymentMethod) {
    return this.post(API_CONFIG.ENDPOINTS.TAKE_FLIGHT, {
      flight_id: flightId,
      payment_method: paymentMethod,
    });
  }

  static async getMyFlights() {
    return this.get(API_CONFIG.ENDPOINTS.GET_MY_FLIGHTS);
  }

  static async getFlightInfo(flightId) {
    return this.get(API_CONFIG.ENDPOINTS.GET_FLIGHT_INFO, {
      flight_id: flightId,
    });
  }

  // Company APIs
  static async addFlight(flightData) {
    return this.post(API_CONFIG.ENDPOINTS.ADD_FLIGHT, flightData);
  }

  static async updateFlight(flightId, flightData) {
    return this.post(API_CONFIG.ENDPOINTS.UPDATE_FLIGHT, {
      ...flightData,
      flight_id: flightId,
    });
  }

  static async cancelFlight(flightId) {
    return this.post(API_CONFIG.ENDPOINTS.CANCEL_FLIGHT, {
      flight_id: flightId,
    });
  }

  static async getFlights() {
    return this.get(API_CONFIG.ENDPOINTS.GET_FLIGHTS);
  }

  static async getFlightDetails(flightId) {
    return this.get(API_CONFIG.ENDPOINTS.GET_FLIGHT_DETAILS, {
      flight_id: flightId,
    });
  }

  // Profile APIs
  static async getProfile() {
    return this.get(API_CONFIG.ENDPOINTS.GET_PROFILE);
  }

  static async updateProfile(formData) {
    return this.postFormData(API_CONFIG.ENDPOINTS.UPDATE_PROFILE, formData);
  }

  // Message APIs
  static async sendMessage(receiverId, message) {
    return this.post(API_CONFIG.ENDPOINTS.SEND_MESSAGE, {
      receiver_id: receiverId,
      message: message,
    });
  }

  static async getMessages(userId = null) {
    const params = userId ? { with_user_id: userId } : {};
    return this.get(API_CONFIG.ENDPOINTS.GET_MESSAGES, params);
  }
}

// Session Management
class Session {
  static setUserData(userData) {
    localStorage.setItem(SESSION_KEYS.USER_DATA, JSON.stringify(userData));
    localStorage.setItem(SESSION_KEYS.USER_TYPE, userData.user_type);
    localStorage.setItem(SESSION_KEYS.USER_ID, userData.user_id);
  }

  static getUserData() {
    const data = localStorage.getItem(SESSION_KEYS.USER_DATA);
    return data ? JSON.parse(data) : null;
  }

  static getUserType() {
    return localStorage.getItem(SESSION_KEYS.USER_TYPE);
  }

  static getUserId() {
    return localStorage.getItem(SESSION_KEYS.USER_ID);
  }

  static isLoggedIn() {
    return this.getUserData() !== null;
  }

  static clear() {
    localStorage.removeItem(SESSION_KEYS.USER_DATA);
    localStorage.removeItem(SESSION_KEYS.USER_TYPE);
    localStorage.removeItem(SESSION_KEYS.USER_ID);
  }

  static requireAuth() {
    if (!this.isLoggedIn()) {
      window.location.href = "login.html";
      return false;
    }
    return true;
  }

  static requireUserType(type) {
    if (!this.requireAuth()) return false;

    if (this.getUserType() !== type) {
      alert("Access denied. Insufficient permissions.");
      window.location.href = "index.html";
      return false;
    }
    return true;
  }
}

// Utility Functions
class Utils {
  static showAlert(elementId, message, type = "error") {
    const alertElement = $(`#${elementId}`);
    alertElement.removeClass(
      "alert-success alert-error alert-warning alert-info"
    );
    alertElement.addClass(`alert-${type}`);
    alertElement.html(message);
    alertElement.fadeIn();

    setTimeout(() => {
      alertElement.fadeOut();
    }, 5000);
  }

  static showError(elementId, message) {
    this.showAlert(elementId, message, "error");
  }

  static showSuccess(elementId, message) {
    this.showAlert(elementId, message, "success");
  }

  static clearErrors() {
    $(".error-text").text("");
  }

  static showFieldError(fieldName, message) {
    $(`#${fieldName}-error`).text(message);
  }

  static formatCurrency(amount) {
    return `$${parseFloat(amount).toFixed(2)}`;
  }

  static formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  static formatDate(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }

  static showLoading(buttonId) {
    const btn = $(`#${buttonId}`);
    btn.find(".btn-text").hide();
    btn.find(".btn-loader").show();
    btn.prop("disabled", true);
  }

  static hideLoading(buttonId) {
    const btn = $(`#${buttonId}`);
    btn.find(".btn-text").show();
    btn.find(".btn-loader").hide();
    btn.prop("disabled", false);
  }

  static openModal(modalId) {
    $(`#${modalId}`).addClass("show");
  }

  static closeModal(modalId) {
    $(`#${modalId}`).removeClass("show");
  }

  static confirmAction(message) {
    return confirm(message);
  }

  static validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  static validatePhone(phone) {
    const re = /^[\d\s\-\+\(\)]+$/;
    return re.test(phone) && phone.replace(/\D/g, "").length >= 10;
  }
}
