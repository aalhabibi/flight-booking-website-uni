// Authentication JavaScript

$(document).ready(function () {
  // Check if already logged in
  if (Session.isLoggedIn()) {
    const userData = Session.getUserData();
    redirectToDashboard(userData.user_type);
  }

  // Handle login form
  if ($("#loginForm").length) {
    initLoginForm();
  }

  // Handle register form
  if ($("#registerForm").length) {
    initRegisterForm();
  }
});

function initLoginForm() {
  $("#loginForm").on("submit", async function (e) {
    e.preventDefault();

    Utils.clearErrors();
    $("#error-message").hide();

    const email = $("#email").val().trim();
    const password = $("#password").val();

    // Validation
    if (!email || !password) {
      Utils.showError("error-message", "Please fill in all fields");
      return;
    }

    if (!Utils.validateEmail(email)) {
      Utils.showFieldError("email", "Please enter a valid email address");
      return;
    }

    Utils.showLoading("loginBtn");

    try {
      const response = await API.login(email, password);

      if (response.success) {
        // Save user data to session
        Session.setUserData(response.data);

        Utils.showSuccess(
          "success-message",
          "Login successful! Redirecting..."
        );

        // Redirect to appropriate dashboard
        setTimeout(() => {
          redirectToDashboard(response.data.user_type);
        }, 1000);
      } else {
        Utils.showError("error-message", response.message);
        Utils.hideLoading("loginBtn");
      }
    } catch (error) {
      console.error("Login error:", error);
      Utils.showError("error-message", "An error occurred. Please try again.");
      Utils.hideLoading("loginBtn");
    }
  });
}

function initRegisterForm() {
  // User type selector
  $(".type-btn").on("click", function () {
    $(".type-btn").removeClass("active");
    $(this).addClass("active");

    const userType = $(this).data("type");
    $("#user_type").val(userType);

    if (userType === "passenger") {
      $("#passengerFields").show();
      $("#companyFields").hide();
    } else {
      $("#passengerFields").hide();
      $("#companyFields").show();
    }
  });

  // Check if user type was pre-selected from URL
  const preSelectedType = localStorage.getItem("register_user_type");
  if (preSelectedType) {
    $(`.type-btn[data-type="${preSelectedType}"]`).click();
    localStorage.removeItem("register_user_type");
  }

  // Handle registration form submission
  $("#registerForm").on("submit", async function (e) {
    e.preventDefault();

    Utils.clearErrors();
    $("#error-message").hide();

    // Get form values
    const userType = $("#user_type").val();
    const name = $("#name").val().trim();
    const username = $("#username").val().trim();
    const email = $("#email").val().trim();
    const tel = $("#tel").val().trim();
    const password = $("#password").val();
    const confirmPassword = $("#confirm_password").val();
    const terms = $("#terms").is(":checked");

    // Validation
    let hasError = false;

    if (!name || name.length < 2) {
      Utils.showFieldError("name", "Name must be at least 2 characters");
      hasError = true;
    }

    if (!username || username.length < 3) {
      Utils.showFieldError(
        "username",
        "Username must be at least 3 characters"
      );
      hasError = true;
    }

    if (!Utils.validateEmail(email)) {
      Utils.showFieldError("email", "Please enter a valid email address");
      hasError = true;
    }

    if (!Utils.validatePhone(tel)) {
      Utils.showFieldError("tel", "Please enter a valid phone number");
      hasError = true;
    }

    if (!password || password.length < 8) {
      Utils.showFieldError(
        "password",
        "Password must be at least 8 characters"
      );
      hasError = true;
    }

    if (password !== confirmPassword) {
      Utils.showFieldError("confirm_password", "Passwords do not match");
      hasError = true;
    }

    if (!terms) {
      Utils.showFieldError("terms", "You must agree to the terms");
      hasError = true;
    }

    if (hasError) {
      Utils.showError("error-message", "Please fix the errors above");
      return;
    }

    // Create FormData object
    const formData = new FormData();
    formData.append("user_type", userType);
    formData.append("name", name);
    formData.append("username", username);
    formData.append("email", email);
    formData.append("tel", tel);
    formData.append("password", password);

    // Add type-specific fields
    if (userType === "passenger") {
      const photo = $("#photo")[0].files[0];
      const passport = $("#passport_img")[0].files[0];

      if (photo) formData.append("photo", photo);
      if (passport) formData.append("passport_img", passport);
    } else {
      const bio = $("#bio").val().trim();
      const address = $("#address").val().trim();
      const location = $("#location").val().trim();
      const logo = $("#logo")[0].files[0];

      if (bio) formData.append("bio", bio);
      if (address) formData.append("address", address);
      if (location) formData.append("location", location);
      if (logo) formData.append("logo", logo);
    }

    Utils.showLoading("registerBtn");

    try {
      const response = await API.register(formData);

      if (response.success) {
        Utils.showSuccess(
          "success-message",
          "Registration successful! Redirecting to login..."
        );

        // Redirect to login page
        setTimeout(() => {
          window.location.href = "login.html";
        }, 2000);
      } else {
        // Handle validation errors
        if (response.data && response.data.errors) {
          Object.keys(response.data.errors).forEach((field) => {
            Utils.showFieldError(field, response.data.errors[field]);
          });
        }
        Utils.showError("error-message", response.message);
        Utils.hideLoading("registerBtn");
      }
    } catch (error) {
      console.error("Registration error:", error);
      Utils.showError("error-message", "An error occurred. Please try again.");
      Utils.hideLoading("registerBtn");
    }
  });
}

function redirectToDashboard(userType) {
  if (userType === "passenger") {
    window.location.href = "passenger-dashboard.html";
  } else if (userType === "company") {
    window.location.href = "company-dashboard.html";
  }
}
