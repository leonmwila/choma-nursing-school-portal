<?php
/**
 * Detailed Login Test for RosarioSIS
 * This script tests the login process step by step
 */

// Include necessary files
require_once 'config.inc.php';
require_once 'functions/Password.php';

// Mock required functions
if (!function_exists('do_action')) {
    function do_action($hook, $args = []) { return true; }
}
if (!function_exists('Config')) {
    function Config($key) {
        $defaults = ['SYEAR' => '2025'];
        return isset($defaults[$key]) ? $defaults[$key] : '';
    }
}
if (!function_exists('DBEscapeString')) {
    function DBEscapeString($str) { return $str; }
}
if (!function_exists('DBGet')) {
    function DBGet($sql) {
        global $DatabaseType, $DatabaseServer, $DatabaseUsername, $DatabasePassword, $DatabaseName, $DatabasePort;
        
        try {
            if ($DatabaseType === 'postgresql') {
                $dsn = "pgsql:host=$DatabaseServer;port=$DatabasePort;dbname=$DatabaseName";
            } else {
                $dsn = "mysql:host=$DatabaseServer;port=$DatabasePort;dbname=$DatabaseName";
            }
            
            $pdo = new PDO($dsn, $DatabaseUsername, $DatabasePassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                return false;
            }
            
            // Convert to RosarioSIS format (1-indexed array)
            $formatted = array();
            foreach ($results as $i => $row) {
                $formatted[$i + 1] = array();
                foreach ($row as $key => $value) {
                    $formatted[$i + 1][strtoupper($key)] = $value;
                }
            }
            
            return $formatted;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Simulate the login process
 */
function simulate_login($username, $password) {
    echo "<h3>Testing Login Process for: " . htmlspecialchars($username) . "</h3>";
    
    // Step 1: Lookup staff user
    echo "<h4>Step 1: Looking up staff user...</h4>";
    $login_RET = DBGet("SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN,PASSWORD
        FROM staff
        WHERE SYEAR='" . Config('SYEAR') . "'
        AND UPPER(USERNAME)=UPPER('" . $username . "')");
    
    if ($login_RET) {
        echo "<p>✓ Staff user found: " . $login_RET[1]['USERNAME'] . " (Profile: " . $login_RET[1]['PROFILE'] . ")</p>";
        
        // Test password
        if (match_password($login_RET[1]['PASSWORD'], $password)) {
            echo "<p>✓ Staff password verified</p>";
            return ['success' => true, 'type' => 'staff', 'data' => $login_RET];
        } else {
            echo "<p>✗ Staff password failed</p>";
            $login_RET = false;
        }
    } else {
        echo "<p>⚠ No staff user found</p>";
    }
    
    // Step 2: If staff login failed, try student
    echo "<h4>Step 2: Looking up student user...</h4>";
    $student_RET = false; // Initialize to prevent warnings
    
    if (!$login_RET) {
        $student_RET = DBGet("SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,
            s.FAILED_LOGIN,s.PASSWORD,se.START_DATE
            FROM students s,student_enrollment se
            WHERE se.STUDENT_ID=s.STUDENT_ID
            AND se.SYEAR='" . Config('SYEAR') . "'
            AND CURRENT_DATE>=se.START_DATE
            AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
            AND UPPER(s.USERNAME)=UPPER('" . $username . "')");
        
        if ($student_RET) {
            echo "<p>✓ Student user found: " . $student_RET[1]['USERNAME'] . "</p>";
            
            if (match_password($student_RET[1]['PASSWORD'], $password)) {
                echo "<p>✓ Student password verified</p>";
                return ['success' => true, 'type' => 'student', 'data' => $student_RET];
            } else {
                echo "<p>✗ Student password failed</p>";
                $student_RET = false;
            }
        } else {
            echo "<p>⚠ No student user found</p>";
        }
    }
    
    // Step 3: Check for inactive students
    if (!$login_RET && !$student_RET) {
        echo "<h4>Step 3: Checking for inactive/unverified students...</h4>";
        $inactive_student = DBGet("SELECT s.USERNAME,s.STUDENT_ID,
            s.LAST_LOGIN,s.FAILED_LOGIN,se.START_DATE,s.PASSWORD
            FROM students s,student_enrollment se
            WHERE se.STUDENT_ID=s.STUDENT_ID
            AND se.SYEAR='" . Config('SYEAR') . "'
            AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
            AND UPPER(s.USERNAME)=UPPER('" . $username . "')");
        
        if ($inactive_student && match_password($inactive_student[1]['PASSWORD'], $password)) {
            echo "<p>⚠ Found inactive/unverified student account</p>";
            return ['success' => false, 'type' => 'inactive_student', 'message' => 'Account not yet activated'];
        }
    }
    
    return ['success' => false, 'type' => 'none', 'message' => 'Invalid credentials'];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>RosarioSIS Login Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
        .success { color: green; background: #f0f8f0; padding: 10px; margin: 10px 0; }
        .error { color: red; background: #fff0f0; padding: 10px; margin: 10px 0; }
        .warning { color: orange; background: #fff8f0; padding: 10px; margin: 10px 0; }
        h3 { border-bottom: 2px solid #ccc; }
        h4 { color: #666; }
        .test-form { background: #f9f9f9; padding: 15px; margin: 15px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>RosarioSIS Login Debug Test</h1>
    
    <div class="test-form">
        <h2>Test Different Credentials</h2>
        <form method="POST">
            <table>
                <tr>
                    <td>Username:</td>
                    <td><input type="text" name="test_username" value="<?php echo htmlspecialchars($_POST['test_username'] ?? 'admin'); ?>" required></td>
                </tr>
                <tr>
                    <td>Password:</td>
                    <td><input type="password" name="test_password" value="<?php echo htmlspecialchars($_POST['test_password'] ?? 'admin123'); ?>" required></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Test Login" style="padding: 10px 20px;">
                    </td>
                </tr>
            </table>
        </form>
    </div>
    
    <?php
    // Test login if form submitted
    if (isset($_POST['test_username']) && isset($_POST['test_password'])) {
        $result = simulate_login($_POST['test_username'], $_POST['test_password']);
        
        if ($result['success']) {
            echo "<div class='success'>";
            echo "<h3>✓ Login Successful!</h3>";
            echo "<p><strong>Type:</strong> " . ucfirst($result['type']) . "</p>";
            echo "<p><strong>User Data:</strong></p>";
            echo "<pre>" . print_r($result['data'], true) . "</pre>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<h3>✗ Login Failed</h3>";
            echo "<p><strong>Reason:</strong> " . $result['message'] . "</p>";
            echo "</div>";
        }
    } else {
        // Default test with admin credentials
        echo "<h2>Default Test with Admin Credentials</h2>";
        $result = simulate_login('admin', 'admin123');
        
        if ($result['success']) {
            echo "<div class='success'>";
            echo "<p>✓ Default admin login working correctly!</p>";
            echo "</div>";
        }
    }
    ?>
    
    <h2>System Information</h2>
    <p><strong>Current School Year:</strong> <?php echo Config('SYEAR'); ?></p>
    <p><strong>Database Type:</strong> <?php echo $DatabaseType; ?></p>
    <p><strong>Database Server:</strong> <?php echo $DatabaseServer; ?></p>
    
    <h3>Available Test Pages:</h3>
    <ul>
        <li><a href="index.php">Main RosarioSIS Login</a></li>
        <li><a href="test_login.php">Quick Login Test</a></li>
        <li><a href="init_admin.php">Admin Initialization</a></li>
        <li><a href="query_users.php">View All Users</a></li>
    </ul>
</body>
</html>