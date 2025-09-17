<?php
/**
 * Library functions
 *
 * @package Library module
 */


/**
 * Get Document or Document Category Form
 *
 * @example echo GetDocumentsForm( $title, $RET );
 *
 * @example echo GetDocumentsForm(
 *              $title,
 *              $RET,
 *              null,
 *              array( 'text' => _( 'Text' ), 'textarea' => _( 'Long Text' ) )
 *          );
 *
 * @uses DrawHeader()
 * @uses MakeDocumentType()
 *
 * @param  string $title                 Form Title.
 * @param  array  $RET                   Document or Document Category Data.
 * @param  array  $extra_fields Extra fields for Document Category.
 * @param  array  $type_options          Associative array of Document Types (optional). Defaults to null.
 *
 * @return string Document or Document Category Form HTML
 */
function LibraryGetDocumentsForm( $title, $RET, $extra_fields = [], $type_options = null )
{
	$id = issetVal( $RET['ID'] );

	$category_id = issetVal( $RET['CATEGORY_ID'] );

	if ( empty( $id )
		&& empty( $category_id ) )
	{
		return '';
	}

	$new = $id === 'new' || $category_id === 'new';

	$action = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $category_id
		&& $category_id !== 'new' )
	{
		$action .= '&category_id=' . $category_id;
	}

	if ( $id )
	{
		$action .= '&id=' . $id;
	}

	if ( $id )
	{
		$full_table = 'library_documents';
	}
	else
	{
		$full_table = 'library_categories';
	}

	$action .= '&table=' . $full_table . '&modfunc=save';

	$form = '<form action="' . ( function_exists( 'URLEscape' ) ? URLEscape( $action ) : _myURLEncode( $action ) ) . '" method="POST" enctype="multipart/form-data">';

	$allow_edit = AllowEdit();

	$div = $allow_edit;

	$delete_button = '';

	if ( $allow_edit
		&& ! $new
		&& ( $id || ! LibraryCategoryHasDocuments( $category_id ) ) )
	{
		$delete_URL = ( function_exists( 'URLEscape' ) ?
			URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $category_id . '&id=' . $id ) :
			_myURLEncode( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $category_id . '&id=' . $id ) );

		$onclick_link = 'ajaxLink(' . json_encode( $delete_URL ) . ');';

		$delete_button = '<input type="button" value="' .
		( function_exists( 'AttrEscape' ) ? AttrEscape( _( 'Delete' ) ) : htmlspecialchars( _( 'Delete' ), ENT_QUOTES ) ) .
		'" ' .
		( version_compare( ROSARIO_VERSION, '12.5', '>=' ) ?
			// @since RosarioSIS 12.5 CSP remove unsafe-inline Javascript
			'class="onclick-ajax-link" data-link="' . $delete_URL . '"' :
			'onclick="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $onclick_link ) : htmlspecialchars( $onclick_link, ENT_QUOTES ) ) . '"' ) .
		' /> ';
	}

	ob_start();

	DrawHeader( $title, $delete_button . SubmitButton() );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col cellpadding-5"><tr class="st">';

	if ( $id )
	{
		// FJ document name required.
		$header .= '<td>' . TextInput(
			issetVal( $RET['TITLE'] ),
			'tables[' . $id . '][TITLE]',
			_( 'Document' ),
			'required maxlength=500' .
			( empty( $RET['TITLE'] ) ? ' size=35' : '' ),
			$div
		) . '</td>';

		$header .= '</tr><tr class="st">';

		// @todo Add TinyMCE Math plugin
		// @link https://stackoverflow.com/documents/20682820/inserting-mathematical-symbols-into-tinymce-4#20686520
		$header .= '<td colspan="2">' . TinyMCEInput(
			issetVal( $RET['DESCRIPTION'] ),
			'tables[' . $id . '][DESCRIPTION]',
			_( 'Description' )
		) . '</td>';

		$header .= '</tr><tr class="st">';

		// REFERENCE.
		if ( ! $new )
		{
			// You can't change a student document type after it has been created.
			$header .= '<td>' . NoInput(
				$RET['REF'],
				dgettext( 'Library', 'Reference' )
			) . '</td>';
		}
		else
		{
			$header .= '<td' . ( ! $category_id ? ' colspan="2"' : '' ) . '>' . TextInput(
				issetVal( $RET['REF'] ),
				'tables[' . $id . '][REF]',
				dgettext( 'Library', 'Reference' ),
				'maxlength=50 required',
				$div
			) . '</td>';
		}

		if ( $category_id )
		{
			// CATEGORIES.
			$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
				FROM library_categories
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

			foreach ( (array) $categories_RET as $category )
			{
				$categories_options[ $category['ID'] ] = $category['TITLE'];
			}

			$header .= '<td>' . SelectInput(
				$RET['CATEGORY_ID'] ? $RET['CATEGORY_ID'] : $category_id,
				'tables[' . $id . '][CATEGORY_ID]',
				_( 'Category' ),
				$categories_options,
				false,
				'required'
			) . '</td>';
		}

		$header .= '</tr><tr class="st">';

		$field = [
			'ID' => 'AUTHOR',
			'TYPE' => 'autox',
			'SELECT_OPTIONS' => '',
			'REQUIRED' => 'Y',
		];

		// AUTHOR.
		$header .= '<td>' . LibraryCustomSelectInput(
			$field,
			issetVal( $RET['AUTHOR'] ),
			'tables[' . $id . '][AUTHOR]',
			dgettext( 'Library', 'Author' )
		) . '</td>';

		// YEAR.
		$header .= '<td>' . TextInput(
			( empty( $RET['YEAR'] ) ? date( 'Y' ) : $RET['YEAR'] ),
			'tables[' . $id . '][YEAR]',
			_( 'Year' ),
			'type="number" min="-9999" max="9999" style="width: 65px;"',
			! $new
		) . '</td></tr>';

		// Extra Fields.
		if ( ! empty( $extra_fields ) )
		{
			$header .= '<tr><td colspan="2"><hr /></td></tr><tr class="st">';

			$i = 0;

			foreach ( (array) $extra_fields as $extra_field )
			{
				if ( $i && $i % 2 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_fields ) - 1 ) )
				{
					$colspan = abs( ( $i % 2 ) - 2 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}

			$header .= '</tr>';
		}

		$header .= '</table>';
	}
	// Documents Category Form.
	else
	{
		$title = isset( $RET['TITLE'] ) ? $RET['TITLE'] : '';

		// Title.
		$header .= '<td>' . TextInput(
			$title,
			'tables[' . $category_id . '][TITLE]',
			_( 'Title' ),
			'required maxlength=255' . ( empty( $title ) ? ' size=20' : '' )
		) . '</td>';

		// Sort Order.
		$header .= '<td>' . TextInput(
			( isset( $RET['SORT_ORDER'] ) ? $RET['SORT_ORDER'] : '' ),
			'tables[' . $category_id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		// Extra Fields.
		if ( ! empty( $extra_fields ) )
		{
			$i = 2;

			foreach ( (array) $extra_fields as $extra_field )
			{
				if ( $i % 3 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_fields ) + 1 ) )
				{
					$colspan = abs( ( $i % 3 ) - 3 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}
		}

		$header .= '</tr></table>';
	}

	ob_start();

	DrawHeader( $header );

	$form .= ob_get_clean();

	$form .= '</form>';

	return $form;
}

