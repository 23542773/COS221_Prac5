const page = document.getElementsByClassName('page');
const signupButton = document.getElementById('signupButton');
const signupForm = document.getElementById('signupForm');
const loginButton = document.getElementById('loginButton');
const loginForm = document.getElementById('loginForm');

loginButton.addEventListener('click', function () {
    // revert signup
    signupForm.style.display = 'none';
    signupButton.style.backgroundColor = '#fcfaf9';
    // login actions
    loginForm.style.display = 'flex';
    loginButton.style.backgroundColor = '#2edf84';
});

signupButton.addEventListener('click', function () {
    // revert login
    loginForm.style.display = 'none';
    loginButton.style.backgroundColor = '#fcfaf9';
    // signup actions
    signupForm.style.display = 'flex';
    signupButton.style.backgroundColor = '#2edf84';
});

