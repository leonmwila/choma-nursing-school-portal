<?php
/**
 * Library
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Library/includes/common.fnc.php';
require_once 'modules/Library/includes/Library.fnc.php';

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

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'] );

$_REQUEST['id'] = issetVal( $_REQUEST['id'] );

if ( AllowEdit()
	&& $_REQUEST['modfunc'] === 'save' )
{
	$id = $_REQUEST['id'];

	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'library_categories', 'library_documents' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables'][ $id ]['DESCRIPTION'] ) );
		}

		// FJ fix SQL bug invalid sort order.
		if ( empty( $columns['SORT_ORDER'] )
			|| is_numeric( $columns['SORT_ORDER'] ) )
		{
			// FJ added SQL constraint TITLE is not null.
			if ( ( ! isset( $columns['TITLE'] )
					|| ! empty( $columns['TITLE'] ) )
				&& ( ! isset( $columns['REF'] )
					|| ! empty( $columns['REF'] ) ) )
			{
				if ( ! empty( $columns['AUTHOR'] ) )
				{
					$columns['AUTHOR'] = trim( $columns['AUTHOR'] );
				}

				$go = false;

				// Update Document / Category.
				if ( $id !== 'new' )
				{
					if ( isset( $columns['CATEGORY_ID'] )
						&& $columns['CATEGORY_ID'] != $_REQUEST['category_id'] )
					{
						$_REQUEST['category_id'] = $columns['CATEGORY_ID'];
					}

					$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";

						$go = true;
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
				}
				// New Document / Category.
				else
				{
					$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

					// New Document.
					if ( $table === 'library_documents' )
					{
						if ( isset( $columns['CATEGORY_ID'] ) )
						{
							$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

							unset( $columns['CATEGORY_ID'] );
						}

						$fields = 'CATEGORY_ID,CREATED_BY,';

						$values = "'" . $_REQUEST['category_id'] . "','" . User( 'STAFF_ID' ) . "',";
					}
					// New Category.
					elseif ( $table === 'library_categories' )
					{
						$fields = '';

						$values = '';
					}

					// School, Created by.
					/*$fields .= 'SCHOOL_ID,';

					$values .= "'" . UserSchool() . "',";*/

					foreach ( (array) $columns as $column => $value )
					{
						if ( ! empty( $value )
							|| $value == '0' )
						{
							$fields .= DBEscapeIdentifier( $column ) . ',';

							$values .= "'" . $value . "',";

							$go = true;
						}
					}

					$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
				}

				if ( $go )
				{
					DBQuery( $sql );

					if ( $id === 'new' )
					{
						if ( function_exists( 'DBLastInsertID' ) )
						{
							$id = DBLastInsertID();
						}
						else
						{
							// @deprecated since RosarioSIS 9.2.1.
							$id = DBGetOne( "SELECT LASTVAL();" );
						}

						if ( $table === 'library_documents' )
						{
							$_REQUEST['id'] = $id;
						}
						elseif ( $table === 'library_categories' )
						{
							$_REQUEST['category_id'] = $id;
						}
					}
				}
			}
			else
				$error[] = _( 'Please fill in the required fields' );
		}
		else
			$error[] = _( 'Please enter valid Numeric data.' );
	}

	// Unset tables & redirect URL.
	RedirectURL( [ 'tables', 'modfunc' ] );
}

