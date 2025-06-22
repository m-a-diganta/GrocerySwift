<?php
session_start();
require_once 'database.php';

// Redirect to cart if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Search functionality
$search_results = [];
$search_term = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = clean_input($_GET['search']);
    $search_results = searchProducts($search_term);
}


$user_data = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'zip_code' => '',
    'phone' => ''
];

$errors = [];


if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE user_id = $user_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_data['first_name'] = $user['first_name'];
        $user_data['last_name'] = $user['last_name'];
        $user_data['email'] = $user['email'];
        $user_data['phone'] = $user['phone'];
        
        if (!empty($user['address'])) {
            $address_parts = json_decode($user['address'], true);
            if ($address_parts) {
                $user_data['address'] = $address_parts['address'] ?? '';
                $user_data['city'] = $address_parts['city'] ?? '';
                $user_data['state'] = $address_parts['state'] ?? '';
                $user_data['zip_code'] = $address_parts['zip_code'] ?? '';
            }
        }
    }
}

// Process checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $required_fields = ['first_name', 'last_name', 'email', 'address', 'city', 'state', 'zip_code', 'phone', 'payment_method'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        } else {
            $user_data[$field] = clean_input($_POST[$field]);
        }
    }
    
    // Validate email format
    if (!empty($user_data['email']) && !filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    if (!empty($user_data['phone']) && !preg_match('/^[0-9()\-\s]+$/', $user_data['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    if (empty($errors)) {
        $address_json = json_encode([
            'address' => $user_data['address'],
            'city' => $user_data['city'],
            'state' => $user_data['state'],
            'zip_code' => $user_data['zip_code']
        ]);

        $shipping_address = $user_data['address'] . ', ' . 
                           $user_data['city'] . ', ' . 
                           $user_data['state'] . ' ' . 
                           $user_data['zip_code'];
        
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax_rate = 0.08;
        $tax = $subtotal * $tax_rate;
        $total = $subtotal + $tax;
    
        
            header('Location: process_order.php');
            exit;
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
    <title>Checkout - GrocerySwift</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

.checkout-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.checkout-section h1 {
    color: #2c3e50;
    margin-bottom: 2rem;
    font-size: 2rem;
    font-weight: 600;
    border-bottom: 1px solid #e1e1e1;
    padding-bottom: 1rem;
}

.checkout-container {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
}

.checkout-form {
    flex: 1 1 60%;
    min-width: 300px;
}

.order-summary {
    flex: 1 1 30%;
    min-width: 250px;
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    position: sticky;
    top: 2rem;
    height: fit-content;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.form-section {
    margin-bottom: 2rem;
    background-color: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.form-section h2 {
    color: #3498db;
    font-size: 1.4rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.25rem;
    width: 100%;
}

.form-group.half {
    flex: 1 1 calc(50% - 0.5rem);
    min-width: 150px;
}

.form-group.quarter {
    flex: 1 1 calc(25% - 0.75rem);
    min-width: 100px;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #555;
    font-size: 0.9rem;
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}


.error {
    color: #e74c3c;
    font-size: 0.85rem;
    margin-top: 0.25rem;
    display: block;
}

.form-group input.error {
    border-color: #e74c3c;
}

/* Payment Methods */
.payment-methods {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.payment-method {
    flex: 1 1 calc(33.333% - 0.67rem);
    min-width: 120px;
    position: relative;
}

.payment-method input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.payment-method label {
    display: block;
    padding: 1rem;
    background-color: #f8f9fa;
    border: 2px solid #e1e1e1;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.payment-method input[type="radio"]:checked + label {
    border-color: #3498db;
    background-color: rgba(52, 152, 219, 0.05);
}

.payment-method i {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: #555;
}

.payment-method input[type="radio"]:checked + label i {
    color: #3498db;
}

/* Order Summary */
.order-summary h2 {
    color: #2c3e50;
    font-size: 1.4rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
    border-bottom: 1px solid #e1e1e1;
    padding-bottom: 0.75rem;
}

.summary-items {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}

.item-info {
    display: flex;
    align-items: center;
}

.item-quantity {
    font-weight: 600;
    margin-right: 0.5rem;
    color: #555;
}

.item-name {
    color: #333;
}

.item-price {
    font-weight: 600;
    color: #333;
}

.summary-totals {
    border-top: 1px solid #ddd;
    padding-top: 1rem;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.summary-line.total {
    font-weight: 700;
    font-size: 1.2rem;
    color: #2c3e50;
    border-top: 1px solid #ddd;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
}

/* Action Buttons */
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
}

.back-btn {
    padding: 0.75rem 1.5rem;
    background-color: #f8f9fa;
    color: #555;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.back-btn:hover {
    background-color: #e9ecef;
    color: #333;
}

.place-order-btn {
    padding: 0.75rem 2rem;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.place-order-btn:hover {
    background-color: #2980b9;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .checkout-container {
        flex-direction: column;
    }
    
    .order-summary {
        position: static;
        order: -1;
        margin-bottom: 2rem;
    }
    
    .payment-method {
        flex: 1 1 100%;
    }
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
            <a href="index.php">Home</a> &gt; <a href="cart.php">Shopping Cart</a> &gt; Checkout
        </div>
        
        <?php if (!empty($errors['system'])): ?>
            <div class="alert error">
                <?php echo $errors['system']; ?>
            </div>
        <?php endif; ?>
        
        <section class="checkout-section">
            <h1>Checkout</h1>
            
            <div class="checkout-container">
                <div class="checkout-form">
                    <form action="checkout.php" method="POST">
                        <div class="form-section">
                            <h2>Contact Information</h2>
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                                    <?php if (isset($errors['first_name'])): ?>
                                        <span class="error"><?php echo $errors['first_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group half">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                                    <?php if (isset($errors['last_name'])): ?>
                                        <span class="error"><?php echo $errors['last_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" required pattern="[^@\s]+@[^@\s]+\.[^@\s]+" value="<?php echo htmlspecialchars($user_data['email']); ?>">
                                    <?php if (isset($errors['email'])): ?>
                                        <span class="error"><?php echo $errors['email']; ?></span>

                                    <?php endif; ?>
                                </div>
                                <div class="form-group half">
                                    <label for="phone">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                                    <?php if (isset($errors['phone'])): ?>
                                        <span class="error"><?php echo $errors['phone']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h2>Shipping Address</h2>
                            <div class="form-group">
                                <label for="address">Street Address *</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address']); ?>" required>
                                <?php if (isset($errors['address'])): ?>
                                    <span class="error"><?php echo $errors['address']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user_data['city']); ?>" required>
                                    <?php if (isset($errors['city'])): ?>
                                        <span class="error"><?php echo $errors['city']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group quarter">
                                <label for="state">State/Territory *</label>
                                    <select id="state" name="state" required class="form-group half">
                                        <option value="">Select a state/territory</option>
                                        <option value="NSW" <?php if (($user_data['state'] ?? '') === 'NSW') echo 'selected'; ?>>New South Wales (NSW)</option>
                                        <option value="VIC" <?php if (($user_data['state'] ?? '') === 'VIC') echo 'selected'; ?>>Victoria (VIC)</option>
                                        <option value="QLD" <?php if (($user_data['state'] ?? '') === 'QLD') echo 'selected'; ?>>Queensland (QLD)</option>
                                        <option value="WA" <?php if (($user_data['state'] ?? '') === 'WA') echo 'selected'; ?>>Western Australia (WA)</option>
                                        <option value="SA" <?php if (($user_data['state'] ?? '') === 'SA') echo 'selected'; ?>>South Australia (SA)</option>
                                        <option value="TAS" <?php if (($user_data['state'] ?? '') === 'TAS') echo 'selected'; ?>>Tasmania (TAS)</option>
                                        <option value="ACT" <?php if (($user_data['state'] ?? '') === 'ACT') echo 'selected'; ?>>Australian Capital Territory (ACT)</option>
                                        <option value="NT" <?php if (($user_data['state'] ?? '') === 'NT') echo 'selected'; ?>>Northern Territory (NT)</option>
                                        <option value="Others" <?php if (($user_data['state'] ?? '') === 'Others') echo 'selected'; ?>>Others</option>
                                    </select>
                                    <?php if (isset($errors['state'])): ?>
                                        <span class="error"><?php echo $errors['state']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group quarter">
                                    <label for="zip_code">ZIP Code *</label>
                                    <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user_data['zip_code']); ?>" required>
                                    <?php if (isset($errors['zip_code'])): ?>
                                        <span class="error"><?php echo $errors['zip_code']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h2>Payment Method</h2>
                            <div class="payment-methods">
                                <div class="payment-method">
                                    <input type="radio" id="credit_card" name="payment_method" value="credit_card" checked>
                                    <label for="credit_card">
                                        <i class="far fa-credit-card"></i> Credit Card
                                    </label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" id="paypal" name="payment_method" value="paypal">
                                    <label for="paypal">
                                        <i class="fab fa-paypal"></i> PayPal
                                    </label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" id="cash_on_delivery" name="payment_method" value="cash_on_delivery">
                                    <label for="cash_on_delivery">
                                        <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                                    </label>
                                </div>
                            </div>
                            <?php if (isset($errors['payment_method'])): ?>
                                <span class="error"><?php echo $errors['payment_method']; ?></span>
                            <?php endif; ?>
                            
                            <!-- Credit card form fields would be added here and shown/hidden via JavaScript -->
                        </div>
                        
                        <form action="process-order.php" method="POST">
                        <div class="form-actions">
                            <a href="cart.php" class="back-btn">Back to Cart</a>
                            <input type="hidden" name="action" value="place_order">
                            <button type="submit" class="place-order-btn">Place Order</button>
                        </div>
                        </form>

                    </form>
                </div>
                
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-items">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="summary-item">
                                <div class="item-info">
                                    <span class="item-quantity"><?php echo $item['quantity']; ?> ×</span>
                                    <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                                <span class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-line">
                            <span>Tax (8%)</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="summary-line total">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
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
        // Form validation and payment method toggle would be added here
        document.addEventListener('DOMContentLoaded', function() {
            // Example of payment method toggle
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            // Additional payment form validation would be implemented here
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

            document.getElementById('email').addEventListener('input', function() {
                const errorSpan = this.nextElementSibling;
                if (!this.validity.valid) {
                    errorSpan.textContent = "Please enter a valid email address.";
                } else {
                    errorSpan.textContent = "";
                }
            });


    </script>
</body>
</html>