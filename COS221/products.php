<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
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
                    <div class="block">
                        <div class="img"><img/></div>
                        <div class="info">
                            <h3 class="title"></h3>
                            <p class="price"></p>
                            <div class="rating"></div>
                        </div>
                    </div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                    <div class="block"></div>
                </div>
                <div id="view"></div>
            </section>
        </main>
        <?php include_once 'footer.php' ?>
    </body>
</html>