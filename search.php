<!DOCTYPE html>
<html>
<head>
    <title>Shopping</title>
</head>

<?php
include 'header.php';
?>
<body>
    <div class="search-bar">
        <form action="search" autocomplete="off" method="POST" role="search">
            <label for="searchBar" id="shoppingID">Shopping</label>
            <div class="input-wrapper">
                <input type="text" name="searchBar" id="searchBar">
                <button id="searchBtn" type="submit">🔍</button>
            </div>
        </form>
    </div>
</body>

<?php
include 'footer.php';
?>
</html>

 <style>
        body {
            background-color: black;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, Helvetica, sans-serif;
        }

        .search-bar {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        #shoppingID {
            background: linear-gradient(to right, darkblue, blue, lightblue);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 36px;
            font-weight: bold;
        }

        .input-wrapper {
            display: flex;
            border: 3px solid #ccc;
            border-radius: 25px;
            overflow: hidden;
            background-color: white;
        }

        #searchBar {
            padding: 10px;
            border: none;
            outline: none;
            width: 450px;
            font-size: 16px;
            border-radius: 0;
        }

        #searchBtn {
            padding: 10px 20px;
            border: none;
            background-color: #3498db;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #searchBtn:hover {
            background-color: #2980b9;
        }
    </style>
