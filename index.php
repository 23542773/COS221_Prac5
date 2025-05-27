<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chief Kompare</title>
    <link rel="icon" type="image/x-icon" href="COS221/img/favicon.ico">
    <link rel="stylesheet" href="COS221/css/search.css" >
</head>

<body>
    <?php include_once 'header.php' ?>

    <main>
        <div id="search">
            <input type="text" class="search-box" placeholder="Search Products...">
        </div>
        
    </main>
    <script>
    const searchBox = document.querySelector('.search-box');
    searchBox.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            const searchTerm = searchBox.value.trim();
            if (searchTerm.length > 0) {
                // Save to localStorage
                localStorage.setItem('search', searchTerm);
                // Redirect to another page
                window.location.href = 'COS221/products.php'; // change to your target page
            }
        }
    });
</script>

    <?php include_once 'COS221/footer.php' ?>
</body>

</html>