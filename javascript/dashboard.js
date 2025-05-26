//DOM elements
const preferences = document.getElementById('preferences');
const togglePreferences = document.getElementById('togglePreferences');
const darkModeToggle = document.getElementById('darkModeToggle');

togglePreferences.addEventListener('click', function () {
    preferences.classList.toggle('hide');
});

darkModeToggle.addEventListener('change', function () {
    const page = document.querySelector('.page');

    if (darkModeToggle.checked) {
        page.style.background = '#262626';
    } else {
        page.style.background = '#fcfaf9';
    }
});