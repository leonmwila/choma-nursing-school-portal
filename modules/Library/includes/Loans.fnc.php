<?php
/**
 * Library functions
 *
 * @package Library module
 */

// Allow User to Return if can Edit Loans & document can is lent or late!
function LibraryCanReturnDocument( $document_id )
{
	if ( (string) (int) $document_id != $document_id
		|| $document_id < 1 )
	{
		return false;
	}

	$status = LibraryGetDocumentStatus( $document_id );

	return ( $status === 'lent'
			|| $status === 'late' )
		&& ( AllowEdit( 'Library/Loans.php' ) || AllowEdit( 'Library_Premium/Loans.php' ) );
}
