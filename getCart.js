async function fetchCart() {
    const apiKey = localStorage.getItem('apikey') || 'a1b2c3d4e5';

    const payload = {
        api: "addToCart",
        apikey: apiKey,
        operation: "get"
    };

    try {
        const response = await fetch("http://localhost:8001/api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.status === "success" && result.data?.carts?.length > 0) {
            renderCart(result.data.carts);
        } else {
            document.getElementById("cart-grid").innerHTML = "<p>Your cart is empty.</p>";
        }

    } catch (error) {
        console.error("Failed to fetch cart:", error);
        document.getElementById("cart-grid").innerHTML = "<p>Error loading cart.</p>";
    }
}

function renderCart(items) {
    const container = document.getElementById("cart-grid");
    container.innerHTML = "";

    items.forEach(item => {
        const card = document.createElement("div");
        card.className = "cart-card";
        card.setAttribute("data-id", item.PID);

        card.innerHTML = `
            <img src="${item.Thumbnail}" alt="${item.Name}">
            <div class="cart-info">
                <div class="cart-category">${item.Category}</div>
                <div class="cart-name">${item.Name}</div>
                <div class="cart-brand">Brand: ${item.Brand}</div>
                <div class="cart-description">${item.Description}</div>
                <div class="cart-quantity">Quantity: ${item.Quantity}</div>
                <button onclick="deleteFromCart('${item.PID}')" class="delete-button">Delete</button>
            </div>
        `;

        container.appendChild(card);
    });
}


