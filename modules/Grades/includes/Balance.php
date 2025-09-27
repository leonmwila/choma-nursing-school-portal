<?php
/**
 * Check Student Balance functions
 *
 * @package RosarioSIS
 * @subpackage modules/Grades
 */

/**
 * Check if Student has Outstanding Balance
 * Returns true if student has balance greater than 0
 *
 * @since 1.0
 *
 * @return boolean True if student has outstanding balance
 */
function StudentHasOutstandingBalance()
{
    // Only check for students.
    if ( User( 'PROFILE' ) !== 'student' )
    {
        return false;
    }

    $student_id = UserStudentID();

    if ( ! $student_id )
    {
        return false;
    }

    // Calculate student's balance using the same query from StudentBalances.php
    $balance = DBGetOne( "SELECT (COALESCE((SELECT SUM(f.AMOUNT)
        FROM billing_fees f
        WHERE f.STUDENT_ID='" . (int) $student_id . "'
        AND f.SYEAR='" . UserSyear() . "'), 0) -
        COALESCE((SELECT SUM(p.AMOUNT)
        FROM billing_payments p
        WHERE p.STUDENT_ID='" . (int) $student_id . "'
        AND p.SYEAR='" . UserSyear() . "'), 0)) AS BALANCE" );

    // If no balance found, return false
    if ( $balance === null || $balance === false )
    {
        return false;
    }

    // Convert to float for proper comparison
    $balance = (float) $balance;

    return ( $balance > 0 );
}

/**
 * Display Grades Restricted Message
 * Shows message and link to restricted page if student has outstanding balance
 *
 * @since 1.0
 *
 * @return void
 */
function DisplayGradesRestrictedMessage()
{
    $message = '<div class="alert alert-danger text-center">
        <h4 class="alert-heading mb-3">' . _( 'Access Restricted' ) . '</h4>
        <p class="mb-2">' . _( 'You have an outstanding balance. Please settle your balance to access your grades.' ) . '</p>
        <p class="mb-0"><em>' . _( 'Contact the administration office for more information.' ) . '</em></p>
    </div>';

    echo $message;
}
