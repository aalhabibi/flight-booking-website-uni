// Main JavaScript - Landing Page

$(document).ready(function () {
  // Initialize jQuery UI Tooltips for elements with title attribute
  $(document).tooltip({
    position: {
      my: "center bottom-10",
      at: "center top",
    },
    show: {
      effect: "fadeIn",
      duration: 200,
    },
    hide: {
      effect: "fadeOut",
      duration: 200,
    },
  });

  // Smooth scrolling for anchor links
  $('a[href^="#"]').on("click", function (e) {
    const target = $(this.getAttribute("href"));
    if (target.length) {
      e.preventDefault();
      $("html, body")
        .stop()
        .animate(
          {
            scrollTop: target.offset().top - 70,
          },
          1000
        );
    }
  });

  // Mobile menu toggle
  $(".mobile-menu-toggle").on("click", function () {
    $(".navbar-menu").toggleClass("active");
  });

  // Highlight active menu item on scroll
  $(window).on("scroll", function () {
    let scrollPos = $(document).scrollTop();

    $('.navbar-menu a[href^="#"]').each(function () {
      let currLink = $(this);
      let refElement = $(currLink.attr("href"));

      if (
        refElement.length &&
        refElement.position().top - 100 <= scrollPos &&
        refElement.position().top + refElement.height() > scrollPos
      ) {
        $(".navbar-menu a").removeClass("active");
        currLink.addClass("active");
      }
    });
  });

  // Check URL parameters for user type in register link
  const urlParams = new URLSearchParams(window.location.search);
  const userType = urlParams.get("type");
  if (userType) {
    localStorage.setItem("register_user_type", userType);
  }

  // Check if user is already logged in and redirect to dashboard
  if (Session.isLoggedIn()) {
    const userData = Session.getUserData();
    if (
      window.location.pathname.includes("login.html") ||
      window.location.pathname.includes("register.html")
    ) {
      redirectToDashboard(userData.user_type);
    }
  }
});

function redirectToDashboard(userType) {
  if (userType === "passenger") {
    window.location.href = "passenger-dashboard.html";
  } else if (userType === "company") {
    window.location.href = "company-dashboard.html";
  }
}

// Modal functionality
$(document).on("click", ".modal-close, .modal-close-btn", function () {
  $(this).closest(".modal").removeClass("show");
});

$(document).on("click", ".modal", function (e) {
  if ($(e.target).hasClass("modal")) {
    $(this).removeClass("show");
  }
});

// Escape key to close modal
$(document).on("keydown", function (e) {
  if (e.key === "Escape") {
    $(".modal").removeClass("show");
  }
});
