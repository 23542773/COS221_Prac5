<?php
session_start();
// Assuming 'anapikey' is the session variable that indicates a logged-in user
$isLoggedIn = isset($_SESSION['anapikey']);
$username = $isLoggedIn ? $_SESSION['username'] : ''; // Get the username if logged in
?>

<style>
  header {
    position: fixed;
    width: 100vw;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #747578;
    padding: 1rem 2rem;
    box-shadow: 0 4px 5px #132A13;
  }

  header>div {
    font-weight: 700;
    font-size: 1.4rem;
    color: #222;
    margin-right: 3rem;
    user-select: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  nav ul {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 2rem;
  }

  nav ul li {
    cursor: pointer;
    font-weight: 500;
    color: #262626;
    position: relative;
    padding: 0.25rem 0;
    transition: color 0.2s ease;
  }

  nav ul li:hover {
    color: #2edf84;
  }

  .logo {
    max-height: 40px;
    object-fit: contain;
  }

  #logop {
    margin: 0;
    font-weight: 600;
    font-size: 1.2rem;
    color: #222;
    user-select: none;
  }

  a {
    color: #262626;
    text-decoration: none;
  }

  a:hover {
    color: #2edf84;
    transition: color 0.2s ease;
  }
</style>

<header>
  <div>
    <img class="logo" src="img/logo.png">
    <p id="logop">Chief Kompare</p>
  </div>
  <nav>
    <ul>
      <li>Dashboard</li>
      <li>Cart</li>
      <li>Wishlist</li>
      <?php if ($isLoggedIn): ?>
        <li>Logout <?php echo htmlspecialchars($username); ?></li>
      <?php else: ?>
        <li><a href="/login.php">Login</a></li> <?php endif; ?>
    </ul>
  </nav>
</header>