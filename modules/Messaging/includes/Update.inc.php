<?php
/**
 * Update database
 *
 * @package Messaging module
 */

global $DatabaseType;

// @since 11.0 SQL add CREATED_AT column
$created_at_column_exists = DBGetOne( "SELECT 1
	FROM information_schema.columns
	WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
	AND table_name='messages'
	AND column_name='created_at';" );

if ( ! $created_at_column_exists )
{
	db_query( "ALTER TABLE messages
		ADD COLUMN created_at timestamp;" );

	// Update CREATED_AT with DATETIME.
	db_query( "UPDATE messages
		SET CREATED_AT=" . DBEscapeIdentifier( 'DATETIME' ) . "
		WHERE CREATED_AT IS NULL;" );

	// Finally, set DEFAULT for CREATED_AT.
	db_query( "ALTER TABLE messages
		ALTER COLUMN created_at SET DEFAULT current_timestamp;" );
}
