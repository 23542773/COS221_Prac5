async function addToCart(productId) {
    console.log("Trying to add to cart:", productId);
    const apiKey = localStorage.getItem('apikey') || 'a1b2c3d4e5';

    try {
        // Fetch existing cart
        const payload = {
            api: "addToCart",
            apikey: apiKey,
            operation: "get"
        };

        const response = await fetch("http://localhost:8001/api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const cartResult = await response.json();
        const cartItems = cartResult?.data?.carts || [];

        //Checks if product is already in cart
        const existingItem = cartItems.find(item => item.PID === productId);
        const newQuantity = existingItem ? existingItem.Quantity + 1 : 1;

        //Sends updated quantity to the API. Allowing for incrementation
        const updatePayload = {
            api: "addToCart",
            apikey: apiKey,
            operation: "set",
            PID: productId,
            Quantity: newQuantity
        };

        const updateResponse = await fetch('http://localhost:8001/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatePayload),
        });

        if (!updateResponse.ok) {
            const errorData = await updateResponse.json();
            throw new Error(errorData.data || 'Add to cart failed');
        }

        const result = await updateResponse.json();
        alert(`Product added to cart! (Quantity: ${newQuantity})`);
        console.log("Cart update result:", result.data);

    } catch (error) {
        console.error('Error adding to cart:', error);
        alert("Failed to add product to cart.");
    }
}
