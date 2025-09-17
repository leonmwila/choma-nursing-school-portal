<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Messaging module
 *
 * @package Messaging module
 */

// Menu entries for the Messaging module.
$menu['Messaging']['admin'] = [ // Admin menu.
	'title' => dgettext( 'Messaging', 'Messaging' ),
	'default' => 'Messaging/Messages.php', // Program loaded by default when menu opened.
	'Messaging/Messages.php' => dgettext( 'Messaging', 'Messages' ),
	'Messaging/Write.php' => dgettext( 'Messaging', 'Write' ),
] + issetVal( $menu['Messaging']['admin'], [] );

$menu['Messaging']['teacher'] = [ // Teacher menu
	'title' => dgettext( 'Messaging', 'Messaging' ),
	'default' => 'Messaging/Messages.php', // Program loaded by default when menu opened.
	'Messaging/Messages.php' => dgettext( 'Messaging', 'Messages' ),
	'Messaging/Write.php' => dgettext( 'Messaging', 'Write' ),
] + issetVal( $menu['Messaging']['admin'], [] );

$menu['Messaging']['parent'] = $menu['Messaging']['teacher'] + issetVal( $menu['Messaging']['parent'], [] ); // Parent & student menu.
