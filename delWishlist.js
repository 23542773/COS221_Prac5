function handleRemove(productId, buttonElement) {
    if (!confirm("Are you sure you want to remove this item from your wishlist?")) return;

    const payload = {
        api: "wishlist",
        apikey: "a1b2c3d4e5",
        operation: "unset",
        ProductID: productId
    };

    fetch('delWishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = buttonElement.closest('.wishlist-card');
            card.remove();
        } else {
            alert("Failed to remove item: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("An error occurred while trying to remove the item.");
    });
}
