<?php
// Start session for cart functionality
session_start();

// Include database connection
require_once 'database.php';

// Initialize or get the cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid category ID, redirect to homepage
if ($category_id <= 0) {
    header('Location: index.php');
    exit;
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

// Get category info
$categories = getCategories();
$current_category = null;

foreach ($categories as $category) {
    if ($category['category_id'] == $category_id) {
        $current_category = $category;
        break;
    }
}

// If category not found, redirect to homepage
if (!$current_category) {
    header('Location: index.php');
    exit;
}

// Get products for this category
$products = getProductsByCategory($category_id);

// Process add to cart if form submitted
if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Ensure valid quantity
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Check if product exists
    $product = getProductById($product_id);
    if ($product) {
        // Check if product is already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'product_id' => $product_id,
                'name' => $product['product_name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image_url']
            ];
        }
        $added_to_cart = true;
    }
}

// Get cart count
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_category['category_name']); ?> - GrocerySwift</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
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
        <?php if (isset($added_to_cart) && $added_to_cart): ?>
            <div class="alert success">
                Product added to cart successfully!
            </div>
        <?php endif; ?>
        
        <div class="breadcrumb" style="margin-top: 20px; margin-bottom: 10px; font-size: 14px; font-weight: bold;">
            <a href="index.php">Home</a> &gt; 
            <span><?php echo htmlspecialchars($current_category['category_name']); ?></span>
        </div>
        
        <section class="category-header">
            <div class="category-banner" style="background-color:#02383c; padding: 10px; border-radius: 10px; text-align: center; color: white; margin-bottom: 20px; margin-top: 20px;">
                <h1><?php echo htmlspecialchars($current_category['category_name']); ?></h1>
            </div>
        </section>
        
        <section class="category-products">
            <?php if (empty($products)): ?>
                <p class="no-products">No products found in this category.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product.php?id=<?php echo $product['product_id']; ?>">
                                    <img src="images/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                </a>
                            </div>
                            <div class="product-info">
                                <h3>
                                    <a href="product.php?id=<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </a>
                                </h3>
                                <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                                <form action="category.php?id=<?php echo $category_id; ?>" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <div class="quantity">
                                        <button type="button" class="decrement">-</button>
                                        <input type="number" name="quantity" value="1" min="1" max="99">
                                        <button type="button" class="increment">+</button>
                                    </div>
                                    <button type="submit" name="add_to_cart" class="add-to-cart">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <section class="other-categories">
            <h2>Browse Other Categories</h2>
            <div class="category-grid" style="margin-top: 20px;">
                <?php foreach ($categories as $category): ?>
                    <?php if ($category['category_id'] != $category_id): ?>
                        <a href="category.php?id=<?php echo $category['category_id']; ?>" class="category-card">
                            <div class="category-image">
                                <img src="images/<?php echo htmlspecialchars($category['category_image']); ?>" alt="<?php echo htmlspecialchars($category['category_name']); ?>">
                            </div>
                            <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
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