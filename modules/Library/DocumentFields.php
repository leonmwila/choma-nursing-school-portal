<?php
/**
 * Document Fields (Premium program)
 *
 * @package Library module
 */

$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

$screenshot_lang = '';

if ( $lang_2_chars === 'fr'
	|| $lang_2_chars === 'es' )
{
	$screenshot_lang = '_' . $lang_2_chars;
}

$screenshot_url = 'https://www.rosariosis.org/wp-content/uploads/2019/03/rosariosis_library_premium' .
	$screenshot_lang . '_screenshot_3.png';

require_once 'modules/Library/includes/UpsellPremium.inc.php';
