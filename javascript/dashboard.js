const preferences = document.getElementById('preferences');

const togglePreferences = document.getElementById('togglePreferences');

togglePreferences.addEventListener('click', function () {
    preferences.classList.toggle('hide');
})
