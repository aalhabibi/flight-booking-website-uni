// API Configuration
const API_CONFIG = {

  //changed to suit my local host
  BASE_URL: 'http://localhost:8000/Backend',
  ENDPOINTS: {
    // Auth
    LOGIN: "/services/auth/login.php",
    REGISTER: "/services/auth/register.php",
    LOGOUT: "/services/auth/logout.php",

    // Passenger
    SEARCH_FLIGHTS: "/services/passenger/searchFlights.php",
    TAKE_FLIGHT: "/services/passenger/takeFlight.php",
    GET_MY_FLIGHTS: "/services/passenger/getMyFlights.php",
    GET_FLIGHT_INFO: "/services/passenger/getFlightInfo.php",

    // Company
    ADD_FLIGHT: "/services/company/addFlight.php",
    UPDATE_FLIGHT: "/services/company/updateFlight.php",
    CANCEL_FLIGHT: "/services/company/cancelFlight.php",
    GET_FLIGHTS: "/services/company/getFlights.php",
    GET_FLIGHT_DETAILS: "/services/company/getFlightDetails.php",

    // Profile
    GET_PROFILE: "/services/profile/get.php",
    UPDATE_PROFILE: "/services/profile/update.php",

    // Messages
    SEND_MESSAGE: "/services/messages/send.php",
    GET_MESSAGES: "/services/messages/get.php",
  },
};

// Session storage keys
const SESSION_KEYS = {
  USER_DATA: "skybooker_user_data",
  USER_TYPE: "skybooker_user_type",
  USER_ID: "skybooker_user_id",
};
