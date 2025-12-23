// Company Dashboard JavaScript

let currentConversation = null;
let itineraryCount = 0;

// Convert datetime-local format (2024-12-20T14:30) to MySQL format (2024-12-20 14:30:00)
function convertToMySQLDateTime(datetimeLocal) {
  if (!datetimeLocal) return "";
  // Replace T with space and add :00 for seconds
  return datetimeLocal.replace("T", " ") + ":00";
}

$(document).ready(function () {
  // Check authentication
  if (!Session.requireUserType("company")) {
    return;
  }

  initDashboard();
  initFlights();
  initBookings();
  initMessages();
  initProfile();
  initModals();

  // Load initial data
  loadUserInfo();
  loadFlights();
});

function initDashboard() {
  const userData = Session.getUserData();
  $("#userName").text(userData.name);
  updateBalance(userData.account_balance);

  // Sidebar navigation
  $(".sidebar-menu li").on("click", function () {
    $(".sidebar-menu li").removeClass("active");
    $(this).addClass("active");

    const section = $(this).data("section");
    $(".dashboard-section").removeClass("active");
    $(`#${section}Section`).addClass("active");

    // Load section-specific data
    if (section === "flights") {
      loadFlights();
    } else if (section === "bookings") {
      loadBookings();
    } else if (section === "messages") {
      loadConversations();
    } else if (section === "profile") {
      loadProfile();
    }
  });

  // Logout
  $("#logoutBtn").on("click", async function () {
    if (confirm("Are you sure you want to logout?")) {
      await API.logout();
      Session.clear();
      window.location.href = "index.html";
    }
  });
}

function initFlights() {
  // Add flight button
  $("#addFlightBtn").on("click", function () {
    openFlightModal();
  });

  // View flight details
  $(document).on("click", ".view-company-flight-btn", async function () {
    const flightId = $(this).data("flight-id");
    await viewFlightDetails(flightId);
  });

  // Edit flight
  $(document).on("click", ".edit-flight-btn", async function () {
    const flightId = $(this).data("flight-id");
    await editFlight(flightId);
  });

  // Cancel flight
  $(document).on("click", ".cancel-flight-btn", async function () {
    const flightId = $(this).data("flight-id");
    await cancelFlight(flightId);
  });
}

async function loadFlights() {
  $("#flightsList").html('<div class="loading">Loading flights...</div>');

  try {
    const response = await API.getFlights();

    if (response.success) {
      displayFlights(response.data.flights);
    } else {
      $("#flightsList").html(
        `<div class="empty-state"><p>${response.message}</p></div>`
      );
    }
  } catch (error) {
    console.error("Load flights error:", error);
    $("#flightsList").html(
      '<div class="empty-state"><p>Error loading flights</p></div>'
    );
  }
}

function displayFlights(flights) {
  const container = $("#flightsList");
  container.empty();

  if (!flights || !Array.isArray(flights) || flights.length === 0) {
    container.html(
      '<div class="empty-state"><i class="icon-plane"></i><p>No flights yet. Create your first flight!</p></div>'
    );
    return;
  }

  flights.forEach((flight) => {
    const card = createCompanyFlightCard(flight);
    container.append(card);
  });
}

