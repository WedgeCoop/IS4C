function show_shifts(shift_date)
{
    $('#shifts').load('shifts.php?date=' + shift_date);
}

function add_shift(start_time, end_time, date)
{
    time_test = /^([1-9]|1[0-2]|0[1-9]){1}(:[0-5][0-9]\s?[aApP][mM]){1}$/;

    if (time_test.test(start_time))
    {
        if (start_time.substr(6, 2).toUpperCase() == 'PM' && start_time.substr(0, 2) != 12)
        {
            start_time = (parseInt(start_time.substr(0, 2)) + 12) + start_time.substr(3, 2);
        }
        else
        {
            start_time = start_time.substr(0, 2) + start_time.substr(3, 2);
        }
        if (end_time.substr(6, 2).toUpperCase() == 'PM' && end_time.substr(0, 2) != 12)
        {
            end_time = (parseInt(end_time.substr(0, 2)) + 12) + end_time.substr(3, 2);
        }
        else
        {
            end_time = end_time.substr(0, 2) + end_time.substr(3, 2);
        }
        date = date.toString();
        start = new Date(date.substring(0, 4), date.substring(4, 6) - 1, date.substring(6, 8), start_time.substr(0, 2), start_time.substr(3, 4), 0).getTime() / 1000;
        end = new Date(date.substring(0, 4), date.substring(4, 6), date.substring(6, 8), end_time.substr(0, 2), end_time.substr(3, 4)).getTime() / 1000;
        if (start >= end)
        {
            alert ("The start time must be before the end time.");
        }
        else
        {
            $('#empty').load('/time/api/shift_management.php?action=add_shift&start=' + start + '&end=' + end);
        }
    }
    else
    {
        alert('Invalid time entered');
    }
}

function delete_shift(shift_id, date)
{
    $('#empty').load('/time/api/shift_management.php?action=delete_shift&id=' + shift_id);
}

function add_employee_to_shift(emp_id, shift_id)
{
    $('#empty').load('/time/api/shift_management.php?action=add_shift_worker&emp_id=' + emp_id + '&shift_id=' + shift_id);
}

function remove_employee_from_shift(emp_id, shift_id)
{
    $('#empty').load('/time/api/shift_management.php?action=delete_shift_worker&emp_id=' + emp_id + '&shift_id=' + shift_id);
}

function shift_worked(emp_id, shift_id, add_remove)
{
    if (add_remove)
    {
        $('#empty').load('/time/api/shift_management.php?action=mark_shift_worked&emp_id=' + emp_id + '&shift_id=' + shift_id);
    }
    else
    {
        $('#empty').load('/time/api/shift_management.php?action=unmark_shift_worked&emp_id=' + emp_id + '&shift_id=' + shift_id);
    }
}
