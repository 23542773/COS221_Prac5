//DOM elements
const preferences = document.getElementById('preferences');
const togglePreferences = document.getElementById('togglePreferences');
const darkModeToggle = document.getElementById('darkModeToggle');
const ratingContainer = document.getElementById('rating-container');
const topProductsContainer = document.querySelector('.product-container');

document.addEventListener('DOMContentLoaded', function () {
    getReviews();
    getTopRated();
});

function getReviews() {
    fetch('../api_cos221.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            "api": "rating",
            "operation": "get",
            "apikey": "a1b2c3d4e5"
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log(data);
            processReviews(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function getTopRated() {
    fetch('../api_cos221.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            "api": "GetAllProducts",
            "apikey": "a1b2c3d4e5",
            "sort": "averageRating",
            "order": "DESC",
            "limit": 10
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log(data);
            processTopRated(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function processReviews(reviews) {

    reviews.data.userRatings.forEach(review => {
        const reviewDiv = document.createElement('div');
        reviewDiv.classList.add('review-item');

        const nameElement = document.createElement('p');
        nameElement.textContent = review.productName.substring(0, review.productName.indexOf(' ', 25)) + '...';

        const dateElement = document.createElement('p');
        dateElement.textContent = review.Date;

        const ratingElement = document.createElement('p');
        ratingElement.textContent = `Rating: ${review.Rating} / 5`;

        const commentElement = document.createElement('p');
        commentElement.textContent = review.Comment;

        reviewDiv.appendChild(nameElement);
        reviewDiv.appendChild(dateElement);
        reviewDiv.appendChild(ratingElement);
        reviewDiv.appendChild(commentElement);

        ratingContainer.appendChild(reviewDiv);
    });
}

function processTopRated(products) {
    products.data.forEach(product => {
        const  productDiv = document.createElement('div');
        productDiv.classList.add('product');

        const Thumbnail = document.createElement('img');
        Thumbnail.src = product.Thumbnail;

        productDiv.appendChild(Thumbnail);

        topProductsContainer.appendChild(productDiv);
    });
}

darkModeToggle.addEventListener('change', function () {
    const page = document.querySelector('.page');

    if (darkModeToggle.checked) {
        page.style.background = '#262626';
    } else {
        page.style.background = '#fcfaf9';
    }
});