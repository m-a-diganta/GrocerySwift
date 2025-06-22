<?php

session_start();
require_once 'database.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php?error=empty_cart');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $errors = [];
    
    // Personal Information
    $first_name = isset($_POST['first_name']) ? clean_input($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? clean_input($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
    
    // Shipping Information
    $address = isset($_POST['address']) ? clean_input($_POST['address']) : '';
    $city = isset($_POST['city']) ? clean_input($_POST['city']) : '';
    $state = isset($_POST['state']) ? clean_input($_POST['state']) : '';
    $zip_code = isset($_POST['zip_code']) ? clean_input($_POST['zip_code']) : '';
    $country = isset($_POST['country']) ? clean_input($_POST['country']) : '';
    
    // Payment Information
    $payment_method = isset($_POST['payment_method']) ? clean_input($_POST['payment_method']) : '';
    
    // Validate required fields
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($zip_code)) $errors[] = "ZIP code is required";
    if (empty($country)) $errors[] = "Country is required";
    if (empty($payment_method)) $errors[] = "Payment method is required";
    
    // If validation passes, process the order
    if (empty($errors)) {
        // Format shipping address
        $shipping_address = "$address, $city, $state $zip_code, $country";
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        // Apply tax (for example, 8%)
        $tax_rate = 0.08;
        $tax_amount = $total_amount * $tax_rate;
        $total_with_tax = $total_amount + $tax_amount;
        
        // Create new user if not logged in but wants to create account
        if (!$user_id && isset($_POST['create_account']) && $_POST['create_account'] == 1) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (first_name, last_name, email, password, address, phone) 
                    VALUES ('$first_name', '$last_name', '$email', '$password', '$shipping_address', '$phone')";
            
            if ($conn->query($sql) === TRUE) {
                $user_id = $conn->insert_id;
                $_SESSION['user_id'] = $user_id;
            } else {
                $errors[] = "Error creating user account: " . $conn->error;
            }
        }
        
        // If no errors, proceed with order creation
        if (empty($errors)) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert order
                $status = 'pending';
                
                if ($user_id) {
                    $sql = "INSERT INTO orders (user_id, order_date, total_amount, status, shipping_address, payment_method) 
                            VALUES ('$user_id', NOW(), '$total_with_tax', '$status', '$shipping_address', '$payment_method')";
                } else {
                    // For guest checkout
                    $sql = "INSERT INTO orders (order_date, total_amount, status, shipping_address, payment_method) 
                            VALUES (NOW(), '$total_with_tax', '$status', '$shipping_address', '$payment_method')";
                }
                
                if ($conn->query($sql) !== TRUE) {
                    throw new Exception("Error creating order: " . $conn->error);
                }
                
                $order_id = $conn->insert_id;
                
                // Insert order items and update stock
                foreach ($_SESSION['cart'] as $item) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $unit_price = $item['price'];
                    
                    // Insert order item
                    $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                            VALUES ('$order_id', '$product_id', '$quantity', '$unit_price')";
                    
                    if ($conn->query($sql) !== TRUE) {
                        throw new Exception("Error adding order items: " . $conn->error);
                    }
                    
                    // Update product stock
                    $sql = "UPDATE products SET stock_quantity = stock_quantity - $quantity 
                            WHERE product_id = $product_id AND stock_quantity >= $quantity";
                    
                    if ($conn->query($sql) !== TRUE) {
                        throw new Exception("Error updating product stock: " . $conn->error);
                    }
                    
                    // Check if stock was actually updated
                    if ($conn->affected_rows == 0) {
                        // Get current stock for error message
                        $result = $conn->query("SELECT product_name, stock_quantity FROM products WHERE product_id = $product_id");
                        $product = $result->fetch_assoc();
                        throw new Exception("Not enough stock for {$product['product_name']}. Available: {$product['stock_quantity']}");
                    }
                }
                
                // If everything is successful, commit transaction
                $conn->commit();
                
                // Store order ID in session for order confirmation
                $_SESSION['order_id'] = $order_id;
                
                // Clear the cart
                $_SESSION['cart'] = [];
                
                // Redirect to order confirmation
                header("Location: order_confirmation.php?order_id=$order_id");
                exit;
                
            } catch (Exception $e) {
                // An error occurred, rollback the transaction
                $conn->rollback();
                $errors[] = $e->getMessage();
            }
        }
    }
}

// If not a POST request or there were errors, redirect back to checkout
if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: checkout.php');
    exit;
}

// If someone tries to access this page directly without submitting the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Order - GrocerySwift</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .processing {
            text-align: center;
            padding: 50px 0;
        }
        
        .spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 5px solid rgba(76, 175, 80, 0.3);
            border-radius: 50%;
            border-top-color: #4CAF50;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <a href="index.php">
                        <h1>GrocerySwift</h1>
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="index.php" method="GET">
                        <input type="text" name="search" placeholder="Search products...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="user-actions">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <a href="account.php" class="account-icon">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="processing">
            <div class="spinner"></div>
            <h2>Processing Your Order</h2>
            <p>Please wait while we process your order. You will be redirected to the confirmation page shortly.</p>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <h2>GrocerySwift</h2>
                    <p>Fresh groceries delivered to your door</p>
                </div>
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Grocery Lane, Foodville</p>
                    <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@groceryswift.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 GrocerySwift. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // This script will automatically redirect to order confirmation page if needed
        // Normally this would be handled by the PHP redirect, but in case there's an issue:
        setTimeout(function() {
            // Check if we're still on this page after 5 seconds
            window.location.href = "order_confirmation.php";
        }, 5000);
    </script>
</body>
</html>