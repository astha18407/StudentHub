
const themeToggle = document.getElementById("themeToggle");

if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
}

if (themeToggle) {
    themeToggle.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");

        const theme = document.body.classList.contains("dark-mode")
            ? "dark"
            : "light";

        localStorage.setItem("theme", theme);
    });
}

// Menu
const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");

if (menuToggle && mainNav) {
    menuToggle.addEventListener("click", function () {
        mainNav.classList.toggle("show");
    });
}

// Notification
const closeNotification = document.getElementById("closeNotification");
const notificationBanner = document.getElementById("notificationBanner");

if (closeNotification && notificationBanner) {
    closeNotification.addEventListener("click", function () {
        notificationBanner.style.display = "none";
    });
}

// Welcome modal
const openModal = document.getElementById("openModal");
const closeModal = document.getElementById("closeModal");
const welcomeModal = document.getElementById("welcomeModal");

if (openModal && welcomeModal) {
    openModal.addEventListener("click", function () {
        welcomeModal.classList.add("show");
    });
}

if (closeModal && welcomeModal) {
    closeModal.addEventListener("click", function () {
        welcomeModal.classList.remove("show");
    });
}

// FAQ
const questions = document.querySelectorAll(".faq-question");

questions.forEach(function (question) {
    question.addEventListener("click", function () {
        question.parentElement.classList.toggle("active");
    });
});

// Slider
const slides = document.querySelectorAll(".slider-item");
const nextSlide = document.getElementById("nextSlide");
const prevSlide = document.getElementById("prevSlide");

let currentSlide = 0;

function showSlide() {
    slides.forEach(function (slide) {
        slide.classList.remove("active");
    });

    if (slides.length > 0) {
        slides[currentSlide].classList.add("active");
    }
}

if (slides.length > 0) {
    showSlide();
}

if (nextSlide) {
    nextSlide.addEventListener("click", function () {
        if (slides.length === 0) return;

        currentSlide = (currentSlide + 1) % slides.length;
        showSlide();
    });
}

if (prevSlide) {
    prevSlide.addEventListener("click", function () {
        if (slides.length === 0) return;

        currentSlide =
            (currentSlide - 1 + slides.length) % slides.length;

        showSlide();
    });
}

// Event category filter
const eventFilter = document.getElementById("eventFilter");
const eventCards = document.querySelectorAll(".event-card");

if (eventFilter) {
    eventFilter.addEventListener("change", function () {
        const selected = this.value;

        eventCards.forEach(function (card) {
            if (
                selected === "all" ||
                card.dataset.category === selected
            ) {
                card.style.display = "flex";
            } else {
                card.style.display = "none";
            }
        });
    });
}

// Contact form
const contactForm = document.getElementById("contactForm");
const contactMessage = document.getElementById("contactMessage");

if (contactForm) {
    contactForm.addEventListener("submit", function (event) {
        event.preventDefault();

        if (contactMessage) {
            contactMessage.textContent =
                "Your message has been sent successfully.";
        }

        contactForm.reset();
    });
}

// Login
// No demo handler here. The browser submits the form to login.php.

// Registration form
const registerForm = document.getElementById("registerForm");

