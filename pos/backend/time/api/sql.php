<?php

$mysql_connection = mysql_connect("localhost", "root");

if (!$mysql_connection) {
    echo "SQL Connection Failed<br /> " . mysql_error();
    exit;
}

if (!mysql_select_db("time")) {
    echo "Unable to open time schema<br /> " . mysql_error();
    exit;
}

function get_shifts($day, $month, $year)
{
    $day = mysql_real_escape_string($day);
    $month = mysql_real_escape_string($month);
    $year = mysql_real_escape_string($year);
    $count = 0;
    $sql_query = '
        SELECT shift_id,
            start,
            end
            FROM shifts
            WHERE start BETWEEN "' . $year . '-' . $month . '-' . $day . ' 00:00:00" AND "' . $year . '-' . $month . '-' . $day . ' 23:59:59"
            ORDER BY start;';
    $shift_results = mysql_query($sql_query);

    $shift_list = array();
    while ($row = mysql_fetch_assoc($shift_results))
    {
        $count++;
        $shift_list[$count]['shift_id'] = $row['shift_id'];
        $shift_list[$count]['start'] = $row['start'];
        $shift_list[$count]['end'] =  $row['end'];
    }
    return $shift_list;
}

function insert_shift($shift_start, $shift_end)
{
    $shift_start = mysql_real_escape_string($shift_start);
    $shift_end = mysql_real_escape_string($shift_end);
    if ($shift_start < $shift_end)
    {
        $sql_query = '
            INSERT INTO shifts
                (
                    start,
                    end
                )
                VALUES
                (
                    FROM_UNIXTIME(' . $shift_start . '),
                    FROM_UNIXTIME(' . $shift_end . ')
                );';
        mysql_query($sql_query);
        return true;
    }
    else
    {
        return false;
    }
}

function update_shift($shift_id, $shift_start, $shift_end)
{
    $shift_id = mysql_real_escape_string($shift_id);
    $shift_start = mysql_real_escape_string($shift_start);
    $shift_end = mysql_real_escape_string($shift_end);
    $sql_query = '
        UPDATE shifts
            SET start = ' . $shift_start . ',
                end = ' . $shift_end . '
            WHERE shift_id = ' . $shift_id . ';';
    mysql_query($sql_query);
    return true;
}

function delete_shift($shift_id)
{
    $shift_id = mysql_real_escape_string($shift_id);
    $sql_query = '
        DELETE FROM shifts
            WHERE shift_id = ' . $shift_id . ';';
    mysql_query($sql_query);
    return true;
}

function get_shift_workers($shift_id)
{
    $shift_id = mysql_real_escape_string($shift_id);
    $count = 0;
    $sql_query = '
        SELECT emp_no,
            FirstName,
            LastName
            FROM is4c_op.employees
                JOIN employee_shifts
                    USING (emp_no)
            WHERE shift_id = ' . $shift_id . ';';
    $worker_results = mysql_query($sql_query);

    $worker_list = array();
    while ($row = mysql_fetch_assoc($worker_results))
    {
        $count++;
        $worker_list[$count]['emp_no'] = $row['emp_no'];
        $worker_list[$count]['first_name'] =  $row['FirstName'];
        $worker_list[$count]['last_name'] =  $row['LastName'];
        $worker_list[$count]['worked'] = check_shift_worked($row['emp_no'], $shift_id);
    }
    return $worker_list;
}

function add_shift_worker($emp_id, $shift_id)
{
    $emp_id = mysql_real_escape_string($emp_id);
    $shift_id = mysql_real_escape_string($shift_id);
    $sql_query = '
        INSERT INTO employee_shifts
        (
            emp_no,
            shift_id
        )
            VALUES
            (
                ' . $emp_id . ',
                ' . $shift_id . '
            );';
    mysql_query($sql_query);
    return true;
}

function delete_shift_worker($emp_id, $shift_id)
{
    $emp_id = mysql_real_escape_string($emp_id);
    $shift_id = mysql_real_escape_string($shift_id);
    $sql_query = '
        DELETE FROM employee_shifts
        WHERE emp_no = ' . $emp_id . '
            AND shift_id = ' . $shift_id . ';';
    mysql_query($sql_query);
    return true;
}

function get_active_employees()
{
    $count = 0;
    $sql_query = '
        SELECT emp_no,
            FirstName,
            LastName
            FROM is4c_op.employees
            WHERE EmpActive = 1
            ORDER BY LastName,
                FirstName;';
    $employee_results = mysql_query($sql_query);

    $employee_list = array();
    while ($row = mysql_fetch_assoc($employee_results))
    {
        $count++;
        $employee_list[$count]['emp_no'] = $row['emp_no'];
        $employee_list[$count]['first_name'] =  $row['FirstName'];
        $employee_list[$count]['last_name'] =  $row['LastName'];
    }
    return $employee_list;
}

function mark_shift_worked($emp_id, $shift_id)
{
    $emp_id = mysql_real_escape_string($emp_id);
    $shift_id = mysql_real_escape_string($shift_id);
    $sql_query = '
        INSERT INTO volunteer_hours
            (
                emp_no,
                shift_id
            )
            VALUES
            (
                ' . $emp_id . ',
                ' . $shift_id . '
            );';
    mysql_query($sql_query);
    return true;
}

function unmark_shift_worked($emp_id, $shift_id)
{
    $emp_id = mysql_real_escape_string($emp_id);
    $shift_id = mysql_real_escape_string($shift_id);
    $sql_query = '
        DELETE FROM volunteer_hours
        WHERE emp_no = ' . $emp_id . '
            AND shift_id = ' . $shift_id . ';';
    mysql_query($sql_query);
    return true;
}

function check_shift_worked($emp_id, $shift_id)
{
    $emp_id = mysql_real_escape_string($emp_id);
    $shift_id = mysql_real_escape_string($shift_id);
    $count = 0;
    $sql_query = '
        SELECT id
            FROM time.volunteer_hours
            WHERE shift_id = ' . $shift_id . '
                AND emp_no = ' . $emp_id . ';';
    $worked_results = mysql_query($sql_query);

    while ($row = mysql_fetch_assoc($worked_results))
    {
        $count++;
    }
    if ($count > 0)
    {
        return true;
    }
    else
    {
        return false;
    }

}
?>
