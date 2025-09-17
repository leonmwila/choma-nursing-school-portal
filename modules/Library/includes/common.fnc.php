<?php
/**
 * Library common functions
 *
 * @package Library module
 */


function LibraryDrawUserTypeHeader( $action = '' )
{
	if ( User( 'PROFILE' ) !== 'admin' )
	{
		return '';
	}

	$header = '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=student' . $action ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=student' . $action ) ) . '">' .
		( $_REQUEST['type'] === 'student' ?
			'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

	$header .= ' | <a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=staff' . $action ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=staff' . $action ) ) . '">' .
		( $_REQUEST['type'] === 'staff' ?
			'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

	DrawHeader( $header );
}

function LibraryGetDocument( $document_id, $reset = false )
{
	static $documents = [];

	if ( (string) (int) $document_id != $document_id
		|| $document_id < 1 )
	{
		return [];
	}

	if ( isset( $documents[ $document_id ] )
		&& ! $reset )
	{
		return $documents[ $document_id ];
	}

	$document_RET = DBGet( "SELECT ID,CATEGORY_ID,TITLE,REF,
		DESCRIPTION,AUTHOR,YEAR,CREATED_AT,CREATED_BY,
		(SELECT TITLE
			FROM library_categories
			WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
		FROM library_documents
		WHERE ID='" . (int) $document_id . /*"'
		AND SCHOOL_ID='" . UserSchool() . */"'" );

	$documents[ $document_id ] = ( ! $document_RET ? [] : $document_RET[1] );

	return $documents[ $document_id ];
}

function LibraryMakeDocumentAPA( $value, $column = 'DOCUMENT_ID' )
{
	global $THIS_RET;

	if ( is_array( $THIS_RET )
		&& array_key_exists( 'TITLE', $THIS_RET )
		&& array_key_exists( 'AUTHOR', $THIS_RET )
		&& array_key_exists( 'YEAR', $THIS_RET ) )
	{
		$document = $THIS_RET;
	}
	else
	{
		$document = LibraryGetDocument( $value );
	}

	if ( ! $document )
	{
		return '';
	}

	// Truncate Document title to 80 chars.
	$title = mb_strlen( $document['TITLE'] ) <= 80 || ! empty( $_REQUEST['LO_save'] ) ?
		$document['TITLE'] :
		'<span title="' . $document['TITLE'] . '">' . mb_substr( $document['TITLE'], 0, 77 ) . '...</span>';

	$apa = $title;

	if ( $document['AUTHOR']
		|| $document['YEAR'] )
	{
		$apa .= ' (';

		$apa .= $document['AUTHOR'] ? $document['AUTHOR'] : '';

		$apa .= $document['YEAR'] ? ', ' . $document['YEAR'] : '';

		$apa .= ')';
	}

	return $apa;
}

if ( ! function_exists( 'LibraryDrawDocumentHeader' ) )
{
	function LibraryDrawDocumentHeader( $document_id )
	{
		$document = LibraryGetDocument( $document_id );

		if ( ! $document )
		{
			return;
		}

		$document_apa = LibraryMakeDocumentAPA( $document_id );

		$title = $document['CATEGORY_TITLE'] . ' - ';

		$title .= '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Library/Library.php&category_id=' .
				$document['CATEGORY_ID'] . '&id=' . $document['ID'] ) :
			_myURLEncode( 'Modules.php?modname=Library/Library.php&category_id=' .
				$document['CATEGORY_ID'] . '&id=' . $document['ID'] ) ) . '">' . $document_apa . '</a>';

		DrawHeader( $title );
	}
}

/**
 * Get Document Lending Status
 *
 * @param int $document_id Document ID.
 *
 * @return string Empty if no document, else free|lent|late.
 */
function LibraryGetDocumentStatus( $document_id )
{
	if ( (string) (int) $document_id != $document_id
		|| $document_id < 1 )
	{
		return false;
	}

	$document_lent_RET = DBGet( "SELECT DATE_DUE
		FROM library_loans
		WHERE DOCUMENT_ID='" . (int) $document_id . "'
		AND DATE_RETURN IS NULL
		AND DATE_BEGIN<=CURRENT_DATE
		LIMIT 1" );

	$lent = (bool) $document_lent_RET;

	if ( ! $lent )
	{
		return 'free';
	}

	$late = $lent && ( $document_lent_RET[1]['DATE_DUE'] < DBDate() );

	return $late ? 'late' : 'lent';
}
