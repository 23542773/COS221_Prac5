var retailers = [];
var currentid;
var currentidx;
var allproducts = [];
var categories = [];

async function viewproduct(id, idx) {
    currentid = id;
    currentidx = idx;
    var view = document.getElementById("view");
    view.style.flex = " 0 0 auto";
    var rating = await getreview(id);
    var allof = await getallof(id);
    for (var i = 0; i < (allof.results).length; i++) {
        (allof.results[i]).retailername = getretailername((allof.results[i]).RID);
    }
    const data = {
        title: products[idx].Name,
        imageSrc: products[idx].Thumbnail,
        description: products[idx].Description,
        rating: rating.averageRating || null,
        reviews: rating.ratings || null,
        prices: allof.results || null
    }

    populateProductDetails(data);

}

function closeview() {
    var view = document.getElementById("view");
    var viewd = document.getElementById("viewdata");
    viewd.innerHTML = '<h2 id="title"></h2><div id="img"><img id="image"></div><p id="description"></p><p id="rating"></p><div id="reviews"><div class="review"><h6 class="reviewname"></h6><p class="reviewtext"></p><p class="reviewrating"></p></div></div><div id="prices"><p class="price"></p></div></div>';
    view.style.flex = 0;
}

document.addEventListener('DOMContentLoaded', async () => {

    var addReviewBtnn = document.getElementById('addreview');
    if (localStorage.getItem("apikey") == null) {
        addReviewBtnn.style.display = "none";
    }

    retailers = await getretailers();
    categories = await getcategories();
    var vcloseiew = document.getElementById("close-button");
    vcloseiew.addEventListener('click', closeview);
    var search = document.getElementById("searchb");
    search.addEventListener("change", searchfil);
    if (localStorage.getItem("search") != null) {
        search.value = localStorage.getItem("search");
        await searchfil();
        await popfil();
    } else
        await getproducts();
});

var products = [];

async function getproducts() {
    const data = {
        api: "GetAllProducts",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        Best: "true",
        limit: "100"
    }
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        products = result.data;
        var p = document.getElementById("products");
        p.innerHTML = "";
        for (var i = 0; i < products.length; i++) {
            var block = createProductBlock(products[i].Thumbnail, products[i].Name, products[i].price, products[i].averageRating, products[i].ProductID, i);
            p.appendChild(block);
        }
        await getallproducts();
        await popfil();

    } catch (error) {
        console.error('Error:', error);
    }
}

async function getproductssearch() {
    const data = {
        api: "GetAllProducts",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        Best: "true",
        limit: "100"
    }
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        products = result.data;
        var p = document.getElementById("products");
        p.innerHTML = "";
        for (var i = 0; i < products.length; i++) {
            var block = createProductBlock(products[i].Thumbnail, products[i].Name, products[i].price, products[i].averageRating, products[i].ProductID, i);
            p.appendChild(block);
        }
        await getallproducts();

    } catch (error) {
        console.error('Error:', error);
    }
}

async function getallproducts() {
    const data = {
        api: "GetAllProducts",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        Best: "true",
        limit: "800"
    }
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        allproducts = result.data;

    } catch (error) {
        console.error('Error:', error);
    }
}