// Delete Document / Category.
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Library', 'Document' ) ) )
		{
			$delete_sql = "DELETE FROM library_documents
				WHERE ID='" . (int) $_REQUEST['id'] . /*"'
				AND SCHOOL_ID='" . UserSchool() . */"';";

			$delete_sql .= "DELETE FROM library_loans
				WHERE DOCUMENT_ID='" . (int) $_REQUEST['id'] . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& intval( $_REQUEST['category_id'] ) > 0
		&& ! LibraryCategoryHasDocuments( $_REQUEST['category_id'] ) )
	{
		if ( DeletePrompt( dgettext( 'Library', 'Document Category' ) ) )
		{
			DBQuery( "DELETE FROM library_categories
				WHERE ID='" . (int) $_REQUEST['category_id'] . /*"'
				AND SCHOOL_ID='" . UserSchool() . */"'" );

			// Unset modfunc & category ID redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
}

// Lend Document to Student or Staff submit.
if ( $_REQUEST['modfunc'] === 'lend_submit' )
{
	if ( ! empty( $_REQUEST['id'] )
		&& LibraryCanLendDocument( $_REQUEST['id'] )
		&& ( UserStudentID() > 0 || UserStaffID() > 0 ) )
	{
		$user_id = $_REQUEST['type'] === 'student' ? ( UserStudentID() * -1 ) : UserStaffID();

		$requested_dates = RequestedDates(
			$_REQUEST['year_values'],
			$_REQUEST['month_values'],
			$_REQUEST['day_values']
		);

		$insert_sql = 'INSERT INTO library_loans';

		$fields = 'DOCUMENT_ID,USER_ID,CREATED_BY,';

		$values = "'" . $_REQUEST['id'] . "','" . $user_id . "','" . User( 'STAFF_ID' ) . "',";

		$fields .= 'DATE_BEGIN,DATE_DUE,COMMENTS';

		$values .= "'" . $requested_dates['DATE_BEGIN'] . "','" .
			$requested_dates['DATE_DUE'] . "','" .
			$_REQUEST['values']['COMMENTS'] . "'";

		$insert_sql .= '(' . $fields . ') values(' . $values . ')';

		DBQuery( $insert_sql );

		$note[] = button( 'check', '', '', 'bigger' ) .
			dgettext( 'Library', 'The document was lent.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( [ 'modfunc', 'type' ] );
}


// Lend Document to Student or Staff view.
if ( $_REQUEST['modfunc'] === 'lend' )
{
	if ( ! empty( $_REQUEST['id'] )
		&& LibraryCanLendDocument( $_REQUEST['id'] ) )
	{
		$_REQUEST['type'] = ( isset( $_REQUEST['type'] ) && $_REQUEST['type'] === 'staff' ) ?
			'staff' :
			( User( 'PROFILE' ) === 'teacher' ? 'staff' : 'student' );

		LibraryDrawUserTypeHeader( '&modfunc=lend&id=' . $_REQUEST['id'] );
		LibraryDrawDocumentHeader( $_REQUEST['id'] );

		$extra = isset( $extra ) ? $extra : [];

		$extra['action'] = '&id=' . $_REQUEST['id'] . '&type=' . $_REQUEST['type'];

		$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=Library/Library.php' . $extra['action'] .
				'&modfunc=lend';

		if ( $_REQUEST['type'] === 'student' )
		{
			Search( 'student_id', $extra );
		}
		else
		{
			Search( 'staff_id', $extra );
		}

		if ( $_REQUEST['type'] === 'student' && UserStudentID()
			|| $_REQUEST['type'] === 'staff' && UserStaffID() )
		{
			echo '<br />';

			PopTable( 'header', dgettext( 'Library', 'Lend' ), 'style="max-width: 300px;"' );

			echo '<form action="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=Library/Library.php' . $extra['action'] . '&modfunc=lend_submit' ) :
				_myURLEncode( 'Modules.php?modname=Library/Library.php' . $extra['action'] . '&modfunc=lend_submit' ) ) . '" method="POST">';

			$div = $allow_na = false;

			$required = true;

			$document = LibraryGetDocument( $_REQUEST['id'] );

			$current_user_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
				FROM " . ( $_REQUEST['type'] === 'student' ? 'students' : 'staff' ) .
				" WHERE " .
				( $_REQUEST['type'] === 'student' ?
					"STUDENT_ID='" . UserStudentID() . "'" :
					"STAFF_ID='" . UserStaffID() . "'" ) );

			echo '<table><tr><td><p>' . sprintf(
				dgettext( 'Library', 'Lend "%s" to %s' ),
				$document['TITLE'],
				$current_user_RET[1]['FULL_NAME']
			) . '</p></td></tr>';

			echo '<tr><td>' . DateInput(
				DBDate(),
				'values[DATE_BEGIN]',
				_( 'Date' ),
				$div,
				$allow_na,
				$required
			) . '</td></tr>';

			echo '<tr><td>' . DateInput(
				// Defaults to today + 1 month.
				date( 'Y-m-d', time() + 60 * 60 * 24 * 30 ),
				'values[DATE_DUE]',
				_( 'Due Date' ),
				$div,
				$allow_na,
				$required
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				'',
				'values[COMMENTS]',
				_( 'Comments' ),
				'maxlength="1000" size="30"',
				$div
			) . '</td></tr></table>';

			echo '<br /><div class="center">' . SubmitButton( _( 'Submit' ) ) . '</div>';

			echo '</form>';

			PopTable( 'footer' );
		}
	}
	else
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );
	echo ErrorMessage( $note, 'note' );

	$RET = [];

	$title = '';

	// ADDING & EDITING FORM.
	if ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = LibraryGetDocument( $_REQUEST['id'] );

		$title = LibraryMakeDocumentAPA( $_REQUEST['id'] );

		// Set Document Category ID if not set yet or if wrong.
		if ( empty( $_REQUEST['category_id'] )
			|| $_REQUEST['category_id'] != $RET['CATEGORY_ID'] )
		{
			$_REQUEST['category_id'] =  $RET['CATEGORY_ID'];
		}
	}
	elseif ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !== 'new'
		&& empty( $_REQUEST['id'] ) )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,SORT_ORDER
			FROM library_categories
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'" );

		$RET = $RET[1];

		$title = $RET['TITLE'];
	}
	elseif ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] === 'new' )
	{
		$title = dgettext( 'Library', 'New Document' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = isset( $_REQUEST['category_id'] ) ? $_REQUEST['category_id'] : null;
	}
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = dgettext( 'Library',  'New Document Category' );

		$RET['CATEGORY_ID'] = 'new';
	}

	echo LibraryGetDocumentsForm(
		$title,
		$RET,
		isset( $extra_fields ) ? $extra_fields : []
	);

	echo LibraryGetDocumentLoansHeader( $RET );

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM library_categories
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	// DISPLAY THE MENU.
	echo '<div class="st">';

	LibraryDocumentsMenuOutput( $categories_RET, $_REQUEST['category_id'] );

	echo '</div>';

	// DOCUMENTS.
	if ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !=='new'
		&& $categories_RET )
	{
		$documents_RET = DBGet( "SELECT ID,TITLE,AUTHOR,YEAR,REF
			FROM library_documents
			WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . /*"'
			AND SCHOOL_ID='" . UserSchool() . */"'
			ORDER BY TITLE",
		[
			'TITLE' => 'LibraryMakeDocumentAPA',
		] );

		echo '<div class="st">';

		LibraryDocumentsMenuOutput( $documents_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}


