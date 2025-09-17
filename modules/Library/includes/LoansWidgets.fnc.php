<?php
/**
 * Loans Widgets functions
 *
 * @package Library module
 */


/**
 * Loans Widgets
 *
 * @example LoansWidgets( 'borrowed_documents' );
 * @example LoansWidgets( 'has_late_documents' );
 *
 * @global $extra The $extra variable contains the options for the Search function
 * @global $_ROSARIO sets $_ROSARIO['SearchTerms']
 *
 * @param string $item       'borrowed_documents' (Borrowed documents Widget) or 'has_late_documents' (Has late documents Widget).
 * @param bool   $add_column Add (Documents or Past Due) column to Student / Staff list.
 */
function LoansWidgets( $item, $add_column = true )
{
	global $extra,
		$_ROSARIO;

	$type = ( isset( $_REQUEST['type'] ) && $_REQUEST['type'] === 'staff' ) ?
		'staff' :
		'student';

	$extra['search'] = issetVal( $extra['search'] );

	$extra['SELECT'] = issetVal( $extra['SELECT'] );

	$extra['WHERE'] = issetVal( $extra['WHERE'] );

	$extra['columns_after'] = issetVal( $extra['columns_after'] );

	$extra['functions'] = issetVal( $extra['functions'] );

	switch ( $item )
	{
		// Borrowed documents Widget.
		case 'borrowed_documents':

			// If subject selected.
			if ( ! empty( $_REQUEST['borrowed_documents'] ) )
			{
				if ( $type === 'student' )
				{
					// Limit student search to borrowed documents >= X.
					$extra['WHERE'] .= " AND (SELECT COUNT(1)
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<=CURRENT_DATE
						AND USER_ID*-1=s.STUDENT_ID)>='" . $_REQUEST['borrowed_documents'] . "'";
				}
				else
				{
					// Limit student search to borrowed documents >= X.
					$extra['WHERE'] .= " AND (SELECT COUNT(1)
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<=CURRENT_DATE
						AND USER_ID=s.STAFF_ID)>='" . $_REQUEST['borrowed_documents'] . "'";
				}

				// Add SearchTerms.
				if ( empty( $extra['NoSearchTerms'] ) )
				{
					$_ROSARIO['SearchTerms'] = issetVal( $_ROSARIO['SearchTerms'], '' );

					$_ROSARIO['SearchTerms'] .= '<b>' . dgettext( 'Library', 'Borrowed documents' ) . '</b> &ge; ' .
						$_REQUEST['borrowed_documents'] . '<br />';
				}
			}

			// Add Widget to Search.
			$extra['search'] .= '<tr><td><label for="borrowed_documents">' . dgettext( 'Library', 'Borrowed documents' ) . '</label></td>
				<td>&ge; ' . TextInput( '', 'borrowed_documents', '', 'type="number" min="1" max="99"' ) .
				'</td></tr>';

			if ( $add_column )
			{
				// Add Documents column to ListOutput.
				$extra['columns_after']['LIBRARY_DOCUMENTS'] = dgettext( 'Library', 'Documents' );

				if ( $type === 'student' )
				{
					// Count number of borrowed documents.
					$extra['SELECT'] .= ",(SELECT COUNT(1)
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<=CURRENT_DATE
						AND USER_ID*-1=s.STUDENT_ID) AS LIBRARY_DOCUMENTS";
				}
				else
				{
					// Count number of borrowed documents.
					$extra['SELECT'] .= ",(SELECT COUNT(1)
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<=CURRENT_DATE
						AND USER_ID=s.STAFF_ID) AS LIBRARY_DOCUMENTS";
				}
			}

		break;

		// Has late documents Widget.
		case 'has_late_documents':

			// If subject selected.
			if ( ! empty( $_REQUEST['has_late_documents'] ) )
			{
				if ( $type === 'student' )
				{
					// Limit student search to documents being late.
					$extra['WHERE'] .= " AND s.STUDENT_ID IN (SELECT USER_ID*-1
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<CURRENT_DATE
						AND DATE_DUE<CURRENT_DATE
						AND USER_ID<0)";
				}
				else
				{
					// Limit student search to documents being late.
					$extra['WHERE'] .= " AND s.STAFF_ID IN (SELECT USER_ID
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<CURRENT_DATE
						AND DATE_DUE<CURRENT_DATE
						AND USER_ID>0)";
				}

				// Add SearchTerms.
				if ( empty( $extra['NoSearchTerms'] ) )
				{
					$_ROSARIO['SearchTerms'] = issetVal( $_ROSARIO['SearchTerms'], '' );

					$_ROSARIO['SearchTerms'] .= '<b>' . dgettext( 'Library', 'Has late documents' ) . ':</b> ' .
						_( 'Yes' ) . '<br />';
				}
			}

			// Add Widget to Search.
			$extra['search'] .= '<tr><td><label for="has_late_documents">' . dgettext( 'Library', 'Has late documents' ) . '</label></td>
				<td>' . CheckboxInput( '', 'has_late_documents', '', '', true ) .
				'</td></tr>';

			if ( $add_column )
			{
				// Add Late column to ListOutput.
				$extra['columns_after']['LIBRARY_LATE'] = dgettext( 'Library', 'Past Due' );

				$extra['functions']['LIBRARY_LATE'] = 'LoansWidgetsMakeLate';

				if ( $type === 'student' )
				{
					// Has Late documents?
					$extra['SELECT'] .= ",(SELECT 1
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<CURRENT_DATE
						AND DATE_DUE<CURRENT_DATE
						AND USER_ID*-1=s.STUDENT_ID
						LIMIT 1) AS LIBRARY_LATE";
				}
				else
				{
					// Has Late documents?
					$extra['SELECT'] .= ",(SELECT 1
						FROM library_loans
						WHERE DATE_RETURN IS NULL
						AND DATE_BEGIN<CURRENT_DATE
						AND DATE_DUE<CURRENT_DATE
						AND USER_ID=s.STAFF_ID
						LIMIT 1) AS LIBRARY_LATE";
				}
			}

		break;
	}
}

/**
 * Make Late column
 *
 * @param string $value  Late value.
 * @param string $column 'LIBRARY_LATE' column.
 *
 * @return string Empty if not late, 'Yes' if export to PDF, else check button.
 */
function LoansWidgetsMakeLate( $value, $column = 'LIBRARY_LATE' )
{
	if ( ! $value )
	{
		return '';
	}

	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		return _( 'Yes' );
	}

	return button( 'check' );
}
