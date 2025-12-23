// Passenger Dashboard JavaScript

let currentFlight = null;
let currentConversation = null;

$(document).ready(function () {
  // Check authentication
  if (!Session.requireUserType("passenger")) {
    return;
  }

  initDashboard();
  initSearch();
  initBookings();
  initMessages();
  initProfile();
  initModals();

  // Load initial data
  loadUserInfo();
  loadMyBookings();
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
    if (section === "bookings") {
      loadMyBookings();
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

function initSearch() {
  // Common cities for autocomplete (jQuery UI)
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

  // Initialize jQuery UI Autocomplete on city fields
  $("#fromCity, #toCity").autocomplete({
    source: popularCities,
    minLength: 2,
    delay: 300,
    autoFocus: true,
    select: function (event, ui) {
      $(this).val(ui.item.value);
    },
  });

  // Search form
  $("#searchForm").on("submit", async function (e) {
    e.preventDefault();

    const from = $("#fromCity").val().trim();
    const to = $("#toCity").val().trim();

    await searchFlights(from, to);
  });

  // Clear search
  $("#clearSearch").on("click", function () {
    $("#searchForm")[0].reset();
    $("#searchResults").empty();
  });
}

async function searchFlights(from, to) {
  $("#searchResults").html('<div class="loading">Searching flights...</div>');

  try {
    const response = await API.searchFlights(from, to);

    if (response.success) {
      displaySearchResults(response.data.flights);
    } else {
      $("#searchResults").html(
        `<div class="empty-state"><p>${response.message}</p></div>`
      );
    }
  } catch (error) {
    console.error("Search error:", error);
    $("#searchResults").html(
      '<div class="empty-state"><p>Error loading flights</p></div>'
    );
  }

  $(document).on("click", ".message-company-btn", function () {
    const companyId = $(this).data("company-id");
    const companyName = $(this).data("company-name");

    // Switch to messages section
    $('.sidebar-menu li[data-section="messages"]').click();

    // Open conversation with company
    setTimeout(() => {
      openConversation(companyId, companyName);
    }, 300);
  });
}

function displaySearchResults(flights) {
  const container = $("#searchResults");
  container.empty();

  if (!flights || flights.length === 0) {
    container.html(
      '<div class="empty-state"><i class="icon-search"></i><p>No flights found</p></div>'
    );
    return;
  }

  flights.forEach((flight) => {
    const card = createFlightCard(flight);
    container.append(card);
  });
}

function createFlightCard(flight) {
  return $(`
        <div class="flight-card">
            <div class="flight-header">
                <div>
                    <div class="flight-title">${flight.flight_name}</div>
                    <div class="flight-code">${flight.flight_code}</div>
                    <div class="info-value">${flight.company_name}</div>
                </div>
                <div class="flight-price">${Utils.formatCurrency(
                  flight.fees
                )}</div>
            </div>
            ${
              flight.departure_city && flight.arrival_city
                ? `
            <div class="flight-route">
                <div class="route-point">
                    <div class="route-city">${flight.departure_city}</div>
                    ${
                      flight.departure_time
                        ? `<div class="route-time">${Utils.formatDate(
                            flight.departure_time
                          )}</div>`
                        : ""
                    }
                </div>
                <div class="route-arrow">
                    <div class="route-line"></div>
                </div>
                <div class="route-point">
                    <div class="route-city">${flight.arrival_city}</div>
                    ${
                      flight.arrival_time
                        ? `<div class="route-time">${Utils.formatDate(
                            flight.arrival_time
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
                    <span class="info-label">Available Seats</span>
                    <span class="info-value">${flight.available_seats} / ${
    flight.max_passengers
  }</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="status-badge status-${flight.status}">${
    flight.status
  }</span>
                </div>
            </div>
            <div class="flight-actions">
              <button class="btn btn-outline btn-sm view-flight-btn" data-flight-id="${
                flight.id
              }">
                View Details
              </button>
              <!-- NEW: Message Company button -->
              <button class="btn btn-secondary btn-sm message-company-btn" 
                      data-company-id="${
                        flight.company_id || flight.company?.id
                      }" 
                      data-company-name="${flight.company_name}">
                💬 Message Company
              </button>
              <button class="btn btn-primary btn-sm book-flight-btn" data-flight-id="${
                flight.id
              }">
                Book Flight
              </button>
            </div>

        </div>
    `);
}

function initBookings() {
  // View flight details from bookings
  $(document).on("click", ".view-booking-flight-btn", async function () {
    const flightId = $(this).data("flight-id");
    await showFlightDetails(flightId);
  });

  // Send message to company
  $(document).on("click", ".message-company-btn", function () {
    const companyId = $(this).data("company-id");
    const companyName = $(this).data("company-name");

    // Switch to messages section
    $('.sidebar-menu li[data-section="messages"]').click();

    // Open conversation with company
    setTimeout(() => {
      openConversation(companyId, companyName);
    }, 300);
  });
}

async function loadMyBookings() {
  $("#bookingsList").html('<div class="loading">Loading bookings...</div>');

  try {
    const response = await API.getMyFlights();

    if (response.success && response.data) {
      // Combine all booking types into one array
      const allBookings = [
        ...(response.data.current_flights || []),
        ...(response.data.completed_flights || []),
        ...(response.data.cancelled_flights || []),
      ];
      displayMyBookings(allBookings);
    } else {
      $("#bookingsList").html(
        `<div class="empty-state"><p>${
          response.message || "No bookings found"
        }</p></div>`
      );
    }
  } catch (error) {
    console.error("Load bookings error:", error);
    $("#bookingsList").html(
      '<div class="empty-state"><p>Error loading bookings</p></div>'
    );
  }
}

function displayMyBookings(bookings) {
  const container = $("#bookingsList");
  container.empty();

  if (!bookings || !Array.isArray(bookings) || bookings.length === 0) {
    container.html(
      '<div class="empty-state"><i class="icon-ticket"></i><p>No bookings yet</p></div>'
    );
    return;
  }

  bookings.forEach((booking) => {
    const card = createBookingCard(booking);
    container.append(card);
  });
}

function createBookingCard(booking) {
  return $(`
        <div class="booking-card">
            <div class="booking-header">
                <div>
                    <div class="flight-title">${booking.flight_name}</div>
                    <div class="flight-code">${booking.flight_code}</div>
                    <div class="info-value">${booking.company_name}</div>
                </div>
                <span class="status-badge status-${booking.booking_status}">${
    booking.booking_status
  }</span>
            </div>
            ${
              booking.departure_city && booking.arrival_city
                ? `
            <div class="flight-route">
                <div class="route-point">
                    <div class="route-city">${booking.departure_city}</div>
                    ${
                      booking.departure_time
                        ? `<div class="route-time">${Utils.formatDate(
                            booking.departure_time
                          )}</div>`
                        : ""
                    }
                </div>
                <div class="route-arrow">
                    <div class="route-line"></div>
                </div>
                <div class="route-point">
                    <div class="route-city">${booking.arrival_city}</div>
                    ${
                      booking.arrival_time
                        ? `<div class="route-time">${Utils.formatDate(
                            booking.arrival_time
                          )}</div>`
                        : ""
                    }
                </div>
            </div>
            `
                : ""
            }
            <div class="booking-info">
                <div class="info-item">
                    <span class="info-label">Booking Date</span>
                    <span class="info-value">${Utils.formatDate(
                      booking.booking_date
                    )}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Amount Paid</span>
                    <span class="info-value">${Utils.formatCurrency(
                      booking.amount_paid
                    )}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Method</span>
                    <span class="info-value">${
                      booking.payment_method === "account"
                        ? "Account Balance"
                        : "Cash"
                    }</span>
                </div>
            </div>
            <div class="booking-actions">
                <button class="btn btn-outline btn-sm view-booking-flight-btn" data-flight-id="${
                  booking.flight_id
                }">
                    View Flight Details
                </button>
                <button class="btn btn-secondary btn-sm message-company-btn" 
                        data-company-id="${booking.company_id}" 
                        data-company-name="${booking.company_name}">
                    Message Company
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

    if (!receiverId) {
      alert("Please select a conversation first");
      return;
    }

    if (!message) {
      alert("Please enter a message");
      return;
    }

    try {
      const response = await API.sendMessage(receiverId, message);

      if (response.success) {
        $("#messageText").val("");
        // Reload messages
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
    // Ensure we have valid IDs
    if (!conv.other_user_id || !conv.other_user_name) {
      console.warn("Invalid conversation data:", conv);
      return;
    }

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
  if (!userId) {
    console.error("Invalid user ID");
    return;
  }

  currentConversation = userId;
  $("#receiverId").val(userId);
  $("#activeConversationName").text(userName);
  $("#messageViewEmpty").hide();
  $("#messageViewActive").show();

  loadMessages(userId);
}

async function loadMessages(userId) {
  if (!userId) {
    console.error("Invalid user ID for loading messages");
    return;
  }

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

  if (!messages || messages.length === 0) {
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

    const formData = new FormData();
    formData.append("name", $("#profileFullName").val());
    formData.append("username", $("#profileUsername").val());
    formData.append("email", $("#profileEmail").val());
    formData.append("tel", $("#profileTel").val());

    const photo = $("#profilePhoto")[0].files[0];
    const passport = $("#profilePassport")[0].files[0];

    if (photo) formData.append("photo", photo);
    if (passport) formData.append("passport_img", passport);

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
      } else {
        alert(response.message);
      }
    } catch (error) {
      console.error("Update profile error:", error);
      alert("Error updating profile");
    }
  });

  // Add balance button
  $("#addBalanceBtn").on("click", function () {
    Utils.openModal("balanceModal");
  });

  // Add balance form
  $("#addBalanceForm").on("submit", async function (e) {
    e.preventDefault();

    const amount = parseFloat($("#balanceAmount").val());

    if (amount <= 0) {
      alert("Please enter a valid amount");
      return;
    }

    try {
      const formData = new FormData();
      formData.append("account_balance", amount);

      const response = await API.updateProfile(formData);

      if (response.success) {
        // Update session data
        const userData = Session.getUserData();
        await loadUserInfo();

        alert("Funds added successfully!");
        Utils.closeModal("balanceModal");
        $("#balanceAmount").val("");
      } else {
        alert(response.message || "Error adding funds");
      }
    } catch (error) {
      console.error("Add balance error:", error);
      alert("Error adding funds");
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
      $("#profileBalance").text(parseFloat(profile.account_balance).toFixed(2));

      // ✅ Profile photo
      if (profile.photo_path) {
        $("#profileAvatar").html(
          `<img src="${API_CONFIG.BASE_URL}/uploads/${profile.photo_path}" alt="Profile">`
        );
      }

      // ✅ PASSPORT IMAGE - ADD THIS
      if (profile.passport_path || profile.passport_img_path) {
        const passportPath = profile.passport_path || profile.passport_img_path;
        $("#profilePassportPreview").html(
          `<img src="${API_CONFIG.BASE_URL}/uploads/${passportPath}" alt="Passport" style="max-width: 200px; border: 1px solid #ddd; border-radius: 4px;">`
        );
      }
    }
  } catch (error) {
    console.error("Load profile error:", error);
  }
}

function initModals() {
  // View flight details
  $(document).on("click", ".view-flight-btn", async function () {
    const flightId = $(this).data("flight-id");
    await showFlightDetails(flightId);
  });

  // Book flight button
  $(document).on("click", ".book-flight-btn", async function () {
    const flightId = $(this).data("flight-id");
    await openBookingModal(flightId);
  });

  // Book flight form
  $("#bookFlightForm").on("submit", async function (e) {
    e.preventDefault();

    const flightId = $("#bookFlightId").val();
    const paymentMethod = $('input[name="payment_method"]:checked').val();

    try {
      const response = await API.bookFlight(flightId, paymentMethod);

      if (response.success) {
        alert("Flight booked successfully!");
        Utils.closeModal("bookModal");

        // Update balance if paid from account
        if (paymentMethod === "account") {
          const userData = Session.getUserData();
          await loadUserInfo();
        }

        // Reload search results
        searchFlights($("#fromCity").val(), $("#toCity").val());
      } else {
        alert(response.message);
      }
    } catch (error) {
      console.error("Book flight error:", error);
      alert("Error booking flight");
    }
  });
}

async function showFlightDetails(flightId) {
  Utils.openModal("flightModal");
  $("#flightModalBody").html('<div class="loading">Loading...</div>');

  try {
    const response = await API.getFlightInfo(flightId);

    if (response.success) {
      const flight = response.data;
      $("#flightModalBody").html(createFlightDetailsHTML(flight));
    } else {
      $("#flightModalBody").html("<p>Error loading flight details</p>");
    }
  } catch (error) {
    console.error("Load flight details error:", error);
    $("#flightModalBody").html("<p>Error loading flight details</p>");
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
                <span>Company:</span>
                <span>${flight.company_name || "N/A"}</span>
            </div>
            <div class="summary-row">
                <span>Price:</span>
                <span>${Utils.formatCurrency(flight.fees)}</span>
            </div>
            <div class="summary-row">
                <span>Available Seats:</span>
                <span>${
                  flight.max_passengers - flight.registered_passengers
                } / ${flight.max_passengers}</span>
            </div>
            <div class="summary-row">
                <span>Status:</span>
                <span class="status-badge status-${flight.status}">${
    flight.status
  }</span>
            </div>
        </div>
        <h4>Flight Route</h4>
        ${itineraryHTML}
    `;
}

async function openBookingModal(flightId) {
  try {
    const response = await API.getFlightInfo(flightId);

    if (response.success) {
      const flight = response.data;
      $("#bookFlightId").val(flightId);

      const summaryHTML = `
                <h3>${flight.flight_name}</h3>
                <div class="summary-row">
                    <span>Flight Code:</span>
                    <span>${flight.flight_code}</span>
                </div>
                <div class="summary-row">
                    <span>Price:</span>
                    <span>${Utils.formatCurrency(flight.fees)}</span>
                </div>
                <div class="summary-row">
                    <span>Total:</span>
                    <span>${Utils.formatCurrency(flight.fees)}</span>
                </div>
            `;

      $("#bookingSummary").html(summaryHTML);
      Utils.openModal("bookModal");
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error loading flight information");
  }
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
