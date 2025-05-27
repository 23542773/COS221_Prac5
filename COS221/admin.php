<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/body.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="javascript/admin.js" defer></script>
</head>

<body>
    <?php include_once 'header.php' ?>

    <div class="page">

        <div class="container">
            <h2>Admin Panel</h2>

            <div class="form-group">
                <label for="action">Action:</label>
                <select id="action">
                    <option value="">Select Action</option>
                    <option value="create">Create Record</option>
                    <option value="update">Update Record</option>
                    <option value="delete">Delete Record</option>
                </select>
            </div>

            <div class="form-group">
                <label for="table">Table:</label>
                <select id="table">
                    <option value="">Select Table</option>
                </select>
            </div>

            <div id="formFields" class="dynamic-fields" style="display: none;"></div>

            <button id="submitBtn" onclick="sendRequest()">Execute Action</button>

            <div id="response"></div>
        </div>

    </div>

    <?php include_once 'footer.php' ?>
</body>