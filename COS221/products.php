<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chief Kompare</title>
        <link rel="icon" type="image/x-icon" href="img/favicon.ico">
        <link rel="stylesheet" href="css/products.css" >
        <script src="javascript/products.js" defer></script>
    </head>
    <body>
        <?php include_once 'header.php' ?>
        <main>
            <div id="search">
                <input type="text" class="search-box" placeholder="Search Products...">
            </div>
            <section id="productsview">
                <div id="filters"></div>
                <div id="products">
                </div>
                <div id="view">
                    <button id="close-button" aria-label="Close button" title="Close">×</button>
                    <div id="viewdata">
                        <h2 id="title"></h2>
                        <div id="img"><img id="image"></div>
                        <p id="description"></p>
                        <p id="rating"></p>
                        <div id="reviews">
                            <div class="review">
                                <h6 class="reviewname"></h6>
                                <p class="reviewtext"></p>
                                <p class="reviewrating"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        <?php include_once 'footer.php' ?>
    </body>
</html>