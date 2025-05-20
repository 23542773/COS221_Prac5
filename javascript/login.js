const page = document.getElementsByClassName('page');
const signupButton = document.getElementById('signupButton');
const signupForm = document.getElementById('signupForm');
const loginButton = document.getElementById('loginButton');
const loginForm = document.getElementById('loginForm');

loginButton.addEventListener('click', function () {
    // revert signup
    signupForm.style.display = 'none';
    signupButton.style.backgroundColor = '#2edf84';
    signupButton.style.boxShadow = ' 0 4px 10px #2edf84';
    // login actions
    loginForm.style.display = 'flex';
    loginButton.style.backgroundColor = '#262626';
    loginButton.style.boxShadow = '0px 0px 0px';
});

signupButton.addEventListener('click', function () {
    // revert login
    loginForm.style.display = 'none';
    loginButton.style.backgroundColor = '#2edf84';
    loginButton.style.boxShadow = ' 0 4px 10px #2edf84';
    // signup actions
    signupForm.style.display = 'flex';
    signupButton.style.backgroundColor = '#262626';
    signupButton.style.boxShadow = '0px 0px 0px';
});

