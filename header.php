<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        header {
            width: 100vw;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fcfaf9;
            padding: 1rem 2rem;
        }


        header > div {
            font-weight: 700;
            font-size: 1.4rem;
            color: #262626;
            margin-right: 3rem;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }


        nav ul {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 2rem;
        }



        nav ul li {
            cursor: pointer;
            font-weight: 500;
            color: #262626;
            position: relative;
            padding: 0.25rem 0;
            transition: color 0.3s ease;
        }



        nav ul li a {
            cursor: pointer;
            font-weight: 500;
            color: #262626;
            position: relative;
            padding: 0.25rem 0;
            transition: color 0.3s ease;
            text-decoration: none;
        }



        nav ul li:hover {
            color: #2edf84;
        }



        nav ul li a:hover {
            color: #2edf84;
        }



        .logo {
            max-height: 40px;
            object-fit: contain;
        }



        #logop {
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
            color: #262626;
            user-select: none;
        }



        .active {
            color: #747378;
        }



        .username-display {
            font-weight: 600;
            color: #262626;
            user-select: none;
            padding-left: 1rem;
            align-self: center;
        }
    </style>
</head>
<body>



<div id="header-container"></div>



<script>
(function() {
    // Retrieve data from localStorage
    const apikey = localStorage.getItem('apikey');
    const username = localStorage.getItem('name') || '';
    const isLoggedIn = apikey !== null;
    console.log(isLoggedIn);



    // Get current page filename
    const currentPage = window.location.pathname.split('/').pop();



    // Map navigation links: text, href, and filename for matching active state
    const navLinks = [
        { text: 'Search', href: 'index.php', page: 'index.php' },
        { text: 'Products', href: 'COS221/products.php', page: 'products.php' },
        { text: 'Dashboard', href: 'COS221/toprated.php', page: 'tpprated.php' },
        { text: 'Wishlist', href: 'COS221/wishlist.php', page: 'wishlist.php' },
        { text: 'Orders', href: 'COS221/orders.php', page: 'orders.php' },
        { text: 'Cart', href: 'COS221/cart.php', page: 'cart.php' },
    ];



    // Create the header element
    const header = document.createElement('header');



    // Logo container with image and text
    const logoContainer = document.createElement('div');
    logoContainer.className = 'logo-container';
    const logoImg = document.createElement('img');
    logoImg.className = 'logo';
    logoImg.src = 'COS221/img/logo.png';
    logoImg.alt = 'Chief Kompare Logo';
    const logoText = document.createElement('p');
    logoText.id = 'logop';
    logoText.textContent = 'Chief Kompare';
    logoContainer.appendChild(logoImg);
    logoContainer.appendChild(logoText);



    // Navigation container
    const nav = document.createElement('nav');
    const ul = document.createElement('ul');



    // Build standard nav links
    navLinks.forEach(({text, href, page}) => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = href;
        a.textContent = text;
        if (page === currentPage) {
            a.classList.add('active');
        }
        li.appendChild(a);
        ul.appendChild(li);
    });



    // Append Login/Logout and username
    if (isLoggedIn) {
        // Logout link
        const logoutLi = document.createElement('li');
        const logoutA = document.createElement('a');
        logoutA.href = 'logout.php';
        logoutA.textContent = 'Logout';
        if (currentPage === 'logout.php') {
            logoutA.classList.add('active');
        }
        logoutLi.appendChild(logoutA);
        ul.appendChild(logoutLi);



        // Username display (not a link)
        const usernameLi = document.createElement('li');
        usernameLi.className = 'username-display';
        usernameLi.textContent = username ? username : 'User ';
        ul.appendChild(usernameLi);
    } else {
        // Login link
        const loginLi = document.createElement('li');
        const loginA = document.createElement('a');
        loginA.href = 'login.php';
        loginA.textContent = 'Login';
        if (currentPage === 'login.php') {
            loginA.classList.add('active');
        }
        loginLi.appendChild(loginA);
        ul.appendChild(loginLi);
    }



    nav.appendChild(ul);



    // Append logo and nav to header
    header.appendChild(logoContainer);
    header.appendChild(nav);



    // Insert header into the DOM
    const headerContainer = document.getElementById('header-container');
    if (headerContainer) {
        headerContainer.appendChild(header);
    } else {
        // If the container div doesn't exist, append directly to body
        document.body.insertBefore(header, document.body.firstChild);
    }
})();
</script>



</body>
</html>