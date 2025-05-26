<?php
session_start();
$isLoggedIn = isset($_SESSION['anapikey']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$current_page = basename($_SERVER['PHP_SELF']);
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
  }

  header>div {
    font-weight: 700;
    font-size: 1.4rem;
    color: #262626;
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
    color: #000;
  }

  nav ul li a:hover {
    color: #2edf84;
    transition: color 0.2s ease;
  }

  .logo {
    max-height: 40px;
    object-fit: contain;
  }

  #logop {
    margin: 0;
    font-weight: 600;
    font-size: 1.2rem;
    color: #262626;
    user-select: none;
  }

  li>a {
    color: #262626;
    text-decoration: none;
  }

  .active {
    color: #747378;
  }
</style>

<header>
  <div>
    <img class="logo" src="img/logo.png">
    <p id="logop">Chief Kompare</p>
  </div>
  <nav>
    <ul>
      <li><a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Search</a></li>
      <li><a href="products.php" class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>">Products</a></li>
      <li><a class="<?php echo $current_page === 'tpprated.php' ? 'active' : ''; ?>">Dashboard</a></li>
      <li><a class="<?php echo $current_page === 'wishlist.php' ? 'active' : ''; ?>">Wishlist</a></li>
      <li><a class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">Orders</a></li>
      <li><a href="cart.php" class="<?php echo $current_page === 'cart.php' ? 'active' : ''; ?>">Cart</a></li>
      <?php if ($isLoggedIn): ?>
        <li><a class="<?php echo $current_page === 'logout.php' ? 'active' : ''; ?>">Logout</a></li>
        <li><?php echo htmlspecialchars($username); ?></li>
      <?php else: ?>
        <li><a href="login.php" class="<?php echo $current_page === 'login.php' ? 'active' : ''; ?>">Login</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>