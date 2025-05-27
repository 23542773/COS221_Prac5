async function addToWishlist(productId) {
    const data = {
        api: "wishlist",
        apikey: localStorage.getItem('apikey') || "a1b2c3d4e5",
        operation: "set",
        ProductID: productId
    };

    try {
        const response = await fetch('http://localhost:8001/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok) {
            const message = result?.data || 'Wishlist operation failed';
            if (response.status === 409) {
                alert("This product is already in your wishlist.");
            } else {
                throw new Error(message);
            }
            return;
        }

        alert("Added to wishlist!");
        console.log(result.data);

    } catch (error) {
        console.error("Error adding to wishlist:", error);
        alert("Failed to add to wishlist.");
    }
}
