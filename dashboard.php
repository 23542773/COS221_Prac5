<?php
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chief Kompare</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/body.css">
    <script src="javascript/dashboard.js" defer></script>
</head>

<body>
    <?php include_once 'header.php' ?>

    <div class="page">
        <div id="preferences">
            <div id="togglePreferences"></div> <!-- preferences hide and show button -->
            <p id="prefH1">Preferences</p>
            <p class="prefHeading">Theme</p>
            <div class="toggle-container">
                <input type="checkbox" id="darkModeToggle" class="toggle-checkbox">
                <label for="darkModeToggle" class="toggle-label"></label>
                <span id="themeName">Light</span>
            </div>
            <p class="prefHeading">Sort</p>
            <button>sort</button>
            <p class="prefHeading">Display Name</p>
            <input type="text" id="displayName">
        </div>

        <div class="topProducts">

        </div>

        <div class="ratings">

        </div>
    </div>

    <?php include_once 'footer.php' ?>
</body>

</html>