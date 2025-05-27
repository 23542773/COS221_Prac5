Tech stack:
LAMP - Linux, Apache, MySQL and PHP


## Technologies Used
- Frontend: HTML5, CSS3, JavaScript,
- Backend: PHP 8.1+
- Database: MariaDB 10.6+
- Version Control: Git
- Package Manager: Composer


how to run application:

assuming database import:
	-import the given sql dump to chosen rdbms
	-run locally = xxamp with chosen rdmbs or run through wheatly (how we are demoing, )
	-http://localhost for running locally and use wheatly path for using wheatly


2. Configuration
Copy the configuration template:

bash
cp config/config.example.php config/config.php
Edit config/config.php with your database credentials:

php
define('DB_HOST', 'localhost');
define('DB_NAME', 'compareit');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
3. Dependency Installation
Install PHP dependencies:

bash
composer install
4. Web Server Setup
Point your web server to the public/ directory as the document root

Ensure the following directories are writable:

uploads/

cache/

Running the Application
Start your web server

Access the application in your browser at:

http://localhost
Default Accounts
For testing purposes, the following accounts are pre-configured:

Admin Account:

Email: admin@compareit.com

Password: Admin@1234

Regular User:

Email: user@compareit.com

Password: User@1234

API Integration
The application uses the following external APIs:

DummyJSON (for product data)

SerpAPI (for price comparison)

API keys should be added to config/config.php:

php
define('DUMMYJSON_API_KEY', 'your_api_key');
define('SERPAPI_API_KEY', 'your_api_key');
Troubleshooting
Database connection issues:

Verify credentials in config.php

Check MariaDB service is running

Permission errors:

Ensure web server has write access to uploads/ and cache/

Missing dependencies:

Run composer install again

Check PHP version meets requirements