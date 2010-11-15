<?php

include_once "sql.php";

class shifts
{

    public function shift_list($day, $month, $year)
    {
        return get_shifts($day, $month, $year);
    }

    public function insert_shift($shift_start, $shift_end)
    {
        if ($shift_start >= $shift_end)
        {
            return false;
        }
        else
        {
            return insert_shift($shift_start, $shift_end);
        }
    }

    public function shift_workers($shift_id)
    {
        return get_shift_workers($shift_id);
    }

    public function active_employees()
    {
        return get_active_employees();
    }
}

?>
