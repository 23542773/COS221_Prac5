//DOM elements
const preferences = document.getElementById('preferences');
const togglePreferences = document.getElementById('togglePreferences');
const darkModeToggle = document.getElementById('darkModeToggle');

togglePreferences.addEventListener('click', function () {
    preferences.classList.toggle('hide');
});

darkModeToggle.addEventListener('change', function () {
    const page = document.querySelector('.page');
    const theme = document.getElementById('themeName');

    if (darkModeToggle.checked) {
        theme.textContent = 'Dark';
        page.style.background = '#262626';
    } else {
        theme.textContent = 'Light'
        page.style.background = '#fcfaf9';
    }
});