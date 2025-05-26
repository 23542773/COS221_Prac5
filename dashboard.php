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
            <!-- just filler buttons for now -->
            <button>theme</button>
            <button>sort</button>
            <button>display name</button>
            <div id="togglePreferences"></div>
        </div>

        <div class="topProducts">

        </div>

        <div class="ratings">

        </div>
    </div>

    <?php include_once 'footer.php' ?>
</body>

</html>