function createProductBlock(imageSrc, titleText, priceText, ratingValue, id, idx) {
    const block = document.createElement('div');
    block.className = 'block';

    const imgDiv = document.createElement('div');
    imgDiv.className = 'img';
    const img = document.createElement('img');
    img.src = imageSrc;
    img.alt = titleText;
    imgDiv.appendChild(img);

    const infoDiv = document.createElement('div');
    infoDiv.className = 'info';

    const title = document.createElement('h3');
    title.className = 'title';
    title.textContent = titleText;

    const price = document.createElement('p');
    price.className = 'price';
    price.textContent = priceText;

    const rating = document.createElement('div');
    rating.className = 'rating';

    // Function to create star elements based on the rating value
    function createStars(ratingValue) {
        const fullStars = Math.floor(ratingValue);
        const halfStar = ratingValue % 1 !== 0;
        const stars = [];

        for (let i = 0; i < fullStars; i++) {
            const star = document.createElement('span');
            star.className = 'star full';
            star.textContent = '★'; // Full star
            stars.push(star);
        }

        if (halfStar) {
            const halfStarElement = document.createElement('span');
            halfStarElement.className = 'star half';
            halfStarElement.textContent = '☆'; // Half star
            stars.push(halfStarElement);
        }

        // Add empty stars if needed (up to 5 stars)
        const emptyStars = 5 - stars.length;
        for (let i = 0; i < emptyStars; i++) {
            const star = document.createElement('span');
            star.className = 'star empty';
            star.textContent = '☆'; // Empty star
            stars.push(star);
        }

        return stars;
    }

    // Append stars to the rating div
    const stars = createStars(ratingValue);
    stars.forEach(star => rating.appendChild(star));

    infoDiv.appendChild(title);
    infoDiv.appendChild(price);
    infoDiv.appendChild(rating);

    block.appendChild(imgDiv);
    block.appendChild(infoDiv);

    block.addEventListener("click", function () {
        viewproduct(id, idx);
    });

    return block;
}

async function getreview(id) {
    const data = {
        api: "rating",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        operation: "get",
        productId: id,
        limit: 8004
    }
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        return result.data;


    } catch (error) {
        console.error('Error:', error);
    }
}

async function getretailers() {
    const data = {
        api: "GetAllRetailers",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        limit: 8004
    }
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        return result.data;


    } catch (error) {
        console.error('Error:', error);
    }
}

async function getcategories() {
    const data = {
        api: "getAllCategories",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        limit: 8004
    }
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        return result.data;


    } catch (error) {
        console.error('Error:', error);
    }
}

// Function to populate the product details
function populateProductDetails(data) {
    // Set title
    document.getElementById("title").textContent = data.title;

    // Set image
    const imgElement = document.getElementById("image");
    imgElement.src = data.imageSrc;
    imgElement.alt = data.title;

    // Set description
    document.getElementById("description").textContent = data.description;

    // Set rating
    rating = document.getElementById("rating");
    rating.innerHTML = "";
    function createStars(ratingValue) {
        const fullStars = Math.floor(ratingValue);
        const halfStar = ratingValue % 1 !== 0;
        const stars = [];

        for (let i = 0; i < fullStars; i++) {
            const star = document.createElement('span');
            star.className = 'star full';
            star.textContent = '★'; // Full star
            stars.push(star);
        }

        if (halfStar) {
            const halfStarElement = document.createElement('span');
            halfStarElement.className = 'star half';
            halfStarElement.textContent = '☆'; // Half star
            stars.push(halfStarElement);
        }

        // Add empty stars if needed (up to 5 stars)
        const emptyStars = 5 - stars.length;
        for (let i = 0; i < emptyStars; i++) {
            const star = document.createElement('span');
            star.className = 'star empty';
            star.textContent = '☆'; // Empty star
            stars.push(star);
        }

        return stars;
    }

    // Append stars to the rating div
    var stars = createStars(data.rating);
    stars.forEach(star => rating.appendChild(star));

    // Populate reviews
    const reviewsContainer = document.getElementById("reviews");
    reviewsContainer.innerHTML = ""; // Clear existing reviews

    data.reviews.forEach(review => {
        const reviewDiv = document.createElement("div");
        reviewDiv.className = "review";

        const reviewName = document.createElement("h6");
        reviewName.className = "reviewname";
        reviewName.textContent = review.Name;

        const reviewText = document.createElement("p");
        reviewText.className = "reviewtext";
        reviewText.textContent = review.Comment;

        const reviewRating = document.createElement("p");
        reviewRating.className = "reviewrating";
        var stars = createStars(review.Rating);
        stars.forEach(star => reviewRating.appendChild(star));

        reviewDiv.appendChild(reviewName);
        reviewDiv.appendChild(reviewText);
        reviewDiv.appendChild(reviewRating);
        reviewsContainer.appendChild(reviewDiv);
    });

    const pricesContainer = document.getElementById("prices");
    pricesContainer.innerHTML = ""; // Clear existing prices
    data.prices.forEach(priceInfo => {
        const retailerDiv = document.createElement("div");
        retailerDiv.className = "retailer";
        const retailerName = document.createElement("h6");
        retailerName.className = "name";
        retailerName.textContent = priceInfo.retailername;
        const price = document.createElement("p");
        price.className = "price";
        price.textContent = priceInfo.price;
        retailerDiv.appendChild(retailerName);
        retailerDiv.appendChild(price);
        pricesContainer.appendChild(retailerDiv);
    });
}