function createCompanyFlightCard(flight) {
  // Extract origin/destination from itinerary
  const departureCity =
    flight.itinerary && flight.itinerary[0] ? flight.itinerary[0].city : null;
  const arrivalCity =
    flight.itinerary && flight.itinerary.length > 1
      ? flight.itinerary[flight.itinerary.length - 1].city
      : null;
  const departureTime =
    flight.itinerary && flight.itinerary[0]
      ? flight.itinerary[0].start_datetime
      : null;
  const arrivalTime =
    flight.itinerary && flight.itinerary.length > 1
      ? flight.itinerary[flight.itinerary.length - 1].end_datetime
      : null;

  return $(`
        <div class="flight-card">
            <div class="flight-header">
                <div>
                    <div class="flight-title">${flight.flight_name}</div>
                    <div class="flight-code">${flight.flight_code}</div>
                </div>
                <div class="flight-price">${Utils.formatCurrency(
                  flight.fees
                )}</div>
            </div>
            ${
              departureCity && arrivalCity
                ? `
            <div class="flight-route">
                <div class="route-point">
                    <div class="route-city">${departureCity}</div>
                    ${
                      departureTime
                        ? `<div class="route-time">${Utils.formatDate(
                            departureTime
                          )}</div>`
                        : ""
                    }
                </div>
                <div class="route-arrow">
                    <div class="route-line"></div>
                </div>
                <div class="route-point">
                    <div class="route-city">${arrivalCity}</div>
                    ${
                      arrivalTime
                        ? `<div class="route-time">${Utils.formatDate(
                            arrivalTime
                          )}</div>`
                        : ""
                    }
                </div>
            </div>
            `
                : ""
            }
            <div class="flight-info">
                <div class="info-item">
                    <span class="info-label">Passengers</span>
                    <span class="info-value">${
                      flight.registered_passengers
                    } / ${flight.max_passengers}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="status-badge status-${flight.status}">${
    flight.status
  }</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Revenue</span>
                    <span class="info-value">${Utils.formatCurrency(
                      flight.registered_passengers * flight.fees
                    )}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Created</span>
                    <span class="info-value">${Utils.formatDate(
                      flight.created_at
                    )}</span>
                </div>
            </div>
            <div class="flight-actions">
                <button class="btn btn-outline btn-sm view-company-flight-btn" data-flight-id="${
                  flight.id
                }">
                    View Details
                </button>
                ${
                  flight.status === "pending"
                    ? `
                    <button class="btn btn-secondary btn-sm edit-flight-btn" data-flight-id="${flight.id}">
                        Edit
                    </button>
                    <button class="btn btn-danger btn-sm cancel-flight-btn" data-flight-id="${flight.id}">
                        Cancel
                    </button>
                `
                    : ""
                }
            </div>
        </div>
    `);
}

async function viewFlightDetails(flightId) {
  Utils.openModal("viewFlightModal");
  $("#viewFlightBody").html('<div class="loading">Loading...</div>');

  try {
    const response = await API.getFlightDetails(flightId);

    if (response.success) {
      const flight = response.data;
      $("#viewFlightBody").html(createFlightDetailsHTML(flight));
    } else {
      $("#viewFlightBody").html("<p>Error loading flight details</p>");
    }
  } catch (error) {
    console.error("Load flight details error:", error);
    $("#viewFlightBody").html("<p>Error loading flight details</p>");
  }
}

function createFlightDetailsHTML(flight) {
  let itineraryHTML = '<div class="itinerary-route">';
  if (flight.itinerary && flight.itinerary.length > 0) {
    flight.itinerary.forEach((stop, index) => {
      itineraryHTML += `
                <div class="route-item">
                    <span class="route-number">${index + 1}</span>
                    <span class="route-city">${stop.city}</span>
                    <span class="route-times">
                        ${Utils.formatDateTime(
                          stop.start_datetime
                        )} - ${Utils.formatDateTime(stop.end_datetime)}
                    </span>
                </div>
            `;
    });
  }
  itineraryHTML += "</div>";

  return `
        <div class="booking-summary">
            <h3>${flight.flight_name} (${flight.flight_code})</h3>
            <div class="summary-row">
                <span>Price:</span>
                <span>${Utils.formatCurrency(flight.fees)}</span>
            </div>
            <div class="summary-row">
                <span>Max Passengers:</span>
                <span>${flight.max_passengers}</span>
            </div>
            <div class="summary-row">
                <span>Registered:</span>
                <span>${flight.registered_passengers.length}</span>
            </div>
            <div class="summary-row">
                <span>Available:</span>
                <span>${
                  flight.max_passengers - flight.registered_passengers.length
                }</span>
            </div>
            <div class="summary-row">
                <span>Status:</span>
                <span class="status-badge status-${flight.status}">${
    flight.status
  }</span>
            </div>
            <div class="summary-row">
                <span>Total Revenue:</span>
                <span>${Utils.formatCurrency(
                  flight.registered_passengers.length * flight.fees
                )}</span>
            </div>
        </div>
        <h4>Flight Route</h4>
        ${itineraryHTML}
    `;
}

