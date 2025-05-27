async function fetchWishlist() {
    const API_KEY = localStorage.getItem('apikey') || 'a1b2c3d4e5';

    const payload = {
        api: "wishlist",
        apikey: API_KEY,
         //apikey: 'a1b2c3d4e5',this was to test the data with
        operation: "get"
    };

    try {
        const response = await fetch("http://localhost:8001/api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.status === "success" && data.data?.wishlist?.length > 0) {
            renderWishlist(data.data.wishlist);
        } else {
            document.getElementById("wishlist-grid").innerHTML = "<p>No items in wishlist or error loading data.</p>";
        }
    } catch (err) {
        console.error("Fetch wishlist failed:", err);
        document.getElementById("wishlist-grid").innerHTML = "<p>Error loading wishlist.</p>";
    }
}

function renderWishlist(items) {
    const container = document.getElementById("wishlist-grid");
    container.innerHTML = "";

    items.forEach(item => {
        const productId = item.ProductID;

        const card = document.createElement("div");
        card.className = "wishlist-card";
        card.setAttribute("data-id", productId);

        card.innerHTML = `
            <img src="${item.Thumbnail}" alt="${item.Name}">
            <div class="wishlist-info">
                <div class="wishlist-category">${item.Category}</div>
                <div class="wishlist-name">${item.Name}</div>
                <div class="wishlist-brand">Brand: ${item.Brand}</div>
                <div class="wishlist-description">${item.Description}</div>
                <button class="remove-btn" data-product-id="${productId}" onclick="handleRemoveFromElement(this)">Remove</button>
            </div>
        `;

        container.appendChild(card);
    });
}