if ( ! function_exists( 'LibraryCustomFieldsForm' ) )
{
	// @todo Premium module.
	function LibraryCustomFieldsForm( $id )
	{
		return '';
	}
}


/**
 * Outputs Documents or Document Categories Menu
 *
 * @example DocumentsMenuOutput( $documents_RET, $_REQUEST['id'], $_REQUEST['category_id'] );
 * @example DocumentsMenuOutput( $categories_RET, $_REQUEST['category_id'] );
 *
 * @uses ListOutput()
 *
 * @param array  $RET         Document Categories (ID, TITLE, SORT_ORDER columns) or Documents (+ REF column) RET.
 * @param string $id          Document Category ID or Document ID.
 * @param string $category_id Document Category ID (optional). Defaults to '0'.
 */
function LibraryDocumentsMenuOutput( $RET, $id, $category_id = '0' )
{
	if ( $RET
		&& $id
		&& $id !== 'new' )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['ID'] == $id )
			{
				$RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	if ( ! $category_id )
	{
		$LO_columns = [
			'TITLE' => _( 'Category' ),
		];
	}
	else
	{
		$LO_columns = [
			'TITLE' => _( 'Document' ),
			'REF' => dgettext( 'Library', 'Reference' ),
		];
	}

	$LO_link = [];

	$LO_link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $category_id )
	{
		$LO_link['TITLE']['link'] .= '&category_id=' . $category_id;
	}

	$LO_link['TITLE']['variables'] = [ ( ! $category_id ? 'category_id' : 'id' ) => 'ID' ];

	$LO_link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=';

	$LO_link['add']['link'] .= $category_id ? $category_id . '&id=new' : 'new';

	// @since 10.1 Move add button to top of list when > 20 entries.
	$LO_link['add']['first'] = 20;

	// Fix Teacher cannot add new Library / not displaying Documents total.
	$tmp_allow_edit = false;

	if ( ! $category_id )
	{
		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Library', 'Document Category' ),
			dgettext( 'Library', 'Document Categories' ),
			$LO_link,
			[],
			$LO_options
		);
	}
	else
	{
		$LO_options['search'] = true;

		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Library', 'Document' ),
			dgettext( 'Library', 'Documents' ),
			$LO_link,
			[],
			$LO_options
		);
	}
}


