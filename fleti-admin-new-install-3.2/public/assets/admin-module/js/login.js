"use strict";

let password = document.getElementById('password');
let passwordIcon = document.getElementById('password-eye');

passwordIcon.onclick = function () {
    if (password.getAttribute('type') === 'text') {
        password.setAttribute('type', 'password');
        passwordIcon.setAttribute('class', 'bi bi-eye-slash-fill tooltip-icon');
    } else {
        password.setAttribute('type', 'text');
        passwordIcon.setAttribute('class', 'bi bi-eye-fill tooltip-icon');
    }
}
