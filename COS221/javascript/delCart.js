async function deleteFromCart(productId) {
    const apiKey = localStorage.getItem('apikey') || 'a1b2c3d4e5';

    const payload = {
        api: "addToCart",
        apikey: apiKey,
        operation: "unset",
        PID: productId
    };

    try {
        const response = await fetch("http://localhost:8001/api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.status === "success") {
            alert("Product removed from cart.");
            console.log("Cart remove response:", result);

            fetchCart();
        } else {
            const message = result?.message || "Failed to remove item from cart.";
            alert(message);
            console.warn("Cart deletion failed:", result);
        }

    } catch (error) {
        console.error("Error removing from cart:", error);
        alert("An error occurred while removing item.");
    }
}
