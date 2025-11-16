# Food Order Application

## Overview
This project is a food ordering application that allows users to browse a menu, add items to their cart, and place orders. Users can also view their past orders.

## File Structure
- **index.html**: The main page displaying the menu items and allowing users to add items to their cart.
- **cart.html**: Displays the user's shopping cart, showing added items, total price, and a button to confirm the order.
- **orders.html**: Displays the user's past orders fetched from the database, including item names, prices, IDs, and security codes.
- **app.js**: Contains JavaScript logic for managing the shopping cart, including adding items, checking out, and saving order data to local storage. It also handles sending order data to the server after checkout.
- **styles.css**: Contains CSS styles for the application, ensuring a consistent and appealing layout across all pages.
- **php/config.php**: Contains database connection settings for connecting to the `myStore` database.
- **php/checkout.php**: Handles the checkout process, receives order data from `app.js`, generates a unique security code, and saves order details to the database.
- **php/get_orders.php**: Retrieves the user's past orders from the database and returns them in a format suitable for display on `orders.html`.
- **php/database.sql**: Contains SQL commands to create the necessary tables in the `myStore` database for storing order details.

## Setup Instructions
1. Clone the repository to your local machine.
2. Import the `database.sql` file into your MySQL database to create the necessary tables.
3. Update the `config.php` file with your database connection settings.
4. Open `index.html` in your web browser to start using the application.

## Features
- Users can add items to their cart and view the total price.
- Users can confirm their orders, which are then saved to the database with a unique security code for verification.
- Users can view their past orders on the `orders.html` page.

## Technologies Used
- HTML
- CSS
- JavaScript
- PHP
- MySQL

## Future Enhancements
- Implement user authentication to allow users to log in and view their order history.
- Add more menu items and options for customization.
- Improve the UI/UX for a better user experience.