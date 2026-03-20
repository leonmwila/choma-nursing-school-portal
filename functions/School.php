<?php
/**
 * School functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Update School Array
 *
 * @example UpdateSchoolArray( UserSchool() );
 *
 * @since 11.5 Use $_ROSARIO global var instead of $_SESSION
 *
 * @global $_ROSARIO['SchoolData']
 *
 * @param int $school_id School ID (optional). Defaults to User School ID.
 *
 * @return void    Fill $_ROSARIO['SchoolData'] global var
 */
function UpdateSchoolArray( $school_id = 0 )
{
	global $_ROSARIO;

	if ( ! $school_id )
	{
		$school_id = UserSchool();
	}

	$school_RET = DBGet( "SELECT *,
		(SELECT COUNT(*) FROM schools WHERE SYEAR='" . UserSyear() . "') AS SCHOOLS_NB
		FROM schools
		WHERE ID='" . (int) $school_id . "'
		AND SYEAR='" . UserSyear() . "'" );

	$_ROSARIO['SchoolData'] = isset( $school_RET[1] ) ? $school_RET[1] : [];
}


/**
 * Get School Info
 *
 * @example DrawHeader( SchoolInfo( 'TITLE' ) );
 *
 * @param  string       $field schools DB table field name (optional). Defaults to every School Fields.
 *
 * @return string|array School Field or array with every School Fields
 */
function SchoolInfo( $field = '' )
{
	global $_ROSARIO;

	if ( empty( $_ROSARIO['SchoolData'] )
		|| $_ROSARIO['SchoolData']['ID'] != UserSchool()
		|| $_ROSARIO['SchoolData']['SYEAR'] != UserSyear() )
	{
		UpdateSchoolArray( UserSchool() );
	}

	if ( $field )
	{
		return $_ROSARIO['SchoolData'][ (string) $field ];
	}

	return $_ROSARIO['SchoolData'];
}


/**
 * Get School Logo path with theme fallback
 *
 * @since 12.8
 *
 * @param  int    $school_id School ID (optional). Defaults to current User school, then default or first school in current year.
 * @param  string $theme     Theme name (optional). Defaults to Config( 'THEME' ).
 *
 * @return string Logo path
 */
function SchoolLogoPath( $school_id = 0, $theme = '' )
{
	if ( ! $school_id )
	{
		$school_id = (int) UserSchool();
	}

	if ( ! $school_id )
	{
		$school_id = (int) Config( 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL' );
	}

	if ( ! $school_id )
	{
		$school_id = (int) DBGetOne( "SELECT ID
			FROM schools
			WHERE SYEAR='" . Config( 'SYEAR' ) . "'
			ORDER BY ID LIMIT 1" );
	}

	if ( $school_id )
	{
		$logo_pic = 'assets/school_logo_' . (int) $school_id . '.jpg';

		if ( file_exists( $logo_pic ) )
		{
			return $logo_pic;
		}
	}

	if ( ! $theme )
	{
		$theme = Config( 'THEME' );
	}

	return 'assets/themes/' . $theme . '/logo.png';
}
