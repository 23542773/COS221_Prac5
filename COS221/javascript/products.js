 var retailers=[];
 
 async function viewproduct(id,idx){
    var view= document.getElementById("view");
    view.style.flex=2;
    var rating= await getreview(id);
    var allof=await getallof(id);
    for(var i=0;i<(allof.results).length;i++){
        (allof.results[i]).retailername=getretailername((allof.results[i]).RID);
    }
    console.log(allof.results);
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
    viewd.innerHTML='<h2 id="title"></h2><div id="img"><img id="image"></div><p id="description"></p><p id="rating"></p><div id="reviews"><div class="review"><h6 class="reviewname"></h6><p class="reviewtext"></p><p class="reviewrating"></p></div></div>';
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
            const response = await fetch('../../api_cos221.php', {
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
            console.log(products);
            var p= document.getElementById("products");
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
            const response = await fetch('../../api_cos221.php', {
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
            const response = await fetch('../../api_cos221.php', {
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
}

async function getallof(id){
    const data={
        api:"GetDistinct",
        apikey:"dafdda51e1bf23147967c1041cac5d6b",
        table:"listings", 
        field:"listings.ProductID",
        search:id,
        sort:"price",
        limit:100,
        fuzzy:"false"
    };
    try {
            // Send data to the API
            const response = await fetch('../../api_cos221.php', {
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