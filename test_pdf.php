<?php
// Test PDF generation with the new configuration
// Place this file in the RosarioSIS root directory and access via browser

// Set basic constants that RosarioSIS expects
if (!defined('ROSARIO')) {
    define('ROSARIO', true);
}

// Initialize session and request variables
if (!isset($_SESSION)) {
    $_SESSION = [];
}
if (!isset($_REQUEST)) {
    $_REQUEST = [];
}
$_REQUEST['modname'] = 'test';

// Mock the do_action function if it doesn't exist
if (!function_exists('do_action')) {
    function do_action($hook, $args = []) {
        // Mock function - does nothing
        return true;
    }
}

// Mock the Preferences function if it doesn't exist
if (!function_exists('Preferences')) {
    function Preferences($key) {
        $defaults = [
            'THEME' => 'FlatSIS',
            'PAGE_SIZE' => 'Letter'
        ];
        return isset($defaults[$key]) ? $defaults[$key] : '';
    }
}

// Mock the ProgramTitle function
if (!function_exists('ProgramTitle')) {
    function ProgramTitle() {
        return 'Test PDF Generation';
    }
}

// Include the necessary RosarioSIS files
require_once 'config.inc.php';
require_once 'functions/PreparePHP_SELF.fnc.php';
require_once 'functions/PDF.php';

// Function to test basic PDF generation
function test_pdf_generation() {
    global $wkhtmltopdfPath;
    
    echo "<h1>PDF Generation Test</h1>";
    echo "<p>wkhtmltopdf path: " . htmlspecialchars($wkhtmltopdfPath) . "</p>";
    
    // Check if wkhtmltopdf exists and is executable
    if (file_exists($wkhtmltopdfPath) && is_executable($wkhtmltopdfPath)) {
        echo "<p>✓ wkhtmltopdf binary is accessible</p>";
    } else {
        echo "<p>✗ wkhtmltopdf binary is not accessible at: " . htmlspecialchars($wkhtmltopdfPath) . "</p>";
        return false;
    }
    
    // Test PDF generation
    echo "<h2>Testing PDF Generation...</h2>";
    
    try {
        $handle = PDFStart([
            'mode' => 3, // Save mode
            'css' => true
        ]);
        
        echo "<h1>Test PDF Document</h1>";
        echo "<p>This is a test document to verify PDF generation is working correctly.</p>";
        echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
        echo "<p>If you can see this PDF, the configuration is working!</p>";
        
        $pdf_file = PDFStop($handle);
        
        if ($pdf_file && file_exists($pdf_file)) {
            echo "<p>✓ PDF generated successfully: " . htmlspecialchars($pdf_file) . "</p>";
            echo "<p>File size: " . filesize($pdf_file) . " bytes</p>";
            echo "<p><a href='data:application/pdf;base64," . base64_encode(file_get_contents($pdf_file)) . "' download='test.pdf'>Download Test PDF</a></p>";
            
            // Clean up
            unlink($pdf_file);
            return true;
        } else {
            echo "<p>✗ PDF generation failed</p>";
            return false;
        }
        
    } catch (Exception $e) {
        echo "<p>✗ Error during PDF generation: " . htmlspecialchars($e->getMessage()) . "</p>";
        return false;
    }
}

// Test network connectivity
echo "<h2>Testing Network Connectivity</h2>";
$test_url = "http://localhost/assets/themes/FlatSIS/stylesheet_wkhtmltopdf.css";
$headers = @get_headers($test_url);
if ($headers && strpos($headers[0], '200') !== false) {
    echo "<p>✓ Network connectivity to localhost is working</p>";
} else {
    echo "<p>✗ Network connectivity issue to localhost</p>";
    echo "<p>Testing alternative: http://127.0.0.1/assets/themes/FlatSIS/stylesheet_wkhtmltopdf.css</p>";
    $test_url2 = "http://127.0.0.1/assets/themes/FlatSIS/stylesheet_wkhtmltopdf.css";
    $headers2 = @get_headers($test_url2);
    if ($headers2 && strpos($headers2[0], '200') !== false) {
        echo "<p>✓ Network connectivity to 127.0.0.1 is working</p>";
    } else {
        echo "<p>✗ Network connectivity issue to 127.0.0.1 as well</p>";
    }
}

// Run the test
test_pdf_generation();

echo "<h2>Environment Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Operating System: " . php_uname() . "</p>";
echo "<p>Temp Directory: " . sys_get_temp_dir() . "</p>";
echo "<p>Current Working Directory: " . getcwd() . "</p>";

// Check if required functions are available
$required_functions = ['proc_open', 'exec', 'shell_exec'];
foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "<p>✓ Function $func is available</p>";
    } else {
        echo "<p>✗ Function $func is disabled</p>";
    }
}
?>