<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CK Login</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/login.css">
    <script src="javascript/login.js" defer></script>
</head>

<body>
    <?php include_once 'header.php' ?>
    <main>
        <section class="page">
            <div class="userBox">

                <div class="options">

                    <div class="dropdownButton" id="loginButton">Login</div>
                    <div class="dropdownButton" id="signupButton">Sign Up</div>

                </div>

                <form id="loginForm" action="#" method="post">

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        <div id="email-error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <div id="password-error" class="error-message"></div>
                    </div>

                    <button type="submit">Login</button>
                </form>

                <form id="signupForm" action="#" method="post">

                    <div class="form-group">
                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required>
                        <div id="firstName-error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="lastName">Last Name:</label>
                        <input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required>
                        <div id="lastName-error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="signup-email" name="email" placeholder="Enter your email" required>
                        <div id="signup-email-error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Celphone:</label>
                        <div id="phonediv">
                            <select id="countryCode" name="countryCode" aria-label="Country code" required>
                                <option value="" disabled selected>Select Country</option>
                                <option value="+27">South Africa (+27)</option>
                                <option value="+264">Namibia (+264)</option>
                                <option value="+267">Botswana (+267)</option>
                                <option value="+263">Zimbabwe (+263)</option>
                                <option value="+258">Mozambique (+258)</option>
                                <option value="+266">Lesotho (+266)</option>
                                <option value="+268">Eswatini (+268)</option>
                                <option value="+244">Angola (+244)</option>
                            </select>
                            <input type="tel" id="phone" name="phone" placeholder="Enter cellphone number" required />
                        </div>
                        <div id="cphone-error" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="signup-password" name="password" placeholder="Enter your password" required>
                        <div id="signup-password-error" class="error-message"></div>
                    </div>

                    <button type="submit">Register</button>
                </form>

            </div>
        </section>
    </main>

    <?php include_once 'footer.php' ?>

</body>

</html>