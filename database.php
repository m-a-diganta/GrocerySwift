<?php
// Database connection details
$host = "localhost";
$username = "root";    // Replace with your actual database username
$password = "";        // Replace with your actual database password
$database = "groceryswift";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
else {
    // echo "Connected successfully"; 
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Function to clean input data
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to fetch all categories
function getCategories() {
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY category_name";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Function to fetch subcategories by category
function getSubcategoriesByCategory($category_id) {
    global $conn;
    $category_id = (int)$category_id; // Ensure it's an integer
    
    $sql = "SELECT * FROM subcategories WHERE category_id = $category_id ORDER BY subcategory_name";
    $result = $conn->query($sql);
    $subcategories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
    }
    
    return $subcategories;
}

// Function to fetch all subcategories
function getAllSubcategories() {
    global $conn;
    $sql = "SELECT s.*, c.category_name FROM subcategories s 
            JOIN categories c ON s.category_id = c.category_id 
            ORDER BY c.category_name, s.subcategory_name";
    $result = $conn->query($sql);
    $subcategories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
    }
    
    return $subcategories;
}

// Function to fetch products by subcategory
function getProductsBySubcategory($subcategory_id) {
    global $conn;
    $subcategory_id = (int)$subcategory_id; // Ensure it's an integer
    
    $sql = "SELECT * FROM products WHERE subcategory_id = $subcategory_id ORDER BY product_name";
    $result = $conn->query($sql);
    $products = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to fetch products by category (fetches products from all subcategories in the category)
function getProductsByCategory($category_id) {
    global $conn;
    $category_id = (int)$category_id; // Ensure it's an integer
    
    $sql = "SELECT p.* FROM products p 
            JOIN subcategories s ON p.subcategory_id = s.subcategory_id 
            WHERE s.category_id = $category_id 
            ORDER BY p.product_name";
    $result = $conn->query($sql);
    $products = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to fetch all products with category and subcategory information
function getAllProducts() {
    global $conn;
    $sql = "SELECT p.*, s.subcategory_name, c.category_name 
            FROM products p 
            JOIN subcategories s ON p.subcategory_id = s.subcategory_id 
            JOIN categories c ON s.category_id = c.category_id 
            ORDER BY c.category_name, s.subcategory_name, p.product_name";
    $result = $conn->query($sql);
    $products = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to fetch product by ID with category and subcategory information
function getProductById($product_id) {
    global $conn;
    $product_id = (int)$product_id; // Ensure it's an integer
    
    $sql = "SELECT p.*, s.subcategory_name, s.subcategory_id, c.category_name, c.category_id 
            FROM products p 
            JOIN subcategories s ON p.subcategory_id = s.subcategory_id 
            JOIN categories c ON s.category_id = c.category_id 
            WHERE p.product_id = $product_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to search products
function searchProducts($search_term) {
    global $conn;
    $search_term = clean_input($search_term);
    
    $sql = "SELECT p.*, s.subcategory_name, c.category_name 
            FROM products p 
            JOIN subcategories s ON p.subcategory_id = s.subcategory_id 
            JOIN categories c ON s.category_id = c.category_id 
            WHERE p.product_name LIKE '%$search_term%' 
            OR p.description LIKE '%$search_term%' 
            OR s.subcategory_name LIKE '%$search_term%'
            OR c.category_name LIKE '%$search_term%'
            ORDER BY p.product_name";
    $result = $conn->query($sql);
    $products = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get category and subcategory path for breadcrumbs
function getProductPath($product_id) {
    global $conn;
    $product_id = (int)$product_id; // Ensure it's an integer
    
    $sql = "SELECT p.product_name, s.subcategory_id, s.subcategory_name, c.category_id, c.category_name 
            FROM products p 
            JOIN subcategories s ON p.subcategory_id = s.subcategory_id 
            JOIN categories c ON s.category_id = c.category_id 
            WHERE p.product_id = $product_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}
?>