async function editFlight(flightId) {
  try {
    const response = await API.getFlightDetails(flightId);

    if (response.success) {
      const flight = response.data;
      openFlightModal(flight);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error loading flight for editing");
  }
}

async function cancelFlight(flightId) {
  if (
    !confirm(
      "Are you sure you want to cancel this flight? This action cannot be undone."
    )
  ) {
    return;
  }

  try {
    const response = await API.cancelFlight(flightId);

    if (response.success) {
      alert("Flight cancelled successfully!");
      loadFlights();
    } else {
      alert(response.message);
    }
  } catch (error) {
    console.error("Cancel flight error:", error);
    alert("Error cancelling flight");
  }
}

function initBookings() {
  // Message passenger
  $(document).on("click", ".message-passenger-btn", function () {
    const passengerId = $(this).data("passenger-id");
    const passengerName = $(this).data("passenger-name");

    // Switch to messages section
    $('.sidebar-menu li[data-section="messages"]').click();

    // Open conversation with passenger
    setTimeout(() => {
      openConversation(passengerId, passengerName);
    }, 300);
  });
}

async function loadBookings() {
  $("#bookingsList").html('<div class="loading">Loading bookings...</div>');

  try {
    const response = await API.getFlights();

    if (response.success && response.data && response.data.flights) {
      // Get all flights and their bookings
      displayAllBookings(response.data.flights);
    } else {
      $("#bookingsList").html(
        '<div class="empty-state"><p>No bookings yet</p></div>'
      );
    }
  } catch (error) {
    console.error("Load bookings error:", error);
    $("#bookingsList").html(
      '<div class="empty-state"><p>Error loading bookings</p></div>'
    );
  }
}

function displayAllBookings(flights) {
  const container = $("#bookingsList");
  container.empty();

  let hasBookings = false;

  flights.forEach((flight) => {
    if (flight.bookings && flight.bookings.length > 0) {
      hasBookings = true;
      flight.bookings.forEach((booking) => {
        const card = createBookingCard(flight, booking);
        container.append(card);
      });
    }
  });

  if (!hasBookings) {
    container.html(
      '<div class="empty-state"><i class="icon-ticket"></i><p>No bookings yet</p></div>'
    );
  }
}

function createBookingCard(flight, booking) {
  // Extract origin/destination from itinerary
  const departureCity =
    flight.itinerary && flight.itinerary[0] ? flight.itinerary[0].city : null;
  const arrivalCity =
    flight.itinerary && flight.itinerary.length > 1
      ? flight.itinerary[flight.itinerary.length - 1].city
      : null;
  const departureTime =
    flight.itinerary && flight.itinerary[0]
      ? flight.itinerary[0].start_datetime
      : null;
  const arrivalTime =
    flight.itinerary && flight.itinerary.length > 1
      ? flight.itinerary[flight.itinerary.length - 1].end_datetime
      : null;

  // Use correct passenger name field
  const passengerName = booking.name || booking.passenger_name || "Passenger";

  return $(`
    <div class="booking-card">
      <div class="booking-header">
        <div>
          <div class="flight-title">${flight.flight_name}</div>
          <div class="flight-code">${flight.flight_code}</div>
        </div>
        <span class="status-badge status-${booking.booking_status}">${
    booking.booking_status
  }</span>
      </div>
      ${
        departureCity && arrivalCity
          ? `
      <div class="flight-route">
        <div class="route-point">
          <div class="route-city">${departureCity}</div>
          ${
            departureTime
              ? `<div class="route-time">${Utils.formatDate(
                  departureTime
                )}</div>`
              : ""
          }
        </div>
        <div class="route-arrow">
          <div class="route-line"></div>
        </div>
        <div class="route-point">
          <div class="route-city">${arrivalCity}</div>
          ${
            arrivalTime
              ? `<div class="route-time">${Utils.formatDate(arrivalTime)}</div>`
              : ""
          }
        </div>
      </div>
      `
          : ""
      }
      <div class="booking-info">
        <div class="info-item">
          <span class="info-label">Passenger</span>
          <span class="info-value">${booking.name}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Booking Date</span>
          <span class="info-value">${Utils.formatDate(
            booking.booking_date
          )}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Amount</span>
          <span class="info-value">${Utils.formatCurrency(
            booking.amount_paid
          )}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Payment</span>
          <span class="info-value">${
            booking.payment_method === "account" ? "Account" : "Cash"
          }</span>
        </div>
      </div>
      <div class="booking-actions">
        <button class="btn btn-secondary btn-sm message-passenger-btn" 
                data-passenger-id="${booking.passenger_id}" 
                data-passenger-name="${passengerName}">
          Message Passenger
        </button>
      </div>
    </div>
  `);
}

function initMessages() {
  // Send message form
  $("#sendMessageForm").on("submit", async function (e) {
    e.preventDefault();

    const receiverId = $("#receiverId").val();
    const message = $("#messageText").val().trim();

    if (!message) return;

    try {
      const response = await API.sendMessage(receiverId, message);

      if (response.success) {
        $("#messageText").val("");
        loadMessages(receiverId);
      } else {
        alert(response.message);
      }
    } catch (error) {
      console.error("Send message error:", error);
      alert("Error sending message");
    }
  });

  // Load conversations on click
  $(document).on("click", ".conversation-item", function () {
    const userId = $(this).data("user-id");
    const userName = $(this).data("user-name");

    $(".conversation-item").removeClass("active");
    $(this).addClass("active");

    openConversation(userId, userName);
  });
}

async function loadConversations() {
  $("#conversationsList").html('<div class="loading">Loading...</div>');

  try {
    const response = await API.getMessages();

    if (response.success && response.data && response.data.conversations) {
      displayConversations(response.data.conversations);
    } else {
      $("#conversationsList").html("<p>No conversations</p>");
    }
  } catch (error) {
    console.error("Load conversations error:", error);
  }
}

function displayConversations(conversations) {
  const container = $("#conversationsList");
  container.empty();

  if (
    !conversations ||
    !Array.isArray(conversations) ||
    conversations.length === 0
  ) {
    container.html(
      '<div style="padding: 1rem; text-align: center;">No messages yet</div>'
    );
    return;
  }

  conversations.forEach((conv) => {
    container.append(
      $(`
            <div class="conversation-item" data-user-id="${
              conv.other_user_id
            }" data-user-name="${conv.other_user_name}">
                <div class="conversation-name">${conv.other_user_name}</div>
                <div class="conversation-preview">${
                  conv.last_message || "No messages yet"
                }</div>
            </div>
        `)
    );
  });
}

function openConversation(userId, userName) {
  currentConversation = userId;
  $("#receiverId").val(userId);
  $("#activeConversationName").text(userName);
  $("#messageViewEmpty").hide();
  $("#messageViewActive").show();

  loadMessages(userId);
}

async function loadMessages(userId) {
  $("#messagesList").html('<div class="loading">Loading messages...</div>');

  try {
    const response = await API.getMessages(userId);

    if (response.success && response.data && response.data.messages) {
      displayMessages(response.data.messages);
    } else {
      $("#messagesList").html("<p>No messages</p>");
    }
  } catch (error) {
    console.error("Load messages error:", error);
  }
}

function displayMessages(messages) {
  const container = $("#messagesList");
  container.empty();

  if (!messages || !Array.isArray(messages) || messages.length === 0) {
    container.html('<div class="empty-state"><p>No messages yet</p></div>');
    return;
  }

  const currentUserId = Session.getUserId();

  messages.forEach((msg) => {
    const isSent = msg.sender_id == currentUserId;
    const messageClass = isSent ? "message-sent" : "message-received";

    container.append(
      $(`
            <div class="message-item ${messageClass}">
                <div>${msg.message}</div>
                <span class="message-time">${Utils.formatDateTime(
                  msg.sent_at
                )}</span>
            </div>
        `)
    );
  });

  // Scroll to bottom
  container.scrollTop(container[0].scrollHeight);
}

function initProfile() {
  // Profile form submission
  $("#profileForm").on("submit", async function (e) {
    e.preventDefault();

    const password = $("#profilePassword").val();
    const confirmPassword = $("#profileConfirmPassword").val();

    // Validate passwords if provided
    if (password || confirmPassword) {
      if (password !== confirmPassword) {
        alert("Passwords do not match");
        return;
      }
      if (password.length < 8) {
        alert("Password must be at least 8 characters");
        return;
      }
    }

    const formData = new FormData();
    formData.append("name", $("#profileFullName").val());
    formData.append("username", $("#profileUsername").val());
    formData.append("email", $("#profileEmail").val());
    formData.append("tel", $("#profileTel").val());
    formData.append("bio", $("#profileBio").val());
    formData.append("address", $("#profileAddress").val());
    formData.append("location", $("#profileLocation").val());

    // Add password if provided
    if (password) {
      formData.append("password", password);
      formData.append("confirm_password", confirmPassword);
    }

    const logo = $("#profileLogo")[0].files[0];
    if (logo) formData.append("logo", logo);

    try {
      const response = await API.updateProfile(formData);

      if (response.success) {
        alert("Profile updated successfully!");
        // Update session data
        const userData = Session.getUserData();
        userData.name = response.data.name;
        userData.email = response.data.email;
        Session.setUserData(userData);
        $("#userName").text(userData.name);

        // Clear password fields
        $("#profilePassword").val("");
        $("#profileConfirmPassword").val("");
      } else {
        alert(response.message);
      }
    } catch (error) {
      console.error("Update profile error:", error);
      alert("Error updating profile");
    }
  });
}

async function loadProfile() {
  try {
    const response = await API.getProfile();

    if (response.success) {
      const profile = response.data;

      $("#profileName").text(profile.name);
      $("#profileFullName").val(profile.name);
      $("#profileUsername").val(profile.username);
      $("#profileEmail").val(profile.email);
      $("#profileTel").val(profile.tel);
      $("#profileBio").val(profile.bio || "");
      $("#profileAddress").val(profile.address || "");
      $("#profileLocation").val(profile.location || "");
      $("#profileBalance").text(parseFloat(profile.account_balance).toFixed(2));

      if (profile.logo_path) {
        $("#profileAvatar").html(
          `<img src="${API_CONFIG.BASE_URL}/uploads/${profile.logo_path}" alt="Logo">`
        );
      }
    }
  } catch (error) {
    console.error("Load profile error:", error);
  }
}

function initModals() {
  // Add itinerary button
  $(document).on("click", "#addItineraryBtn", function () {
    addItineraryItem();
  });

  // Remove itinerary item
  $(document).on("click", ".btn-remove", function () {
    const itemCount = $("#itineraryItems .itinerary-item").length;
    if (itemCount <= 2) {
      alert(
        "You must have at least 2 cities (From and To). Cannot remove more."
      );
      return;
    }
    $(this).closest(".itinerary-item").remove();
    updateItineraryNumbers();
  });

  // Flight form submission
  $("#flightForm").on("submit", async function (e) {
    e.preventDefault();

    const flightId = $("#flightId").val();
    const flightData = collectFlightData();

    if (!flightData) return;

    console.log("Submitting flight data:", JSON.stringify(flightData, null, 2));

    Utils.showLoading("saveFlightBtn");

    try {
      let response;
      if (flightId) {
        response = await API.updateFlight(flightId, flightData);
      } else {
        response = await API.addFlight(flightData);
      }

      if (response.success) {
        alert(
          flightId
            ? "Flight updated successfully!"
            : "Flight added successfully!"
        );
        Utils.closeModal("flightModal");
        loadFlights();
      } else {
        alert(response.message);
      }
    } catch (error) {
      console.error("Save flight error:", error);
      alert("Error saving flight");
    } finally {
      Utils.hideLoading("saveFlightBtn");
    }
  });
}

function openFlightModal(flight = null) {
  $("#flightId").val("");
  $("#flightForm")[0].reset();
  $("#itineraryItems").empty();
  itineraryCount = 0;

  if (flight) {
    // Edit mode
    $("#flightModalTitle").text("Edit Flight");
    $("#flightId").val(flight.id);
    $("#flightName").val(flight.flight_name);
    $("#flightCode").val(flight.flight_code);
    $("#maxPassengers").val(flight.max_passengers);
    $("#fees").val(flight.fees);

    // Populate itinerary
    if (flight.itinerary && flight.itinerary.length > 0) {
      flight.itinerary.forEach((stop) => {
        addItineraryItem(stop);
      });
    } else {
      addItineraryItem();
    }
  } else {
    // Add mode - automatically add From and To cities
    $("#flightModalTitle").text("Add New Flight");
    addItineraryItem(); // From (Departure)
    addItineraryItem(); // To (Arrival)
  }

  Utils.openModal("flightModal");
}

function addItineraryItem(data = null) {
  itineraryCount++;
  const template = $("#itineraryTemplate").html();
  const item = $(template);

  // Update label based on position: From -> To -> Stop 1, Stop 2, etc.
  const currentCount = $("#itineraryItems .itinerary-item").length;
  let label;
  if (currentCount === 0) {
    label = "From (Departure)";
  } else if (currentCount === 1) {
    label = "To (Arrival)";
  } else {
    label = `Stop ${currentCount - 1} (Layover)`;
  }

  item.find(".itinerary-number").text(label);

  // Show/hide fields based on position
  // Note: We'll update this after adding to determine if it's the last stop
  if (currentCount === 0) {
    // First stop (Departure) - only show departure time
    item.find(".arrival-time-group").hide();
    item.find('input[name="start_datetime"]').removeAttr("required");
  }
  // All other stops initially show both fields

  if (data) {
    item.find('input[name="city"]').val(data.city);
    item
      .find('input[name="start_datetime"]')
      .val(formatDateTimeForInput(data.start_datetime));
    item
      .find('input[name="end_datetime"]')
      .val(formatDateTimeForInput(data.end_datetime));
  }

  $("#itineraryItems").append(item);

  // After adding, update all items to ensure the last one is set correctly
  updateItineraryFieldVisibility();

  // Add jQuery UI autocomplete to the newly added city field
  const popularCities = [
    "Cairo",
    "Alexandria",
    "Luxor",
    "Aswan",
    "Sharm El Sheikh",
    "Dubai",
    "London",
    "Paris",
    "New York",
    "Tokyo",
    "Berlin",
    "Rome",
    "Madrid",
    "Barcelona",
    "Amsterdam",
    "Istanbul",
    "Athens",
    "Vienna",
    "Prague",
    "Budapest",
    "Los Angeles",
    "San Francisco",
    "Chicago",
    "Miami",
    "Toronto",
  ];

  item.find('input[name="city"]').autocomplete({
    source: popularCities,
    minLength: 2,
    delay: 300,
    autoFocus: true,
  });
}

function updateItineraryNumbers() {
  $("#itineraryItems .itinerary-item").each(function (index) {
    let label;
    if (index === 0) {
      label = "From (Departure)";
    } else if (index === 1) {
      label = "To (Arrival)";
    } else {
      label = `Stop ${index - 1} (Layover)`;
    }
    $(this).find(".itinerary-number").text(label);
  });
  itineraryCount = $("#itineraryItems .itinerary-item").length;

  // Update field visibility
  updateItineraryFieldVisibility();
}

function updateItineraryFieldVisibility() {
  const totalItems = $("#itineraryItems .itinerary-item").length;

  $("#itineraryItems .itinerary-item").each(function (index) {
    const isFirst = index === 0;
    const isLast = index === totalItems - 1;

    if (isFirst) {
      // First stop - only departure time
      $(this).find(".arrival-time-group").hide();
      $(this).find(".departure-time-group").show();
      $(this).find('input[name="start_datetime"]').removeAttr("required");
      $(this).find('input[name="end_datetime"]').attr("required", "required");
    } else if (isLast) {
      // Last stop - only arrival time
      $(this).find(".arrival-time-group").show();
      $(this).find(".departure-time-group").hide();
      $(this).find('input[name="start_datetime"]').attr("required", "required");
      $(this).find('input[name="end_datetime"]').removeAttr("required");
    } else {
      // Middle/layover stops - both times
      $(this).find(".arrival-time-group").show();
      $(this).find(".departure-time-group").show();
      $(this).find('input[name="start_datetime"]').attr("required", "required");
      $(this).find('input[name="end_datetime"]').attr("required", "required");
    }
  });
}

function collectFlightData() {
  const flightName = $("#flightName").val().trim();
  const flightCode = $("#flightCode").val().trim().toUpperCase();
  const maxPassengers = parseInt($("#maxPassengers").val());
  const fees = parseFloat($("#fees").val());

  // Validate basic fields
  if (!flightName || !flightCode || !maxPassengers || !fees) {
    alert("Please fill in all required fields");
    return null;
  }

  if (maxPassengers < 1) {
    alert("Max passengers must be at least 1");
    return null;
  }

  if (fees < 0) {
    alert("Fees must be positive");
    return null;
  }

  // Collect itinerary
  const itinerary = [];
  let hasError = false;

  const totalItems = $("#itineraryItems .itinerary-item").length;

  $("#itineraryItems .itinerary-item").each(function (index) {
    const city = $(this).find('input[name="city"]').val().trim();
    const startDatetime = $(this).find('input[name="start_datetime"]').val();
    const endDatetime = $(this).find('input[name="end_datetime"]').val();

    // Determine label for error messages
    let stopLabel;
    if (index === 0) {
      stopLabel = "Departure city";
    } else if (index === totalItems - 1) {
      stopLabel = "Arrival city";
    } else {
      stopLabel = `Stop ${index} (Layover)`;
    }

    if (!city) {
      alert(`Please enter a city name for ${stopLabel}`);
      hasError = true;
      return false;
    }

    // Determine what times we need based on position
    const isFirstStop = index === 0;
    const isLastStop = index === totalItems - 1;
    const isLayover = !isFirstStop && !isLastStop;

    if (isFirstStop) {
      // First stop - only departure time required
      if (!endDatetime) {
        alert(`Please enter departure time for ${stopLabel}`);
        hasError = true;
        return false;
      }
    } else if (isLastStop) {
      // Last stop - only arrival time required
      if (!startDatetime) {
        alert(`Please enter arrival time for ${stopLabel}`);
        hasError = true;
        return false;
      }
    } else {
      // Layover stops - both times required
      if (!startDatetime || !endDatetime) {
        alert(`Please fill in all time fields for ${stopLabel}`);
        hasError = true;
        return false;
      }

      if (new Date(startDatetime) >= new Date(endDatetime)) {
        alert(`${stopLabel}: Departure time must be after arrival time`);
        hasError = true;
        return false;
      }
    }

    // Determine final datetime values to send to backend
    let finalStartDatetime, finalEndDatetime;

    if (isFirstStop) {
      // First stop - only departure time, send null for arrival
      finalStartDatetime = null;
      finalEndDatetime = convertToMySQLDateTime(endDatetime);
    } else if (isLastStop) {
      // Last stop - only arrival time, send null for departure
      finalStartDatetime = convertToMySQLDateTime(startDatetime);
      finalEndDatetime = null;
    } else {
      // Layover stops - has both times
      finalStartDatetime = convertToMySQLDateTime(startDatetime);
      finalEndDatetime = convertToMySQLDateTime(endDatetime);
    }

    // Check time sequence with previous stop
    if (itinerary.length > 0) {
      const prevStop = itinerary[itinerary.length - 1];
      const prevDeparture = new Date(prevStop.end_datetime);
      const currArrival = new Date(finalStartDatetime);

      if (currArrival < prevDeparture) {
        alert(
          `${stopLabel}: Arrival time must be after previous departure time`
        );
        hasError = true;
        return false;
      }
    }

    itinerary.push({
      city: city,
      start_datetime: finalStartDatetime,
      end_datetime: finalEndDatetime,
      sequence_order: index,
    });
  });

  if (hasError) return null;

  if (itinerary.length < 2) {
    alert(
      "Please add at least 2 stops to the itinerary (departure and arrival)"
    );
    return null;
  }

  return {
    flight_name: flightName,
    flight_code: flightCode,
    max_passengers: maxPassengers,
    fees: fees,
    itinerary: itinerary,
  };
}

function formatDateTimeForInput(datetime) {
  if (!datetime) return "";
  const date = new Date(datetime);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

async function loadUserInfo() {
  try {
    const response = await API.getProfile();
    if (response.success) {
      const userData = Session.getUserData();
      userData.account_balance = response.data.account_balance;
      Session.setUserData(userData);
      updateBalance(response.data.account_balance);
    }
  } catch (error) {
    console.error("Error loading user info:", error);
  }
}

function updateBalance(balance) {
  $("#userBalance").text(parseFloat(balance).toFixed(2));
  $("#profileBalance").text(parseFloat(balance).toFixed(2));
}
