<?php
include_once "header.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
    <script src="delCart.js"></script>
    <script src="getCart.js"></script>
</head>
<body>
    <h1>My Shopping Cart</h1>
<div id="cart-grid" class="cart-grid">
    <!-- Cart items -->
</div>
</body>
<?php
include_once 'footer.php';
?>
</html>
<script>
     document.addEventListener("DOMContentLoaded", function () {
        fetchCart();
    });
</script>
<style>
    #cart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    padding: 20px;
}

.cart-card {
    display: flex;
    flex-direction: column;
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: transform 0.2s ease-in-out;
}

.cart-card:hover {
    transform: scale(1.02);
}

.cart-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.cart-info {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cart-category {
    font-size: 0.9rem;
    color: #777;
}

.cart-name {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
}

.cart-brand,
.cart-description,
.cart-quantity {
    font-size: 0.95rem;
    color: #555;
}

.delete-button {
    margin-top: 12px;
    padding: 8px 14px;
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    align-self: flex-start;
    transition: background-color 0.2s ease-in-out;
}

.delete-button:hover {
    background-color: #c82333;
}

</style>