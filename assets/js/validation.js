function validateRegister() {
    var name     = document.forms["registerForm"]["full_name"].value.trim();
    var email    = document.forms["registerForm"]["email"].value.trim();
    var password = document.forms["registerForm"]["password"].value;
    var confirm  = document.forms["registerForm"]["confirm_password"].value;
    var emailRx  = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;

    if (name.length < 2) {
        alert("Full name must be at least 2 characters.");
        return false;
    }
    if (!emailRx.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }
    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return false;
    }
    if (password !== confirm) {
        alert("Passwords do not match.");
        return false;
    }
    return true;
}

function validateLogin() {
    var email    = document.forms["loginForm"]["email"].value.trim();
    var password = document.forms["loginForm"]["password"].value;
    var emailRx  = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;

    if (!emailRx.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }
    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return false;
    }
    return true;
}

function validateBookForm() {
    var coverInput = document.querySelector('input[name="cover_image"]');
    if (coverInput && coverInput.files.length > 0) {
        if (coverInput.files[0].size > 1000000) {
            alert("Cover image is too large. Maximum size is 1MB (1000KB). Please choose a smaller image.");
            return false;
        }
    }
    var mediaInput = document.querySelector('input[name="media_file"]');
    if (mediaInput && mediaInput.files.length > 0) {
        if (mediaInput.files[0].size > 1000000) {
            alert("Media file is too large. Maximum size is 1MB (1000KB). Please choose a smaller file.");
            return false;
        }
    }
    var title  = document.getElementById("bookTitle");
    var author = document.getElementById("bookAuthor");
    var desc   = document.getElementById("bookDesc");

    if (!title || title.value.trim() === '') {
        alert("Book title is required.");
        return false;
    }
    if (!author || author.value.trim() === '') {
        alert("Author name is required.");
        return false;
    }
    if (!desc || desc.value.trim().length < 10) {
        alert("Description must be at least 10 characters.");
        return false;
    }
    return true;
}
