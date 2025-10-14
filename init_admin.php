<?php
/**
 * Admin User Initialization Script for RosarioSIS
 * 
 * This script creates or updates an admin user with proper credentials
 * Run this from the web browser: http://localhost:8080/init_admin.php
 */

// Include RosarioSIS configuration and functions
require_once 'config.inc.php';
require_once 'functions/Password.php';

// Mock the do_action function if it doesn't exist
if (!function_exists('do_action')) {
    function do_action($hook, $args = []) {
        // Mock function - does nothing
        return true;
    }
}

// Admin user configuration
$admin_config = [
    'username' => 'admin',
    'password' => 'admin123',  // Change this to a secure password
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'email' => 'admin@chomaschool.com',
    'profile' => 'admin'  // Note: Must be 'admin', not 'Administrator' for RosarioSIS
];

/**
 * Initialize database connection
 */
function get_db_connection() {
    global $DatabaseType, $DatabaseServer, $DatabaseUsername, $DatabasePassword, $DatabaseName, $DatabasePort;
    
    try {
        if ($DatabaseType === 'postgresql') {
            $dsn = "pgsql:host=$DatabaseServer;port=$DatabasePort;dbname=$DatabaseName";
        } else {
            $dsn = "mysql:host=$DatabaseServer;port=$DatabasePort;dbname=$DatabaseName";
        }
        
        $pdo = new PDO($dsn, $DatabaseUsername, $DatabasePassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Create or update admin user
 */
function create_admin_user($config) {
    $pdo = get_db_connection();
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT staff_id, username FROM staff WHERE username = :username");
    $stmt->execute(['username' => $config['username']]);
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Encrypt the password using RosarioSIS function
    $encrypted_password = encrypt_password($config['password']);
    
    if ($existing_user) {
        // Update existing admin user
        echo "<h3>Updating existing admin user...</h3>";
        
        $sql = "UPDATE staff SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    password = :password,
                    profile = :profile,
                    syear = :syear,
                    schools = :schools
                WHERE username = :username";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            'first_name' => $config['first_name'],
            'last_name' => $config['last_name'],
            'email' => $config['email'],
            'password' => $encrypted_password,
            'profile' => $config['profile'],
            'syear' => '2025',
            'schools' => ',1,',
            'username' => $config['username']
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✓ Admin user updated successfully!</p>";
            return $existing_user['staff_id'];
        } else {
            echo "<p style='color: red;'>✗ Failed to update admin user</p>";
            return false;
        }
    } else {
        // Create new admin user
        echo "<h3>Creating new admin user...</h3>";
        
        $sql = "INSERT INTO staff 
                (username, password, first_name, last_name, email, profile, syear, schools) 
                VALUES 
                (:username, :password, :first_name, :last_name, :email, :profile, :syear, :schools)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            'username' => $config['username'],
            'password' => $encrypted_password,
            'first_name' => $config['first_name'],
            'last_name' => $config['last_name'],
            'email' => $config['email'],
            'profile' => $config['profile'],
            'syear' => '2025',
            'schools' => ',1,'
        ]);
        
        if ($result) {
            $staff_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✓ Admin user created successfully with ID: $staff_id</p>";
            return $staff_id;
        } else {
            echo "<p style='color: red;'>✗ Failed to create admin user</p>";
            return false;
        }
    }
}

/**
 * Test login functionality
 */
function test_login($username, $password) {
    $pdo = get_db_connection();
    
    $stmt = $pdo->prepare("SELECT staff_id, username, password, first_name, last_name, profile FROM staff WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && match_password($user['password'], $password)) {
        echo "<p style='color: green;'>✓ Login test successful for user: {$user['username']}</p>";
        echo "<p>User details: {$user['first_name']} {$user['last_name']} ({$user['profile']})</p>";
        return true;
    } else {
        echo "<p style='color: red;'>✗ Login test failed</p>";
        return false;
    }
}

/**
 * Display current users
 */
function display_users() {
    $pdo = get_db_connection();
    
    $stmt = $pdo->query("SELECT staff_id, username, first_name, last_name, profile, email FROM staff ORDER BY staff_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Users in Database:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Profile</th><th>Email</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['staff_id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['first_name']} {$user['last_name']}</td>";
        echo "<td>{$user['profile']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Main execution
?>
<!DOCTYPE html>
<html>
<head>
    <title>RosarioSIS Admin User Initialization</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #f0f8ff; padding: 10px; border: 1px solid #ccc; margin: 10px 0; }
        table { width: 100%; margin: 10px 0; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>RosarioSIS Admin User Initialization</h1>
        
        <div class="info">
            <strong>Admin Credentials:</strong><br>
            Username: <code><?php echo $admin_config['username']; ?></code><br>
            Password: <code><?php echo $admin_config['password']; ?></code><br>
            <em>Please change the password after first login!</em>
        </div>

        <?php
        // Execute the initialization
        echo "<h2>Initializing Admin User...</h2>";
        
        $staff_id = create_admin_user($admin_config);
        
        if ($staff_id) {
            echo "<h2>Testing Login...</h2>";
            test_login($admin_config['username'], $admin_config['password']);
            
            echo "<h2>Current Users:</h2>";
            display_users();
            
            echo "<div class='info'>";
            echo "<h3>Next Steps:</h3>";
            echo "<ol>";
            echo "<li><a href='index.php' target='_blank'>Login to RosarioSIS</a> with the credentials above</li>";
            echo "<li>Change the admin password immediately after login</li>";
            echo "<li>Delete this initialization script for security</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>
        
        <hr>
        <p><em>For security, delete this file after successful initialization.</em></p>
        
        <!-- Form to manually create admin user -->
        <h2>Manual Admin Creation</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_manual">
            <table>
                <tr><td>Username:</td><td><input type="text" name="username" value="admin" required></td></tr>
                <tr><td>Password:</td><td><input type="password" name="password" required placeholder="Enter new password"></td></tr>
                <tr><td>First Name:</td><td><input type="text" name="first_name" value="System" required></td></tr>
                <tr><td>Last Name:</td><td><input type="text" name="last_name" value="Administrator" required></td></tr>
                <tr><td>Email:</td><td><input type="email" name="email" value="admin@chomaschool.com"></td></tr>
                <tr><td colspan="2"><input type="submit" value="Create/Update Admin User" style="padding: 10px 20px;"></td></tr>
            </table>
        </form>
        
        <?php
        // Handle manual form submission
        if ($_POST['action'] === 'create_manual') {
            $manual_config = [
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'email' => $_POST['email'],
                'profile' => 'admin'
            ];
            
            echo "<hr><h2>Manual Admin Creation Results:</h2>";
            $manual_staff_id = create_admin_user($manual_config);
            
            if ($manual_staff_id) {
                echo "<h3>Testing Manual Login:</h3>";
                test_login($manual_config['username'], $manual_config['password']);
            }
        }
        ?>
    </div>
</body>
</html>