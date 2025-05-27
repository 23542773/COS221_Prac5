 var retailers=[];
var currentid;
var currentidx;
 
 async function viewproduct(id,idx){
    currentid=id;
    currentidx=idx;
    var view= document.getElementById("view");
    view.style.flex=2;
    var rating= await getreview(id);
    var allof=await getallof(id);
    for(var i=0;i<(allof.results).length;i++){
        (allof.results[i]).retailername=getretailername((allof.results[i]).RID);
    }
    const data={
        title: products[idx].Name,
        imageSrc: products[idx].Thumbnail, 
        description: products[idx].Description,
        rating: rating.averageRating || null,
        reviews: rating.ratings || null,
        prices: allof.results||null
    }

    populateProductDetails(data);

}

function closeview(){
    var view= document.getElementById("view");
    var viewd= document.getElementById("viewdata");
    viewd.innerHTML='<h2 id="title"></h2><div id="img"><img id="image"></div><p id="description"></p><p id="rating"></p><div id="reviews"><div class="review"><h6 class="reviewname"></h6><p class="reviewtext"></p><p class="reviewrating"></p></div></div><div id="prices"><p class="price"></p></div></div>';
    view.style.flex=0;
}

document.addEventListener('DOMContentLoaded', async () => {
    retailers= await getretailers();
    var vcloseiew= document.getElementById("close-button");
    vcloseiew.addEventListener('click',closeview)
    getproducts();
});

var products=[];

async function  getproducts(){
    const data={
        api:"GetAllProducts",
        apikey:localStorage.getItem('apikey')!=null ? localStorage.getItem('apikey'):"dafdda51e1bf23147967c1041cac5d6b",
        Best:"true",
        limit:"100" 
    }
    try {
            // Send data to the API
            const response = await fetch('http://localhost:8001/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            // Check if the response is ok (status in the range 200-299)
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.data || 'Get Products failed');
            }

            // Handle successful registration
            const result = await response.json();
            products=result.data;
            var p= document.getElementById("products");
            p.innerHTML="";
            for(var i=0;i<products.length;i++){
                var block=createProductBlock(products[i].Thumbnail,products[i].Name,products[i].price,products[i].averageRating,products[i].ProductID,i);
                p.appendChild(block);
            }


        } catch (error) {
            console.error('Error:', error);
        }
}
function createProductBlock(imageSrc, titleText, priceText, ratingValue, id,idx) {
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

    block.addEventListener("click", function() {
        viewproduct(id,idx);
    });

    return block;
}

async function getreview(id){
    const data={
        api:"rating",
        apikey:localStorage.getItem('apikey')!=null ? localStorage.getItem('apikey'):"dafdda51e1bf23147967c1041cac5d6b",
        operation:"get",
        productId:id,
        limit:8004
    }
    try {
            // Send data to the API
            const response = await fetch('http://localhost:8001/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            // Check if the response is ok (status in the range 200-299)
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

async function getretailers(){
    const data={
        api:"GetAllRetailers",
        apikey:localStorage.getItem('apikey')!=null ? localStorage.getItem('apikey'):"dafdda51e1bf23147967c1041cac5d6b",
        limit:8004
    }
    try {
            // Send data to the API
            const response = await fetch('http://localhost:8001/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            // Check if the response is ok (status in the range 200-299)
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


// Sample data for demonstration
/*const productData = {
    title: "Sample Product",
    imageSrc: "https://via.placeholder.com/150", // Replace with actual image URL
    description: "This is a sample product description.",
    rating: 4.5,
    reviews: [
        {
            Name: "John Doe",
            Comment: "Great product! Highly recommend.",
            rating: 5
        },
        {
            name: "Jane Smith",
            text: "Good value for the price.",
            rating: 4
        }
    ],
    prices: [
        {
            retailer: "Retailer A",
            price: "$29.99"
        },
        {
            retailer: "Retailer B",
            price: "$27.49"
        }
    ]
};*/

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
    rating=document.getElementById("rating");
    rating.innerHTML="";
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

    const wishlistBtn = document.getElementById("addwishlist");
    wishlistBtn.onclick = () => {
    const productId = data.prices?.[0]?.ProductID;
    if (!productId) {//check if productID exists. If it can get the ID to add and send to call
        console.error("ProductID is missing from data.prices:", data.prices);
        alert("Could not add to wishlist: Missing product ID.");
        return;
    }
    console.log("Adding ProductID to wishlist:", productId);
    addToWishlist(productId);
    };
}

async function getallof(id){
    const data={
        api:"GetDistinct",
        apikey:localStorage.getItem('apikey')!=null ? localStorage.getItem('apikey'):"dafdda51e1bf23147967c1041cac5d6b",
        table:"listings", 
        field:"listings.ProductID",
        search:id,
        sort:"price",
        limit:100,
        fuzzy:"false"
    };
    try {
            // Send data to the API
            const response = await fetch('http://localhost:8001/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            // Check if the response is ok (status in the range 200-299)
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

function getretailername(id){
    for(var i=0;i<retailers.length;i++){
        if(retailers[i].RetailerID==id){
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
      } else if(e.key === 'Enter' || e.key === ' ') {
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
        api:"rating",
        apikey:localStorage.getItem('apikey')!=null ? localStorage.getItem('apikey'):"dafdda51e1bf23147967c1041cac5d6b",
        operation:"set",
        rating: selectedRating,
        comment: reviewText.value,
        productId:currentid

    };
    await addrating(reviewData);
    closeview();
    getproducts();
    viewproduct(currentid,currentidx);
    // Here you would typically send reviewData to backend or append to UI
    closeModal();
    // For example, append review to #reviews div if required
    //addReviewToPage(reviewData);
});

async function addrating(data){
     try {
            // Send data to the API
            const response = await fetch('http://localhost:8001/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            // Check if the response is ok (status in the range 200-299)
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
