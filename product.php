<?php
// Start session for cart functionality
session_start();

// Include database connection
require_once 'database.php';

// Initialize or get the cart
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


// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid product ID, redirect to homepage
if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get product info
$product = getProductById($product_id);

// If product not found, redirect to homepage
if (!$product) {
    header('Location: index.php');
    exit;
}

// Get products from same category for related products section
$related_products = getProductsByCategory($product['category_id']);

// Remove current product from related products
foreach ($related_products as $key => $related_product) {
    if ($related_product['product_id'] == $product_id) {
        unset($related_products[$key]);
        break;
    }
}

// Process add to cart if form submitted
if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    $cart_product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Ensure valid quantity
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Check if product exists
    $cart_product = getProductById($cart_product_id);
    if ($cart_product) {
        // Check if product is already in cart
        if (isset($_SESSION['cart'][$cart_product_id])) {
            $_SESSION['cart'][$cart_product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cart_product_id] = [
                'product_id' => $cart_product_id,
                'name' => $cart_product['product_name'],
                'price' => $cart_product['price'],
                'quantity' => $quantity,
                'image' => $cart_product['image_url']
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
    <title><?php echo htmlspecialchars($product['product_name']); ?> - GrocerySwift</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    .product-detail {
        margin: 30px 0;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 20px;
    }

    .product-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        align-items: start;
    }

    .product-detail-image {
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f9f9f9;
        position: relative;
    }

    .product-detail-image img {
        width: 100%;
        height: auto;
        max-height: 400px;
        object-fit: contain;
        display: block;
    }

    .product-detail-info {
        display: flex;
        flex-direction: column;
        gap: 0px;
    }

    .product-detail-info h1 {
        font-size: 28px;
        margin: 0 0 10px;
        color: #333;
        line-height: 1.2;
    }

    .product-category {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: #666;
    }

    .product-category .label {
        font-weight: 600;
        margin-right: 5px;
    }

    .product-category a {
        color:#48A6A7;
        text-decoration: none;
        transition: color 0.2s;
    }

    .product-category a:hover {
        color: #48A6A7;
        text-decoration: underline;
    }

    .product-price {
        margin: 10px 0;
    }

    .current-price {
        font-size: 24px;
        font-weight: bold;
        color: #48A6A7;
    }

    .product-description {
        font-size: 15px;
        line-height: 1.6;
        color: #555;
        margin: 10px 0;
    }

    .product-stock {
        margin: 10px 0;
        font-size: 14px;
    }

    .in-stock {
        color: #4a8f29;
        font-weight: 600;
    }

    .in-stock i {
        margin-right: 5px;
    }

    .out-of-stock {
        color: #e74c3c;
        font-weight: 600;
    }

    .out-of-stock i {
        margin-right: 5px;
    }

    .add-to-cart-form {
        margin: 20px 0;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .product-quantity {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .product-quantity label {
        font-weight: 600;
        color: #333;
    }

    .quantity {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
    }

    .quantity button {
        background-color: #f5f5f5;
        border: none;
        color: #333;
        padding: 8px 12px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.2s;
    }

    .quantity button:hover {
        background-color: #e0e0e0;
    }

    .quantity input {
        width: 50px;
        text-align: center;
        border: none;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
        padding: 8px 0;
    }

    .add-to-cart-btn {
        background-color:#48A6A7;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 12px 20px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color 0.2s;
    }

    .add-to-cart-btn:hover {
        background-color: #48A6A7;
    }

    .add-to-cart-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }

    .add-to-cart-btn i {
        font-size: 18px;
    }

    .product-meta {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        color: #666;
        font-size: 14px;
    }

    .product-meta div {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .product-meta i {
        color: #48A6A7;
    }

    /* Breadcrumb navigation */
    .breadcrumb {
        margin: 20px 0;
        font-size: 14px;
        color: #666;
    }

    .breadcrumb a {
        color:#48A6A7;
        text-decoration: none;
        transition: color 0.2s;
    }

    .breadcrumb a:hover {
        color:rgb(24, 77, 78);
        text-decoration: underline;
    }

    /* Related Products Section */
    .related-products {
        margin: 40px 0;
    }

    .related-products h2 {
        font-size: 22px;
        margin-bottom: 20px;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .product-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .product-image {
        height: 180px;
        overflow: hidden;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .product-card:hover .product-image img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 15px;
    }

    .product-info h3 {
        font-size: 16px;
        margin: 0 0 10px;
        line-height: 1.3;
    }

    .product-info h3 a {
        color: #333;
        text-decoration: none;
        transition: color 0.2s;
    }

    .product-info h3 a:hover {
        color: #48A6A7;
    }

    .product-info .price {
        color: #48A6A7;
        font-weight: bold;
        font-size: 18px;
        margin: 10px 0;
    }

    .product-info .add-to-cart {
        width: 100%;
        background-color: #48A6A7;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color 0.2s;
    }

    .product-info .add-to-cart:hover {
        background-color:rgb(20, 71, 72);
    }

    .product-info .add-to-cart i {
        font-size: 14px;
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .products-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .product-detail-grid {
            grid-template-columns: 1fr;
        }
        
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .products-grid {
            grid-template-columns: 1fr;
        }
        
        .product-meta {
            flex-direction: column;
            gap: 10px;
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
        
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> &gt; 
            <span><?php echo htmlspecialchars($product['product_name']); ?></span>
        </div>
        
        <section class="product-detail">
            <div class="product-detail-grid">
                <div class="product-detail-image">
                    <img src="images/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                </div>
                
                <div class="product-detail-info">
                    <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    
                    <div class="product-category">
                        <span class="label">Category:</span>
                        <a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
                    </div>
                    
                    <div class="product-price">
                        <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-description">
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-stock">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                        <?php else: ?>
                            <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <form action="product.php?id=<?php echo $product_id; ?>" method="POST" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        
                        <div class="product-quantity">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity">
                                <button type="button" class="decrement">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="increment">+</button>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_to_cart" class="add-to-cart-btn" <?php if ($product['stock_quantity'] <= 0) echo 'disabled'; ?>>
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </form>
                    
                    <div class="product-meta">
                        <div class="delivery-info">
                            <i class="fas fa-truck"></i> Fast Delivery
                        </div>
                        <div class="return-info">
                            <i class="fas fa-undo"></i> Easy Returns
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <?php if (count($related_products) > 0): ?>
            <section class="related-products">
                <h2>You May Also Like</h2>
                <div class="products-grid">
                    <?php 
                    $count = 0;
                    foreach ($related_products as $related_product): 
                        if ($count >= 4) break; // Show maximum 4 related products
                        $count++;
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product.php?id=<?php echo $related_product['product_id']; ?>">
                                    <img src="images/<?php echo htmlspecialchars($related_product['image_url']); ?>" alt="<?php echo htmlspecialchars($related_product['product_name']); ?>">
                                </a>
                            </div>
                            <div class="product-info">
                                <h3>
                                    <a href="product.php?id=<?php echo $related_product['product_id']; ?>">
                                        <?php echo htmlspecialchars($related_product['product_name']); ?>
                                    </a>
                                </h3>
                                <p class="price">$<?php echo number_format($related_product['price'], 2); ?></p>
                                <form action="product.php?id=<?php echo $product_id; ?>" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $related_product['product_id']; ?>">
                                    <button type="submit" name="add_to_cart" class="add-to-cart">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
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
                    const max = input.hasAttribute('max') ? parseInt(input.getAttribute('max')) : 99;
                    if (value < max) {
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