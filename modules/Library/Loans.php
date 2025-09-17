<?php
/**
 * Library Loans
 * User and Document views.
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Library/includes/common.fnc.php';
require_once 'modules/Library/includes/Loans.fnc.php';
require_once 'modules/Library/includes/LoansWidgets.fnc.php';

if ( file_exists( 'modules/School_Setup/includes/Addon.fnc.php' ) )
{
	// @since RosarioSIS 11.4
	require_once 'modules/School_Setup/includes/Addon.fnc.php';

	if ( function_exists( 'AddonUpsellPremium' )
		&& User( 'PROFILE' ) === 'admin'
		&& ( ! file_exists( 'modules/Library_Premium/' ) || ROSARIO_DEBUG ) )
	{
		// @since RosarioSIS 12.1
		echo AddonUpsellPremium( 'module', 'Library', 'PREMIUM.md' );
	}
}

DrawHeader( ProgramTitle() );

if ( version_compare( ROSARIO_VERSION, '4.5', '>=' ) )
{
	// Set start date.
	$start_date = RequestedDate( 'start', date( 'Y-m-d', strtotime( '-6 months' ) ) );

	// Set end date.
	$end_date = RequestedDate( 'end', DBDate() );
}
else
{
	// Requested start date.
	if ( isset( $_REQUEST['day_start'] )
		&& isset( $_REQUEST['month_start'] )
		&& isset( $_REQUEST['year_start'] ) )
	{
		$start_date = RequestedDate(
			$_REQUEST['year_start'],
			$_REQUEST['month_start'],
			$_REQUEST['day_start']
		);
	}

	if ( empty( $start_date ) )
	{
		// User loans history: set start date 6 months ago.
		$start_date = date( 'Y-m-d', strtotime( '-6 months' ) );
	}

	// Requested end date.
	if ( isset( $_REQUEST['day_end'] )
		&& isset( $_REQUEST['month_end'] )
		&& isset( $_REQUEST['year_end'] ) )
	{
		$end_date = RequestedDate(
			$_REQUEST['year_end'],
			$_REQUEST['month_end'],
			$_REQUEST['day_end']
		);
	}

	if ( empty( $end_date ) )
	{
		//  Set end date as current day.
		$end_date = DBDate();
	}
}

if ( $_REQUEST['modfunc'] === 'return' )
{
	if ( ! empty( $_REQUEST['document_id'] )
		&& LibraryCanReturnDocument( $_REQUEST['document_id'] ) )
	{
		// Document is late or lent.
		// Do not check for user, just return every found loan entry (there should be only one!).
		DBQuery( "UPDATE library_loans
			SET DATE_RETURN=NOW()
			WHERE DOCUMENT_ID='" . (int) $_REQUEST['document_id'] . "'
			AND DATE_RETURN IS NULL" );

		$note[] = dgettext( 'Library', 'Document was returned.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

echo ErrorMessage( $note, 'note' );

if ( ! $_REQUEST['modfunc'] )
{
	if ( ! empty( $_REQUEST['document_id'] ) )
	{
		LibraryDrawDocumentHeader( $_REQUEST['document_id'] );

		$user_id = 0;
	}
	else
	{
		$_REQUEST['type'] = ( isset( $_REQUEST['type'] ) && $_REQUEST['type'] === 'staff' ) ?
			'staff' :
			( User( 'PROFILE' ) === 'teacher' ? 'staff' : 'student' );

		LibraryDrawUserTypeHeader();

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$extra = isset( $extra ) ? $extra : [];

			$extra['action'] = '&type=' . $_REQUEST['type'];

			$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=Library/Loans.php' . $extra['action'];

			// @since 2.4 Add Loans Widgets.
			LoansWidgets( 'borrowed_documents' );
			LoansWidgets( 'has_late_documents' );

			if ( $_REQUEST['type'] === 'student' )
			{
				Search( 'student_id', $extra );
			}
			else
			{
				Search( 'staff_id', $extra );
			}
		}

		if ( $_REQUEST['type'] === 'student' && UserStudentID()
			|| $_REQUEST['type'] === 'staff' && UserStaffID() )
		{
			$user_id = $_REQUEST['type'] === 'student' ?
				UserStudentID() * -1 :
				UserStaffID();
		}
		elseif ( User( 'PROFILE' ) !== 'admin' )
		{
			$user_id = User( 'STAFF_ID' );
		}
	}

	if ( isset( $user_id ) )
	{
		echo '<form action="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] ) ) . '" method="GET">';

		DrawHeader(
			_( 'From' ) . ' ' . PrepareDate( $start_date, '_start', false ) . ' - ' .
			_( 'To' ) . ' ' . PrepareDate( $end_date, '_end', false ) .
			Buttons( _( 'Go' ) )
		);

		echo '</form>';

		// Format DB data.
		$loans_functions = [
			'DOCUMENT_ID' => '_libraryMakeDocument', // APA & link to Library Document.
			'USER_ID' => '_libraryMakeUser', // Full Name & Photo Tip Message & link to Loans history.
			'DATE_BEGIN' => 'ProperDate',
			'DATE_DUE' => 'ProperDate',
			'DATE_RETURN' => '_libraryMakeReturnDateTime',
		];

		$loans_RET = DBGet( "SELECT
			DOCUMENT_ID,USER_ID,DATE_BEGIN,DATE_DUE,DATE_RETURN,COMMENTS
			FROM library_loans
			WHERE DATE_BEGIN >='" . $start_date . "'
			AND DATE_BEGIN <='" . $end_date . "'
			AND " . ( $user_id ? "USER_ID='" . (int) $user_id . "'" :
				"DOCUMENT_ID='" . (int) $_REQUEST['document_id'] . "'" ) . "
			ORDER BY DATE_BEGIN DESC", $loans_functions );

		$loans_columns = [
			'DATE_BEGIN' => _( 'Date' ),
			'DATE_DUE' => _( 'Due Date' ),
			'DATE_RETURN' => dgettext( 'Library', 'Return Date' ),
			'COMMENTS' => _( 'Comments' ),
		];

		$loans_columns = ( $user_id ?
			[ 'DOCUMENT_ID' => dgettext( 'Library', 'Document' ) ] :
			[ 'USER_ID' => _( 'User' ) ] )
			+ $loans_columns;

		ListOutput(
			$loans_RET,
			$loans_columns,
			dgettext( 'Library', 'Loan' ),
			dgettext( 'Library', 'Loans' ),
			[],
			[],
			[ 'count' => true, 'save' => true ]
		);
	}
}


function _libraryMakeUser( $value, $column = 'USER_ID' )
{
	if ( ! $value )
	{
		return '';
	}

	$user_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME," .
		( $value < 0 ? "STUDENT_ID,'student' AS PROFILE" : 'STAFF_ID,PROFILE' ) .
		" FROM " . ( $value < 0 ? 'students' : 'staff' ) .
		" WHERE " .
		( $value < 0 ?
			"STUDENT_ID='" . ( $value * -1 ) . "'" :
			"STAFF_ID='" . (int) $value . "'" ) );

	// TODO Link to User Loans.
	$return = $user_RET[1]['FULL_NAME'];

	$profile_options = [
		'student' => _( 'Student' ),
		'admin' => _( 'Administrator' ),
		'teacher' => _( 'Teacher' ),
		'parent' => _( 'Parent' ),
		'none' => _( 'No Access' ),
	];

	$return .= ' (' . $profile_options[ $user_RET[1]['PROFILE'] ] . ')';

	if ( ! empty( $_REQUEST['LO_save'] )
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $return;
	}

	return '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Library/Loans.php&type=' .
			( $value < 0 ? 'student' : 'staff' ) . '&' .
			( $value < 0 ? 'student' : 'staff' ) .
			'_id=' . ( $value < 0 ? ( $value * -1 ) : $value ) ) :
		_myURLEncode( 'Modules.php?modname=Library/Loans.php&type=' .
			( $value < 0 ? 'student' : 'staff' ) . '&' .
			( $value < 0 ? 'student' : 'staff' ) .
			'_id=' . ( $value < 0 ? ( $value * -1 ) : $value ) ) ) . '">' . $return . '</a>';
}

function _libraryMakeReturnDateTime( $value, $column = 'DATE_RETURN' )
{
	global $THIS_RET;

	if ( ! empty( $_REQUEST['LO_save'] )
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value ? ProperDateTime( $value ) : '';
	}

	if ( ! $value )
	{
		$return = '';

		$status = LibraryGetDocumentStatus( $THIS_RET['DOCUMENT_ID'] );

		if ( $status === 'late' )
		{
			$return = '<span style="color: red">' . dgettext( 'Library', 'Past Due' ) . '</span>';
		}

		if ( AllowEdit() )
		{
			// Return link.
			return ( $return ? $return . ' &mdash; ' : '' ) .
				'<a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=Library/Loans.php&modfunc=return&document_id=' . $THIS_RET['DOCUMENT_ID'] ) :
				_myURLEncode( 'Modules.php?modname=Library/Loans.php&modfunc=return&document_id=' . $THIS_RET['DOCUMENT_ID'] ) ) . '">' . dgettext( 'Library', 'Return document' ) . '</a>';
		}

		return $return;
	}

	$return = ProperDateTime( $value, 'short' );

	if ( substr( $value, 0, 10 ) > $THIS_RET['DATE_DUE'] )
	{
		$return = '<span style="color: red">' . $return . '</span>';
	}

	return $return;
}


function _libraryMakeDocument( $value, $column = 'DOCUMENT_ID' )
{
	if ( ! $value )
	{
		return '';
	}

	$apa = LibraryMakeDocumentAPA( $value );

	if ( ! empty( $_REQUEST['LO_save'] )
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $apa;
	}

	if ( ! AllowUse( 'Library/Library.php' ) )
	{
		return $apa;
	}

	return '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Library/Library.php&id=' . $value ) :
		_myURLEncode( 'Modules.php?modname=Library/Library.php&id=' . $value ) ) . '">' .
		$apa . '</a>';
}
