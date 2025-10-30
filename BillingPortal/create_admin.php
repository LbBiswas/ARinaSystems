<?php
// Create default admin user
header('Content-Type: text/html');

echo "<h2>Create Admin User</h2>";

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if admin user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'admin' OR email = 'admin@example.com'");
    $stmt->execute();
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "<p style='color: orange;'>⚠️ Admin user already exists!</p>";
        
        // Show current admin user details
        $stmt = $db->prepare("SELECT id, username, email, full_name, user_type, status, created_at FROM users WHERE username = 'admin' OR email = 'admin@example.com'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Admin User:</h3>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
        echo "<li><strong>Username:</strong> " . $admin['username'] . "</li>";
        echo "<li><strong>Email:</strong> " . $admin['email'] . "</li>";
        echo "<li><strong>Full Name:</strong> " . $admin['full_name'] . "</li>";
        echo "<li><strong>User Type:</strong> " . $admin['user_type'] . "</li>";
        echo "<li><strong>Status:</strong> " . $admin['status'] . "</li>";
        echo "<li><strong>Created:</strong> " . $admin['created_at'] . "</li>";
        echo "</ul>";
        
        // Option to reset password
        if (isset($_POST['reset_password'])) {
            $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$newPassword, $admin['id']]);
            
            echo "<p style='color: green;'>✅ Admin password has been reset to 'admin123'</p>";
        } else {
            echo '<form method="post">';
            echo '<button type="submit" name="reset_password" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Reset Admin Password to "admin123"</button>';
            echo '</form>';
        }
        
    } else {
        // Create new admin user
        if (isset($_POST['create_admin'])) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, full_name, phone, user_type, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $result = $stmt->execute([
                'admin',
                'admin@example.com',
                $hashedPassword,
                'System Administrator',
                '000-000-0000',
                'admin',
                'active'
            ]);
            
            if ($result) {
                $adminId = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
                echo "<h3>Login Credentials:</h3>";
                echo "<ul>";
                echo "<li><strong>Username:</strong> admin</li>";
                echo "<li><strong>Password:</strong> admin123</li>";
                echo "<li><strong>Email:</strong> admin@example.com</li>";
                echo "<li><strong>User ID:</strong> " . $adminId . "</li>";
                echo "</ul>";
                
                echo '<p><a href="login.html" style="background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">Go to Login Page</a></p>';
            } else {
                echo "<p style='color: red;'>❌ Failed to create admin user</p>";
            }
        } else {
            echo "<p>No admin user found. Click below to create one:</p>";
            echo '<form method="post">';
            echo '<button type="submit" name="create_admin" style="background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Create Admin User</button>';
            echo '</form>';
            echo "<p><small>This will create an admin user with username 'admin' and password 'admin123'</small></p>";
        }
    }
    
    // Also create a test customer if none exists
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'");
    $stmt->execute();
    $customerCount = $stmt->fetch()['count'];
    
    if ($customerCount === 0) {
        echo "<hr>";
        echo "<h3>Create Test Customer</h3>";
        
        if (isset($_POST['create_customer'])) {
            $hashedPassword = password_hash('customer123', PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, full_name, phone, user_type, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $result = $stmt->execute([
                'customer',
                'customer@example.com',
                $hashedPassword,
                'Test Customer',
                '123-456-7890',
                'customer',
                'active'
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Test customer created successfully!</p>";
                echo "<p><strong>Username:</strong> customer | <strong>Password:</strong> customer123</p>";
            }
        } else {
            echo "<p>No customers found. Create a test customer:</p>";
            echo '<form method="post">';
            echo '<button type="submit" name="create_customer" style="background: #6c757d; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Create Test Customer</button>';
            echo '</form>';
        }
    } else {
        echo "<hr>";
        echo "<p style='color: green;'>✅ Found " . $customerCount . " customer(s) in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
button { margin: 5px; }
hr { margin: 20px 0; }
</style>