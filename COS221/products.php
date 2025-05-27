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
                        <div id="prices">
                            <div class="reatiler">
                                <h6 class="name"></h6>
                                <p class="price"></p>
                            </div>
                        </div>
                    </div>
                    <button id="addreview">Add Review</button>
                </div>
            </section>
            <div id="reviewModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div id="reviewModalContent">
                <h3 id="modalTitle">Add Your Review</h3>
                <div class="stars" role="radiogroup" aria-label="Star rating">
                <span class="star" data-value="1" role="radio" aria-checked="false" tabindex="0" aria-label="1 star">★</span>
                <span class="star" data-value="2" role="radio" aria-checked="false" tabindex="-1" aria-label="2 stars">★</span>
                <span class="star" data-value="3" role="radio" aria-checked="false" tabindex="-1" aria-label="3 stars">★</span>
                <span class="star" data-value="4" role="radio" aria-checked="false" tabindex="-1" aria-label="4 stars">★</span>
                <span class="star" data-value="5" role="radio" aria-checked="false" tabindex="-1" aria-label="5 stars">★</span>
                </div>
                <textarea id="reviewText" placeholder="Write your review here..." aria-label="Review text"></textarea>
                <div class="error-message" id="errorMessage" aria-live="assertive" style="display:none;"></div>
                <button id="submitReview">Submit</button>
                <button id="cancelReview">Cancel</button>
            </div>
            </div>
        </main>
        <?php include_once 'footer.php' ?>
    </body>
</html>