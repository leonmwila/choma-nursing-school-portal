<?php
/**
 * The base configurations of RosarioSIS
 *
 * @package RosarioSIS
 */

/**
 * Database Settings
 */

// Database type: postgresql or mysql.
$DatabaseType = 'postgresql';

// Database server hostname: use localhost if on same server.
$DatabaseServer = 'db';

// Database username.
$DatabaseUsername = 'rosariosis';

// Database password.
$DatabasePassword = 'rosariosis_password';

// Database name.
$DatabaseName = 'rosariosis';

// Database port.
$DatabasePort = '5432';

/**
 * Paths
 */

/**
 * Full path to the database dump utility for this server
 */
$DatabaseDumpPath = '/usr/bin/pg_dump';

/**
 * Full path to wkhtmltopdf binary file
 */
$wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf';

/**
 * Default school year
 */
$DefaultSyear = '2025';

/**
 * Email address to receive notifications
 */
$RosarioNotifyAddress = '';

/**
 * Email address to receive errors
 */
$RosarioErrorsAddress = '';

/**
 * Locales
 *
 * Add other languages you want to support here
 *
 * @see locale/ folder
 */
$RosarioLocales = [ 'en_US.utf8' ];

/**
 * Currency Symbol
 */
$LocaleCurrency = '$';

/**
 * Display student names in LastName, FirstName format
 */
$RosarioNameFormat = 'LastFirst';