async function getallof(id) {
    const data = {
        api: "GetDistinct",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        table: "listings",
        field: "listings.ProductID",
        search: id,
        sort: "price",
        limit: 100,
        fuzzy: "false"
    };
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        return result.data;


    } catch (error) {
        console.error('Error:', error);
    }
}

function getretailername(id) {
    for (var i = 0; i < retailers.length; i++) {
        if (retailers[i].RetailerID == id) {
            return retailers[i].Name;
        }
    }
    return null;
}

const addReviewBtn = document.getElementById('addreview');
const modal = document.getElementById('reviewModal');
const stars = Array.from(document.querySelectorAll('.star'));
const reviewText = document.getElementById('reviewText');
const submitBtn = document.getElementById('submitReview');
const cancelBtn = document.getElementById('cancelReview');
const errorMessage = document.getElementById('errorMessage');
let selectedRating = 0;

function openModal() {
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    resetModal();
    stars[0].focus();
}
function closeModal() {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    selectedRating = 0;
}

function resetModal() {
    selectedRating = 0;
    reviewText.value = '';
    errorMessage.style.display = 'none';
    stars.forEach(star => {
        star.classList.remove('selected', 'hover');
        star.setAttribute('aria-checked', 'false');
        star.setAttribute('tabindex', '-1');
    });
    stars[0].setAttribute('tabindex', '0');
}

function updateStars(rating) {
    stars.forEach(star => {
        star.classList.toggle('selected', Number(star.dataset.value) <= rating);
        star.setAttribute('aria-checked', Number(star.dataset.value) === rating ? 'true' : 'false');
    });
}

stars.forEach((star, index) => {
    star.addEventListener('click', () => {
        selectedRating = Number(star.dataset.value);
        updateStars(selectedRating);
    });
    star.addEventListener('mouseenter', () => {
        stars.forEach(s => s.classList.remove('hover'));
        for (let i = 0; i <= index; i++) {
            stars[i].classList.add('hover');
        }
    });
    star.addEventListener('mouseleave', () => {
        stars.forEach(s => s.classList.remove('hover'));
    });
    star.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
            e.preventDefault();
            let next = (index + 1) % stars.length;
            stars[next].focus();
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
            e.preventDefault();
            let prev = (index - 1 + stars.length) % stars.length;
            stars[prev].focus();
        } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            selectedRating = Number(star.dataset.value);
            updateStars(selectedRating);
        }
    });
});

addReviewBtn.addEventListener('click', openModal);
cancelBtn.addEventListener('click', closeModal);

submitBtn.addEventListener('click', async () => {
    errorMessage.style.display = 'none';
    if (selectedRating === 0) {
        errorMessage.textContent = 'Please select a star rating.';
        errorMessage.style.display = 'block';
        stars[0].focus();
        return;
    }
    if (!reviewText.value.trim()) {
        errorMessage.textContent = 'Please write a review.';
        errorMessage.style.display = 'block';
        reviewText.focus();
        return;
    }
    // Collect review data
    const reviewData = {
        api: "rating",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        operation: "set",
        rating: selectedRating,
        comment: reviewText.value,
        productId: currentid

    };
    await addrating(reviewData);
    closeview();
    viewproduct(currentid, currentidx);
    closeModal();
    // For example, append review to #reviews div if required
    //addReviewToPage(reviewData);
});

async function addrating(data) {
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        console.log(result.data);


    } catch (error) {
        console.error('Error:', error);
    }
}

