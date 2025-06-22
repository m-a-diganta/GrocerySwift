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
        });
    </script>
</body>
</html>