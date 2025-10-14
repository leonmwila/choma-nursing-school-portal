<?php
/**
 * Login Test Script for RosarioSIS
 * Tests the admin login credentials
 */

// Include necessary files
require_once 'config.inc.php';
require_once 'functions/Password.php';

// Mock required functions
if (!function_exists('do_action')) {
    function do_action($hook, $args = []) {
        return true;
    }
}

/**
 * Test admin login
 */
function test_admin_login() {
    global $DatabaseType, $DatabaseServer, $DatabaseUsername, $DatabasePassword, $DatabaseName, $DatabasePort;
    
    try {
        // Connect to database
        if ($DatabaseType === 'postgresql') {
            $dsn = "pgsql:host=$DatabaseServer;port=$DatabasePort;dbname=$DatabaseName";
        } else {
            $dsn = "mysql:host=$DatabaseServer;port=$DatabasePort;dbname=$DatabaseName";
        }
        
        $pdo = new PDO($dsn, $DatabaseUsername, $DatabasePassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get admin user
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            return ['success' => false, 'message' => 'Admin user not found'];
        }
        
        // Test password
        $test_passwords = ['admin123', 'admin', 'password'];
        $working_password = null;
        
        foreach ($test_passwords as $password) {
            if (match_password($admin['password'], $password)) {
                $working_password = $password;
                break;
            }
        }
        
        if ($working_password) {
            return [
                'success' => true, 
                'message' => 'Login successful',
                'username' => $admin['username'],
                'password' => $working_password,
                'name' => $admin['first_name'] . ' ' . $admin['last_name'],
                'profile' => $admin['profile'],
                'email' => $admin['email']
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Password verification failed',
                'username' => $admin['username'],
                'stored_hash' => substr($admin['password'], 0, 30) . '...'
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Run the test
$result = test_admin_login();
?>
<!DOCTYPE html>
<html>
<head>
    <title>RosarioSIS Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #f0f8f0; padding: 15px; border: 1px solid #4CAF50; }
        .error { color: red; background: #fff0f0; padding: 15px; border: 1px solid #f44336; }
        .credentials { background: #e7f3ff; padding: 15px; border: 1px solid #2196F3; margin: 10px 0; }
        .login-form { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>RosarioSIS Login Test Results</h1>
    
    <?php if ($result['success']): ?>
        <div class="success">
            <h2>✓ Login Test Successful!</h2>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($result['username']); ?></p>
            <p><strong>Password:</strong> <?php echo htmlspecialchars($result['password']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($result['name']); ?></p>
            <p><strong>Profile:</strong> <?php echo htmlspecialchars($result['profile']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($result['email']); ?></p>
        </div>
        
        <div class="credentials">
            <h3>Admin Credentials Ready to Use:</h3>
            <p><strong>Username:</strong> <code><?php echo htmlspecialchars($result['username']); ?></code></p>
            <p><strong>Password:</strong> <code><?php echo htmlspecialchars($result['password']); ?></code></p>
        </div>
        
        <div class="login-form">
            <h3>Quick Login Form</h3>
            <form action="index.php" method="POST" target="_blank">
                <input type="hidden" name="USERNAME" value="<?php echo htmlspecialchars($result['username']); ?>">
                <input type="hidden" name="PASSWORD" value="<?php echo htmlspecialchars($result['password']); ?>">
                <button type="submit" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer;">
                    Login to RosarioSIS
                </button>
            </form>
            <p><small>Or manually go to <a href="index.php" target="_blank">index.php</a> and use the credentials above.</small></p>
        </div>
        
    <?php else: ?>
        <div class="error">
            <h2>✗ Login Test Failed</h2>
            <p><strong>Error:</strong> <?php echo htmlspecialchars($result['message']); ?></p>
            <?php if (isset($result['username'])): ?>
                <p><strong>Username found:</strong> <?php echo htmlspecialchars($result['username']); ?></p>
            <?php endif; ?>
            <?php if (isset($result['stored_hash'])): ?>
                <p><strong>Password hash:</strong> <?php echo htmlspecialchars($result['stored_hash']); ?></p>
            <?php endif; ?>
        </div>
        
        <p><a href="init_admin.php">⟵ Go back to Admin Initialization</a></p>
    <?php endif; ?>
    
    <hr>
    <h3>Available Scripts:</h3>
    <ul>
        <li><a href="init_admin.php">Admin User Initialization</a></li>
        <li><a href="query_users.php">View All Users</a></li>
        <li><a href="test_pdf.php">Test PDF Generation</a></li>
        <li><a href="index.php">RosarioSIS Main Login</a></li>
    </ul>
</body>
</html>