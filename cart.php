<?php
session_start();

require_once 'database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Search functionality
$search_results = [];
$search_term = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = clean_input($_GET['search']);
    $search_results = searchProducts($search_term);
}

// Get all categories
$categories = getCategories();

// Process cart actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    // Remove item from cart
    if ($action === 'remove' && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $cart_updated = true;
    }
    
    // Update quantity
    if ($action === 'update' && isset($_SESSION['cart'][$product_id]) && isset($_POST['quantity'])) {
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0 && $quantity <= 99) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            $cart_updated = true;
        } elseif ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            $cart_updated = true;
        }
    }
    
    // Clear cart
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        $cart_updated = true;
    }
}

// Calculate cart totals
$subtotal = 0;
$total_items = 0;

foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

// Set tax rate and calculate tax and total
$tax_rate = 0.08; // 8% tax
$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - GrocerySwift</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .cart-section {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .cart-section h1 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            color: #333;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            background-color: #f9f9f9;
            border-radius: 10px;
        }

        .empty-cart i {
            color: #999;
            margin-bottom: 1rem;
        }

        .empty-cart h2 {
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            color: #444;
        }

        .empty-cart p {
            margin-bottom: 1.5rem;
            color: #666;
        }

        .empty-cart .btn {
            background-color: #48A6A7;
            color: #fff;
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }

        /* === Cart Container === */
        .cart-container {
            overflow-x: auto;
        }

        /* === Cart Table === */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .cart-table th,
        .cart-table td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .cart-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-details h3 {
            margin: 0;
            font-size: 1rem;
            color: #333;
        }

        .price,
        .subtotal {
            color: #444;
            font-weight: 500;
        }

        .quantity {
            display: flex;
            align-items: start;
            justify-content: start;
            flex-direction: column;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-controls button {
            background-color: #ddd;
            border: none;
            padding: 0.3rem 0.6rem;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
        }

        .quantity-controls input[type="number"] {
            width: 50px;
            padding: 0.4rem;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .update-btn {
            margin-top: 0.5rem;
            padding: 0.4rem 1rem;
            background-color: #48A6A7;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .remove-btn {
            background-color: transparent;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
        }

        /* === Cart Actions === */
        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .clear-btn,
        .continue-btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .clear-btn {
            background-color: #dc3545;
            color: white;
        }

        .continue-btn {
            background-color: #48A6A7;
            color: white;
        }

        /* === Cart Summary === */
        .cart-summary {
            max-width: 400px;
            margin-left: auto;
            background-color: #f8f8f8;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .cart-summary h2 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: #333;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 1rem;
            color: #555;
        }

        .summary-item.total {
            font-weight: bold;
            color: #000;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            margin-top: 1.5rem;
            text-align: center;
            background-color: #48A6A7;
            color: white;
            padding: 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <a href="index.php">
                        <img src="logo.png" alt="GrocerySwift Logo" width="140" height="auto" style="padding-top: 5px;">
                    </a>
                </div>
                
                <div style="display: flex; align-items: center; gap: 5px; width: 60%;">
                    <div class="search-bar">
                        <form action="index.php" method="GET">
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>

                    <div class="category-dropdown">
                    <button class="category-dropdown-btn">
                        Categories <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <?php foreach ($categories as $category): ?>
                            <?php 
                            // Get subcategories for this category
                            $subcategories = getSubcategoriesByCategory($category['category_id']); 
                            ?>
                            <div class="category-item">
                                <a href="category.php?id=<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                    <?php if (!empty($subcategories)): ?>
                                        <i class="fas fa-chevron-right"></i>
                                    <?php endif; ?>
                                </a>
                                <?php if (!empty($subcategories)): ?>
                                    <div class="subcategory-content">
                                        <?php foreach ($subcategories as $subcategory): ?>
                                            <a href="subcategory.php?id=<?php echo $subcategory['subcategory_id']; ?>">
                                                <?php echo htmlspecialchars($subcategory['subcategory_name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                </div>
                
                <div class="user-actions">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($total_items > 0): ?>
                            <span class="cart-count"><?php echo $total_items; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="account.php" class="account-icon">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="breadcrumb" style="margin-top: 20px; margin-bottom: 10px; font-size: 14px; font-weight: bold;">
            <a href="index.php">Home</a> &gt; Shopping Cart
        </div>
        
        <?php if (isset($cart_updated) && $cart_updated): ?>
            <div class="alert success">
                Your cart has been updated!
            </div>
        <?php endif; ?>
        
        <section class="cart-section">
            <h1>Shopping Cart</h1>
            
            <?php if (empty($_SESSION['cart'])): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart fa-4x"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any products to your cart yet.</p>
                    <a href="index.php" class="btn">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                <tr>
                                    <td class="product-info" >
                                        <div class="product-image">
                                            <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="product-details">
                                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        </div>
                                    </td>
                                    <td class="price">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="quantity">
                                        <form action="cart.php" method="POST" class="update-form">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <div class="quantity-controls">
                                                <button type="button" class="decrement">-</button>
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                                                <button type="button" class="increment">+</button>
                                            </div>
                                            <button type="submit" style="width: 140px; background-color:#48A6A7; margin-bottom: 30px; margin-top:20px;">Update</button>
                                        </form>
                                    </td>
                                    <td class="subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    <td class="actions">
                                        <form action="cart.php" method="POST">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <button type="submit" class="remove-btn"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-actions">
                        <form action="cart.php" method="POST">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="clear-btn">Clear Cart</button>
                        </form>
                        <a href="index.php" class="continue-btn">Continue Shopping</a>
                    </div>
                    
                    <div class="cart-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Tax (8%)</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="summary-item total">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
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
        // Quantity increment/decrement functionality
        document.addEventListener('DOMContentLoaded', function() {
            const decrementButtons = document.querySelectorAll('.decrement');
            const incrementButtons = document.querySelectorAll('.increment');
            
            decrementButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.nextElementSibling;
                    let value = parseInt(input.value);
                    if (value > 1) {
                        input.value = value - 1;
                    }
                });
            });
            
            incrementButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    let value = parseInt(input.value);
                    if (value < 99) {
                        input.value = value + 1;
                    }
                });
            });


            // Category dropdown toggle
            const dropdownBtn = document.querySelector('.category-dropdown-btn');
            const dropdownContent = document.querySelector('.dropdown-content');
            
            dropdownBtn.addEventListener('click', function() {
                dropdownContent.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            window.addEventListener('click', function(event) {
                if (!event.target.matches('.category-dropdown-btn') && 
                    !event.target.matches('.category-dropdown-btn i')) {
                    if (dropdownContent.classList.contains('show')) {
                        dropdownContent.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body>
</html>