/**
 * Category has Documents?
 *
 * @param int $category_id Documents Category ID.
 *
 * @return bool True if Category has Documents.
 */
function LibraryCategoryHasDocuments( $category_id )
{
	if ( (string) (int) $category_id != $category_id
		|| $category_id < 1 )
	{
		return false;
	}

	$category_has_documents = DBGet( "SELECT 1
		FROM library_documents
		WHERE CATEGORY_ID='" . (int) $category_id . /*"'
		AND SCHOOL_ID='" . UserSchool() . */"'
		LIMIT 1" );

	return (bool) $category_has_documents;
}


// Allow User to Lend if can Edit Loans & document can be lent!
function LibraryCanLendDocument( $document_id )
{
	if ( (string) (int) $document_id != $document_id
		|| $document_id < 1 )
	{
		return false;
	}

	$status = LibraryGetDocumentStatus( $document_id );

	return $status !== 'lent'
		&& $status !== 'late'
		&& ( AllowEdit( 'Library/Loans.php' ) || AllowEdit( 'Library_Premium/Loans.php' ) );
}


function LibraryGetDocumentLoansHeader( $RET )
{
	$header = '';

	$id = issetVal( $RET['ID'] );

	if ( ! $id
		|| $id === 'new' )
	{
		return $header;
	}

	$header = LibraryGetDocumentLoansHeaderLeft( $id );

	$header_right = LibraryGetDocumentLoansHeaderRight( $id );

	ob_start();

	DrawHeader( $header, $header_right );

	$loans_header = ob_get_clean();

	return $loans_header;
}


if ( ! function_exists( 'LibraryGetDocumentLoansHeaderLeft' ) )
{
	function LibraryGetDocumentLoansHeaderLeft( $document_id )
	{
		$header = '';

		if ( AllowUse( 'Library/Loans.php' )
			&& User( 'PROFILE' ) === 'admin' )
		{
			// Only admins can browse Loans history per Document view.
			$header .= '<a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=Library/Loans.php&document_id=' . $document_id ) :
				_myURLEncode( 'Modules.php?modname=Library/Loans.php&document_id=' . $document_id ) ) . '"><b>' .
				dgettext( 'Library', 'Loans' ) . '</b></a>';
		}

		return $header;
	}
}

function LibraryGetDocumentLoansHeaderRight( $document_id )
{
	$header_right = '';

	$status = LibraryGetDocumentStatus( $document_id );

	$status_label = dgettext( 'Library', 'Lent' );

	if ( $status === 'late'
		&& User( 'PROFILE' ) === 'admin' )
	{
		$status_label = '<span style="color: red">' . dgettext( 'Library', 'Past Due' ) . '</span>';
	}

	if ( LibraryCanLendDocument( $document_id ) )
	{
		$header_right .= '<form action="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&id=' . $document_id . '&modfunc=lend' ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&id=' . $document_id . '&modfunc=lend' ) ) . '" method="GET">';

		$header_right .= SubmitButton( dgettext( 'Library', 'Lend' ), '', '' );

		$header_right .= '</form>';
	}
	else
	{
		if ( $status === 'free' )
		{
			$status_label = '<span style="color: green">' . dgettext( 'Library', 'Available' ) . '</span>';
		}

		$header_right .= _( 'Status' ) . ': <b>' . $status_label . '</b>';
	}

	return $header_right;
}

