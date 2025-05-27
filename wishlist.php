<?php
// require 'getWishList.php';
include_once 'header.php';
?>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        fetchWishlist();
    });
</script>

<!DOCTYPE html>
<html>
<head>
    <title>My Wishlist</title>
    <script src="delWishlist.js"></script>
    <script src="getWishList.js"></script>
</head>
<body>
    <h1>My Wishlist</h1>
<div id="wishlist-grid" class="wishlist-grid">
    <!-- Wishlist items will be rendered here -->
</div>
</body>
<?php include_once 'footer.php'; ?>
</html> 

 <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }

        .wishlist-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .wishlist-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            width: 280px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease;
        }

        .wishlist-card:hover {
            transform: translateY(-5px);
        }

        .wishlist-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .wishlist-info {
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .wishlist-name {
            font-weight: bold;
            font-size: 16px;
            color: #111;
        }

        .wishlist-brand {
            color: #555;
            font-size: 14px;
        }

        .wishlist-description {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }

        .wishlist-category {
            font-size: 12px;
            font-weight: bold;
            color: #888;
            text-transform: uppercase;
        }
    </style>