async function searchproducts(searchvalue) {
    const data = {
        api: "GetAllProducts",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        search: { Name: searchvalue },
        Best: "true",
        limit: "800"
    }
    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful registration
        const result = await response.json();
        products = result.data;
        var p = document.getElementById("products");
        p.innerHTML = "";
        for (var i = 0; i < products.length; i++) {
            var block = createProductBlock(products[i].Thumbnail, products[i].Name, products[i].price, products[i].averageRating, products[i].ProductID, i);
            p.appendChild(block);
        }


    } catch (error) {
        console.error('Error:', error);
    }
}

async function searchfil() {
    var search = document.getElementById("searchb");
    var searchitem = search.value;
    if (searchitem.trim() == "") {
        localStorage.removeItem("search");
        await getproductssearch();
    } else {
        localStorage.setItem("search", searchitem);
        await searchproducts(searchitem);
        allproducts = products;
    }
}

async function popfil() {
    const dropdown = document.getElementById('categoryDropdown');
    const label = document.querySelector('.dropdown-label');
    const optionsContainer = document.getElementById('dropdownOptions');

    categories.categories.forEach(category => {
        const option = document.createElement('div');
        option.className = 'dropdown-option';
        option.setAttribute('role', 'option');
        option.tabIndex = -1;
        option.textContent = category;
        option.dataset.value = category;
        optionsContainer.appendChild(option);

        option.addEventListener('click', () => {
            label.textContent = category;
            categoriesfill(category);
            closeDropdown();
            console.log('Selected category:', category);
        });
    });

    // Create "All" option
    const allOption = document.createElement('div');
    allOption.className = 'dropdown-option';
    allOption.setAttribute('role', 'option');
    allOption.tabIndex = -1;
    allOption.textContent = "All";
    allOption.dataset.value = "All";
    optionsContainer.appendChild(allOption);

    allOption.addEventListener('click', () => {
        label.textContent = "All";
        categoriesfill("All");
        closeDropdown();
        console.log('Selected category:', "All");
    });

    // Function to open the dropdown
    function openDropdown() {
        optionsContainer.classList.add('open');
        dropdown.setAttribute('aria-expanded', 'true');
    }

    // Function to close the dropdown
    function closeDropdown() {
        optionsContainer.classList.remove('open');
        dropdown.setAttribute('aria-expanded', 'false');
    }

    // Function to toggle the dropdown
    function toggleDropdown() {
        if (optionsContainer.classList.contains('open')) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }

    // Event listener for the dropdown label
    label.addEventListener('click', () => {
        toggleDropdown();
    });

    // Event listener for keyboard navigation
    dropdown.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleDropdown();
        }
        if (e.key === 'Escape') {
            closeDropdown();
            dropdown.focus();
        }
    });

}


function categoriesfill(data) {
    if (data == "All") {
        if (localStorage.getItem("search") != null) {
            search.value = localStorage.getItem("search");
            searchfil();
        } else
            fillallproducts();
        return;
    }
    products = [];
    for (var i = 0; i < allproducts.length; i++) {
        if (allproducts[i].Category == data) {
            products.push(allproducts[i]);
        }
    }
    var p = document.getElementById("products");
    p.innerHTML = "";
    for (var i = 0; i < products.length; i++) {
        var block = createProductBlock(products[i].Thumbnail, products[i].Name, products[i].price, products[i].averageRating, products[i].ProductID, i);
        p.appendChild(block);
    }
}

async function fillallproducts() {
    const data = {
        api: "GetAllProducts",
        apikey: localStorage.getItem('apikey') != null ? localStorage.getItem('apikey') : "dafdda51e1bf23147967c1041cac5d6b",
        Best: "true",
        limit: "100"
    };

    try {
        // Send data to the API
        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });


        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.data || 'Get Products failed');
        }

        // Handle successful response
        const result = await response.json();
        products = result.data;
        var p = document.getElementById("products");
        p.innerHTML = "";
        for (var i = 0; i < products.length; i++) {
            var block = createProductBlock(products[i].Thumbnail, products[i].Name, products[i].price, products[i].averageRating, products[i].ProductID, i);
            p.appendChild(block);
        }

    } catch (error) {
        console.error('Error:', error);
    }
}