// See Schools.php for more Inputs.
function LibraryCustomSelectInput( $field, $value_custom, $name, $title = '' )
{
	static $js_included = false;

	$options = $select_options = [];

	$col_name = DBEscapeIdentifier( $field['ID'] );

	if ( $field['SELECT_OPTIONS'] )
	{
		$options = explode(
			"\r",
			str_replace( [ "\r\n", "\n" ], "\r", $field['SELECT_OPTIONS'] )
		);
	}

	foreach ( (array) $options as $option )
	{
		$value = $option;

		// Exports specificities.
		if ( $field['TYPE'] === 'exports' )
		{
			$option = explode( '|', $option );

			$option = $value = $option[0];
		}

		if ( $value !== ''
			&& $option !== '' )
		{
			$select_options[$value] = $option;
		}
	}

	$div = true;

	// Get autos pull-down edited options.
	if ( $field['TYPE'] === 'autos'
		|| $field['TYPE'] === 'autox' )
	{
		$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
			FROM library_documents s
			WHERE s." . $col_name . " IS NOT NULL
			AND s." . $col_name . "<>''
			AND s." . $col_name . "<>'---'
			ORDER BY SORT_KEY";

		$options_RET = DBGet( $sql_options );

		if ( $value_custom === '---'
			|| count( $select_options ) <= 1
			&& empty( $options_RET ) )
		{
			// FJ new option.
			return TextInput(
				$value_custom === '---' ?
				[ '---', '<span style="color:red">-' . _( 'Edit' ) . '-</span>' ] :
				$value_custom,
				$name,
				$title,
				( $field['REQUIRED'] === 'Y' ? 'required' : '' ),
				$div
			);
		}

		// Add the 'new' option, is also the separator.
		$select_options['---'] = '-' . _( 'Edit' ) . '-';
	}

	foreach ( (array) $options_RET as $option )
	{
		$option_value = $option[$field['ID']];

		if ( $field['TYPE'] === 'autox' )
		{
			$select_options[$option_value] = $option_value;

			continue;
		}

		if ( ! isset( $select_options[$option_value] ) )
		{
			$select_options[$option_value] = '<span style="color:blue">' .
				$option_value . '</span>';
		}

		// Make sure the current value is in the list.
		if ( $value_custom != ''
			&& ! isset( $select_options[$value_custom] ) )
		{
			$select_options[$value_custom] = [
				$value_custom,
				'<span style="color:' . ( $field['TYPE'] === 'autos' ? 'blue' : 'green' ) . '">' .
				$value_custom . '</span>',
			];
		}
	}

	// When -Edit- option selected, change the auto pull-downs to text field.
	$return = '';

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ! $js_included
		&& version_compare( ROSARIO_VERSION, '12.5', '<' ) )
	{
		// @dperecated since RosarioSIS 12.5
		$js_included = true;

		ob_start();?>
		<script>
		function LibraryMaybeEditTextInput(el) {

			// -Edit- option's value is ---.
			if ( el.value === '---' ) {

				var $el = $( el );

				// Remove parent <div> if any
				if ( $el.parent('div').length ) {
					$el.unwrap();
				}

				// Remove the chosen select.
				$el.next('.chosen-container').remove();
				// Remove the Select2 select.
				$el.next('.select2-container').remove();

				// Remove the select input.
				$el.remove();

				// Show & enable the text input of the same name.
				$( '[name="' + el.name + '_text"]' ).prop('name', el.name).prop('disabled', false).show().focus();
			}
		}
		</script>
		<?php $return = ob_get_clean();
	}

	// FJ select field is required.
	$extra = ( $field['REQUIRED'] === 'Y' ? 'required' : '' );

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Add hidden & disabled Text input in case user chooses -Edit-.
		$return .= TextInput(
			'',
			$name . '_text',
			'',
			$extra . ' disabled style="display:none;"',
			false
		);
	}

	// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
	$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'ChosenSelectInput';

	$return .= $select_input_function(
		$value_custom,
		$name,
		$title,
		$select_options,
		'N/A',
		$extra .
		( version_compare( ROSARIO_VERSION, '12.5', '>=' ) ?
			// @since RosarioSIS 12.5 CSP remove unsafe-inline Javascript
			// When -Edit- option selected, change the auto pull-down to text field.
			' class="onchange-maybe-edit-select"' :
			' onchange="LibraryMaybeEditTextInput(this);"' ),
		$div
	);

	return $return;
}
