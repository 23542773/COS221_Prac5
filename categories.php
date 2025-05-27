<?php
require_once 'getCategories.php';

$categories = fetchCategories();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Categories</title>
</head>
<?php
include 'header.php';
?>
<body>
    <div class="category-list">
        <h1>Available Categories</h1>

        <?php if ($categories['status'] === 'success' && !empty($categories['data']['categories'])): ?>
            <ul>
                <?php foreach ($categories['data']['categories'] as $category): ?>
                    <li><?= htmlspecialchars($category) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Error loading categories: <?= htmlspecialchars($categories['message'] ?? 'Unknown error') ?></p>
        <?php endif; ?>
    </div>
</body>
<?php 
include 'footer.php';
?>
</html>

 <style>
        body {
            font-family: Arial, sans-serif;
        }
        .category-list {
            margin: 40px auto;
            width: 60%;
            max-width: 600px;
        }
        .category-list h1 {
            text-align: center;
        }
        .category-list ul {
            list-style-type: none;
            padding: 0;
        }
        .category-list li {
            padding: 10px;
            margin: 5px 0;
            background-color: #f0f8ff;
            border-left: 4px solid #007acc;
        }
    </style>