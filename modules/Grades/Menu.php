<?php
/**
 * Grades module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Grades/includes/Balance.php';

// Initialize the menu array
$menu['Grades'] = [];

// Set up student/parent menu
if ( User( 'PROFILE' ) === 'student' || User( 'PROFILE' ) === 'parent' )
{
    if ( User( 'PROFILE' ) === 'student' && StudentHasOutstandingBalance() )
    {
        DisplayGradesRestrictedMessage();
        return;
    }

    $menu['Grades']['parent'] = [
        'title' => _( 'Grades' ),
        'default' => 'Grades/StudentGrades.php',
        'Grades/StudentGrades.php' => _( 'Grades' ),
        'Grades/FinalGrades.php' => _( 'Final Grades' ),
        'Grades/ReportCards.php' => _( 'Report Cards' ),
        'Grades/Transcripts.php' => _( 'Transcripts' ),
    ];
}

// Set up admin menu if not student
if ( User( 'PROFILE' ) === 'admin' )
{
    $menu['Grades']['admin'] = [
        'title' => _( 'Grades' ),
        'default' => 'Grades/GPARankList.php',
        'Grades/ReportCards.php' => _( 'Report Cards' ),
        'Grades/HonorRoll.php' => _( 'Honor Roll' ),
        'Grades/Transcripts.php' => _( 'Transcripts' ),
        1 => _( 'Reports' ),
        'Grades/StudentGrades.php' => _( 'Student Grades' ),
        'Grades/ProgressReports.php' => _( 'Progress Reports' ),
        'Grades/TeacherCompletion.php' => _( 'Teacher Completion' ),
        'Grades/GradeBreakdown.php' => _( 'Grade Breakdown' ),
        'Grades/FinalGrades.php' => _( 'Final Grades' ),
        'Grades/GPARankList.php' => _( 'GPA / Class Rank List' ),
        2 => _( 'Setup' ),
        'Grades/Configuration.php' => _( 'Configuration' ),
        'Grades/ReportCardGrades.php' => _( 'Grading Scales' ),
        'Grades/ReportCardComments.php' => _( 'Report Card Comments' ),
        'Grades/ReportCardCommentCodes.php' => _( 'Comment Codes' ),
        'Grades/EditHistoryMarkingPeriods.php' => _( 'History Marking Periods' ),
        3 => _( 'Utilities' ),
        'Grades/EditReportCardGrades.php' => _( 'Historical Grades' ),
        'Grades/MassCreateAssignments.php' => _( 'Mass Create Assignments' ),
    ];
}

// Set up teacher menu
if ( User( 'PROFILE' ) === 'teacher' )
{
    $menu['Grades']['teacher'] = [
        'title' => _( 'Grades' ),
        'default' => 'Grades/Grades.php',
        'Grades/InputFinalGrades.php' => _( 'Input Final Grades' ),
        'Grades/ReportCards.php' => _( 'Report Cards' ),
        1 => _( 'Gradebook' ),
        'Grades/Grades.php' => _( 'Grades' ),
        'Grades/Assignments.php' => _( 'Assignments' ),
        'Grades/AnomalousGrades.php' => _( 'Anomalous Grades' ),
        'Grades/ProgressReports.php' => _( 'Progress Reports' ),
        'Grades/GradebookBreakdown.php' => _( 'Grade Breakdown' ),
        2 => _( 'Reports' ),
        'Grades/StudentGrades.php' => _( 'Student Grades' ),
        'Grades/FinalGrades.php' => _( 'Final Grades' ),
        'Grades/GPARankList.php' => _( 'GPA / Class Rank List' ),
        3 => _( 'Setup' ),
        'Grades/Configuration.php' => _( 'Configuration' ),
        'Grades/ReportCardGrades.php' => _( 'Grading Scales' ),
        'Grades/ReportCardComments.php' => _( 'Report Card Comments' ),
        'Grades/ReportCardCommentCodes.php' => _( 'Comment Codes' ),
    ];
}

// Set up parent menu
if ( User( 'PROFILE' ) === 'parent' )
{
    $menu['Grades']['parent'] = [
        'title' => _( 'Grades' ),
        'default' => 'Grades/StudentGrades.php',
        'Grades/StudentGrades.php' => _( 'Gradebook Grades' ),
        'Grades/StudentAssignments.php' => _( 'Assignments' ),
        'Grades/FinalGrades.php' => _( 'Final Grades' ),
        'Grades/ReportCards.php' => _( 'Report Cards' ),
        'Grades/ProgressReports.php' => _( 'Progress Reports' ),
        'Grades/Transcripts.php' => _( 'Transcripts' ),
        'Grades/GPARankList.php' => _( 'GPA / Class Rank' ),
    ];
}

// Add grade-related programs to Users module if enabled
if ( isset( $RosarioModules['Users'] ) )
{
    if ( ! isset( $menu['Users']['admin'] ) )
    {
        $menu['Users']['admin'] = [];
    }

    $menu['Users']['admin'] += [
        'Users/TeacherPrograms.php&include=Grades/InputFinalGrades.php' => _( 'Input Final Grades' ),
        'Users/TeacherPrograms.php&include=Grades/Grades.php' => _( 'Gradebook Grades' ),
        'Users/TeacherPrograms.php&include=Grades/AnomalousGrades.php' => _( 'Anomalous Grades' ),
    ];
}