if (registerForm) {
    const fname = document.getElementById("fname");
    const lname = document.getElementById("lname");
    const email = document.getElementById("email");
    const mobile = document.getElementById("mobile");
    const course = document.getElementById("course");
    const year = document.getElementById("year");
    const password = document.getElementById("password");
    const confirm = document.getElementById("confirm");
    const terms = document.getElementById("terms");

    const registerMessage = document.getElementById("registerMessage");
    const passwordStrength = document.getElementById("passwordStrength");

    const namePattern = /^[A-Za-z ]+$/;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const mobilePattern = /^[0-9]{10}$/;

    function showError(id, message) {
        const errorElement = document.getElementById(id);

        if (errorElement) {
            errorElement.textContent = message;
        }
    }

    function clearError(id) {
        showError(id, "");
    }

    function validateName(input, errorId) {
        if (!input) return false;

        const value = input.value.trim();

        if (value === "") {
            showError(errorId, "This field is required.");
            return false;
        }

        if (!namePattern.test(value)) {
            showError(errorId, "Name should contain only letters.");
            return false;
        }

        clearError(errorId);
        return true;
    }

    function validateEmail() {
        if (!email) return false;

        const value = email.value.trim();

        if (value === "") {
            showError("emailError", "Email is required.");
            return false;
        }

        if (!emailPattern.test(value)) {
            showError("emailError", "Enter a valid email address.");
            return false;
        }

        clearError("emailError");
        return true;
    }

    function validateMobile() {
        if (!mobile) return false;

        const value = mobile.value.trim();

        if (!mobilePattern.test(value)) {
            showError("mobileError", "Enter a valid 10 digit mobile number.");
            return false;
        }

        clearError("mobileError");
        return true;
    }

    function checkPasswordStrength() {
        if (!password || !passwordStrength) return;

        const value = password.value;
        let strength = 0;

        if (value.length >= 8) strength++;
        if (/[A-Z]/.test(value)) strength++;
        if (/[0-9]/.test(value)) strength++;
        if (/[^A-Za-z0-9]/.test(value)) strength++;

        if (value.length === 0) {
            passwordStrength.textContent = "";
        } else if (strength <= 1) {
            passwordStrength.textContent = "Password Strength: Weak";
        } else if (strength <= 3) {
            passwordStrength.textContent = "Password Strength: Medium";
        } else {
            passwordStrength.textContent = "Password Strength: Strong";
        }
    }

    function validatePassword() {
        if (!password) return false;

        if (password.value.length < 8) {
            showError("passwordError", "Password must contain at least 8 characters.");
            return false;
        }

        clearError("passwordError");
        return true;
    }

    function validateConfirmPassword() {
        if (!password || !confirm) return false;

        if (confirm.value === "") {
            showError("confirmError", "Please confirm your password.");
            return false;
        }

        if (password.value !== confirm.value) {
            showError("confirmError", "Passwords do not match.");
            return false;
        }

        clearError("confirmError");
        return true;
    }

    function validateCourse() {
        if (!course || course.value === "") {
            showError("courseError", "Please select a course.");
            return false;
        }

        clearError("courseError");
        return true;
    }

    function validateYear() {
        if (!year || year.value === "") {
            showError("yearError", "Please select a year.");
            return false;
        }

        clearError("yearError");
        return true;
    }

    function validateGender() {
        const gender = document.querySelector(
            '#registerForm input[name="gender"]:checked'
        );

        if (!gender) {
            showError("genderError", "Please select your gender.");
            return false;
        }

        clearError("genderError");
        return true;
    }

    function validateTerms() {
        if (!terms || !terms.checked) {
            showError("termsError", "Please accept the Terms and Conditions.");
            return false;
        }

        clearError("termsError");
        return true;
    }

    if (fname) {
        fname.addEventListener("input", function () {
            validateName(fname, "fnameError");
        });
    }

    if (lname) {
        lname.addEventListener("input", function () {
            validateName(lname, "lnameError");
        });
    }

    if (email) email.addEventListener("input", validateEmail);
    if (mobile) mobile.addEventListener("input", validateMobile);

    if (password) {
        password.addEventListener("input", function () {
            checkPasswordStrength();
            validatePassword();
        });
    }

    if (confirm) {
        confirm.addEventListener("input", validateConfirmPassword);
    }

    if (course) course.addEventListener("change", validateCourse);
    if (year) year.addEventListener("change", validateYear);

    registerForm.addEventListener("submit", function (event) {
        const isValid =
            validateName(fname, "fnameError") &&
            validateName(lname, "lnameError") &&
            validateEmail() &&
            validateMobile() &&
            validatePassword() &&
            validateConfirmPassword() &&
            validateCourse() &&
            validateYear() &&
            validateGender() &&
            validateTerms();

        if (!isValid) {
            event.preventDefault();

            if (registerMessage) {
                registerMessage.textContent =
                    "Please correct the errors above.";
            }
        }
    });
}
