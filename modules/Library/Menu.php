<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Library module
 * - Add Menu entries to other modules
 *
 * @package Library module
 */

/**
 * Use dgettext() function instead of _() for Module specific strings translation
 * see locale/README file for more information.
 */
$module_name = dgettext( 'Library', 'Library' );

if ( empty( $RosarioModules['Library_Premium'] ) )
{
	// Menu entries for the Library module.
	$menu['Library']['admin'] = [ // Admin menu.
		'title' => dgettext( 'Library', 'Library' ),
		'default' => 'Library/Library.php', // Program loaded by default when menu opened.
		'Library/Library.php' => dgettext( 'Library', 'Library' ),
		'Library/Loans.php' => dgettext( 'Library', 'Loans' ),
	] + issetVal( $menu['Library']['admin'], [] );

	if ( ! file_exists( 'modules/Library_Premium/' ) || ROSARIO_DEBUG )
	{
		// Upsell Premium
		$menu['Library']['admin'][] = dgettext( 'Library', 'Premium' );

		$menu['Library']['admin']['Library/DocumentFields.php'] = dgettext( 'Library', 'Document Fields' );

		$menu['Library']['admin'][] = _( 'Reports' );

		$menu['Library']['admin']['Library/LoansBreakdown.php'] = dgettext( 'Library', 'Loans Breakdown' );
	}

	$menu['Library']['teacher'] = [ // Teacher menu.
		'title' => dgettext( 'Library', 'Library' ),
		'default' => 'Library/Library.php', // Program loaded by default when menu opened.
		'Library/Library.php' => dgettext( 'Library', 'Library' ),
		'Library/Loans.php' => dgettext( 'Library', 'Loans' ),
	] + issetVal( $menu['Library']['teacher'], [] );

	$menu['Library']['parent'] = [ // Parent & student menu.
		'title' => dgettext( 'Library', 'Library' ),
		'default' => 'Library/Library.php', // Program loaded by default when menu opened.
		'Library/Library.php' => dgettext( 'Library', 'Library' ),
		'Library/Loans.php' => dgettext( 'Library', 'Loans' ),
	] + issetVal( $menu['Library']['parent'], [] );
}
