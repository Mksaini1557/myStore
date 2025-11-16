# Food Ordering System with QR Code Admin Panel

## Project Overview
A comprehensive web-based food ordering system that allows customers to browse menu items, place orders, and track their order status. The system includes an admin panel with QR code scanning functionality for efficient order management and delivery tracking.

---

## Table of Contents
- [Project Objective](#project-objective)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Dataset Source](#dataset-source)
- [System Architecture](#system-architecture)
- [Installation & Setup](#installation--setup)
- [Database Schema](#database-schema)
- [Steps Performed](#steps-performed)
- [Key Results](#key-results)
- [Screenshots](#screenshots)
- [Future Enhancements](#future-enhancements)
- [Contributors](#contributors)
- [License](#license)

---

## Project Objective

The primary objective of this project is to develop a full-stack web application that:
1. Enables customers to browse food items and place orders online
2. Provides real-time order tracking with security codes
3. Implements a QR code-based admin panel for efficient order management
4. Tracks order lifecycle from cooking → cooked → delivered
5. Demonstrates end-to-end web development with modern technologies

---

## Features

### Customer Features
- Browse food menu with images and prices
- Add items to cart with customizable options
- Place orders with automatic security code generation
- View order history and real-time status updates
- QR code generation for order pickup

### Admin Features
- Dashboard with all active orders
- QR code scanner for quick order lookup
- Order status management (Cooking → Cooked → Delivered)
- Real-time order list updates
- Customer order details view

---

## Technology Stack

### Frontend
- HTML5, CSS3, Bootstrap 5.3.3
- JavaScript (ES6+)
- Bootstrap Icons
- HTML5 QR Code Scanner Library

### Backend
- PHP 8.x
- MySQL Database
- RESTful API design

### Libraries & Tools
- `html5-qrcode` - QR code scanning
- `qrcode.js` - QR code generation
- Bootstrap Modal - UI components
- Fetch API - Asynchronous data handling

---

## Dataset Source

### Database: `mystore`
- **Source**: Custom-designed relational database
- **Size**: Dynamic (grows with orders)
- **Format**: MySQL tables

### Key Tables:
1. **orders** - Stores order information
   - Fields: `id`, `order_group_id`, `user_id`, `user_name`, `status`, `total_amount`, `security_code`, `created_at`

2. **order_item** - Stores individual order items
   - Fields: `id`, `order_group_id`, `item_name`, `option_text`, `price`

---

## System Architecture

