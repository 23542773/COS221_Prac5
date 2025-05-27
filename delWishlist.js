// function handleRemove(productId, buttonElement) {
//     if (!confirm("Are you sure you want to remove this item from your wishlist?")) return;

//     API_KEY = localStorage.getItem('apikey')!=null ? localStorage.getItem('apikey'):"dafdda51e1bf23147967c1041cac5d6b";
//     //"a1b2c3d4e5",
//     const payload = {
//         api: "wishlist",
//         apikey: API_KEY,
//         operation: "unset",
//         ProductID: productId
//     };

//     fetch('delWishlist.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json'
//         },
//         body: JSON.stringify(payload)
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.status === 'success') {
//             const card = buttonElement.closest('.wishlist-card');
//             card.remove();
//         } else {
//             alert("Failed to remove item: " + (data.message || "Unknown error"));
//         }
//     })
//     .catch(err => {
//         console.error("Error:", err);
//         alert("An error occurred while trying to remove the item.");
//     });
// }

function handleRemoveFromElement(buttonElement) {
    const productId = buttonElement.getAttribute('data-product-id');
    handleRemove(parseInt(productId), buttonElement);
}


function handleRemove(productId, buttonElement) {
    if (!confirm("Are you sure you want to remove this item from your wishlist?")) return;

    const API_KEY = localStorage.getItem('apikey') || "a1b2c3d4e5";

    const payload = {
        api: "wishlist",
        apikey: API_KEY,
        operation: "unset",
        ProductID: productId
    };

    fetch("http://localhost:8001/api.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = buttonElement.closest('.wishlist-card');
            if (card) card.remove();
        } else {
            alert("Failed to remove item: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("An error occurred while trying to remove the item.");
    });
}
