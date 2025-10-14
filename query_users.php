<?php
// Query users from the RosarioSIS staff table
// This connects to the PostgreSQL database running in Docker

// Database configuration (from config.inc.php)
$host = 'localhost';  // We're connecting from host to container
$port = '5432';
$dbname = 'rosariosis';
$username = 'rosariosis';
$password = 'rosariosis_password';

try {
    // Create PDO connection
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h2>RosarioSIS Users Query</h2>";
    echo "<h3>All Staff/Users in the System:</h3>";
    
    // Query all staff members (users)
    $stmt = $pdo->query("
        SELECT 
            staff_id,
            first_name,
            last_name,
            username,
            profile,
            syear,
            schools
        FROM staff 
        ORDER BY staff_id
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th>Staff ID</th>";
        echo "<th>First Name</th>";
        echo "<th>Last Name</th>";
        echo "<th>Username</th>";
        echo "<th>Profile</th>";
        echo "<th>School Year</th>";
        echo "<th>Schools</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $profile_name = '';
            switch ($user['profile']) {
                case 'A':
                    $profile_name = 'Administrator';
                    break;
                case 'T':
                    $profile_name = 'Teacher';
                    break;
                case 'P':
                    $profile_name = 'Parent';
                    break;
                default:
                    $profile_name = $user['profile'];
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['staff_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($profile_name) . " (" . htmlspecialchars($user['profile']) . ")</td>";
            echo "<td>" . htmlspecialchars($user['syear']) . "</td>";
            echo "<td>" . htmlspecialchars($user['schools']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p><strong>Total users found:</strong> " . count($users) . "</p>";
    } else {
        echo "<p>No users found in the database.</p>";
    }
    
    // Check if default users exist
    echo "<h3>Default User Check:</h3>";
    $default_users = ['admin', 'teacher', 'parent'];
    
    foreach ($default_users as $default_user) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE username = ?");
        $stmt->execute([$default_user]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "<p>✅ Default user '<strong>$default_user</strong>' exists</p>";
        } else {
            echo "<p>❌ Default user '<strong>$default_user</strong>' NOT found</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2>Database Connection Error:</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure the Docker containers are running and the database is accessible.</p>";
}
?>

<h3>Default Login Credentials:</h3>
<div style="background-color: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;">
    <h4>According to the README.md file:</h4>
    <ul>
        <li><strong>Username:</strong> admin</li>
        <li><strong>Password:</strong> admin</li>
    </ul>
    <p><em>Note: The actual password in the database is hashed. The plain text password is "admin".</em></p>
</div>

<h3>User Profile Types:</h3>
<ul>
    <li><strong>A</strong> = Administrator (full system access)</li>
    <li><strong>T</strong> = Teacher (teacher features)</li>
    <li><strong>P</strong> = Parent (parent portal access)</li>
</ul>

<hr>
<p><small>Query executed at: <?php echo date('Y-m-d H:i:s'); ?></small></p>