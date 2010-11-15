<?php
include_once "sql.php";

switch ($_GET['action'])
{
    case 'add_shift':
        insert_shift($_GET['start'], $_GET['end']);
        break;
    case 'delete_shift':
        delete_shift($_GET['id']);
        break;
    case 'add_shift_worker':
        add_shift_worker($_GET['emp_id'], $_GET['shift_id']);
        break;
    case 'delete_shift_worker':
        delete_shift_worker($_GET['emp_id'], $_GET['shift_id']);
        break;
    case 'mark_shift_worked';
        mark_shift_worked($_GET['emp_id'], $_GET['shift_id']);
        break;
    case 'unmark_shift_worked';
        unmark_shift_worked($_GET['emp_id'], $_GET['shift_id']);
        break;
}
?>
