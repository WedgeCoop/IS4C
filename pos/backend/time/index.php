<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
    <head>
       <link rel="stylesheet" type="text/css" href="css/calendar.css" />
       <link rel="stylesheet" type="text/css" href="css/jquery.autocomplete.css" />
       <script type="text/javascript" src="js/calendar.js"></script>
       <script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
       <script type="text/javascript" src="js/jquery.autocomplete.min.js"></script>
    </head>
    <body>
        <?php
        $today = getdate();
        if (isset($_GET['date']))
        {
            $display_date = getdate($_GET['date']);
        }
        else
        {
            $display_date = $today;
        }
        $first_day = getdate(mktime(0, 0, 0, $display_date['mon'], 1, $display_date['year']));
        $last_day  = getdate(mktime(0, 0, 0, $display_date['mon'] + 1, 0, $display_date['year']));
        ?>
        <table class = 'calendar'>
            <tr class = 'calendar_head'>
                <th class='arrow'>
                    <a href="index.php?date=<?=mktime(0, 0, 0, $display_date['mon'] - 1, 1, $display_date['year'])?>">&lt;-</a>
                </th>
                <th colspan = '5'>
                    <?=$display_date['month']?> <?=$display_date['year']?>
                </th>
                <th class='arrow'>
                    <a href="index.php?date=<?=mktime(0, 0, 0, $display_date['mon'] + 1, 1, $display_date['year'])?>">-&gt;</a>
                </th>
            </tr>
            <tr class="days">
                <td>Su</td><td>Mo</td><td>Tu</td><td>We</td><td>Th</td><td>Fr</td><td>Sa</td></tr>
            <tr>
        <?php
        for($i = 1; $i <= $first_day['wday']; $i++)
        {
            echo '<td>&nbsp;</td>';
        }
        $actday = 0;
        for($i = $first_day['wday']; $i < 7; $i++)
        {
            $actday++;
            echo '<td ';
            if ($actday == $today['mday'] && $today['mon'] == $display_date['mon'] && $today['year'] == $display_date['year'])
            {
                echo "id='current_date'";
            }
            echo ' onclick="show_shifts(' . $display_date['year'] . str_pad($display_date['mon'], 2, '0', STR_PAD_LEFT) . str_pad($actday, 2, '0', STR_PAD_LEFT) . ');">';
            echo $actday;
            echo '</td>';
        }
        echo '</tr>';
        $fullWeeks = floor(($last_day['mday']-$actday)/7);

        for ($i=0; $i < $fullWeeks; $i++)
        {
            echo '<tr>';
            for ($j=0; $j < 7; $j++)
            {
                $actday++;
                echo '<td ';
                if ($actday == $today['mday'] && $today['mon'] == $display_date['mon'] && $today['year'] == $display_date['year'])
                {
                    echo "id='current_date'";
                }
                echo ' onclick="show_shifts(' . $display_date['year'] . str_pad($display_date['mon'], 2, '0', STR_PAD_LEFT) . str_pad($actday, 2, '0', STR_PAD_LEFT) . ');">';
                echo $actday;
                echo '</td>';
            }
            echo '</tr>';
        }
        if ($actday < $last_day['mday'])
        {
            echo '<tr>';

            for ($i=0; $i<7; $i++)
            {
                $actday++;
                if ($actday <= $last_day['mday'])
                {
                    echo '<td ';
                    if ($actday == $today['mday'] && $today['mon'] == $display_date['mon'] && $today['year'] == $display_date['year'])
                    {
                        echo "id='current_date'";
                    }
                    echo ' onclick="show_shifts(' . $display_date['year'] . str_pad($display_date['mon'], 2, '0', STR_PAD_LEFT) . str_pad($actday, 2, '0', STR_PAD_LEFT) . ');">';
                    echo $actday;
                    echo '</td>';
                }
                else
                {
                    echo '<td>&nbsp;</td>';
                }
            }
            echo '</tr>';
        }
        ?>
    </table>
    <br />
    <div id="shifts">
    </div>

    </body>
</html>

<?php
function gmgetdate($timestamp = null) {
    if (is_null($timestamp)) { $timestamp = time(); }

    $dateParts = array(
        'mday'    => 'j',
        'wday'    => 'w',
        'yday'    => 'z',
        'mon'     => 'n',
        'year'    => 'Y',
        'hours'   => 'G',
        'minutes' => 'i',
        'seconds' => 's',
        'weekday' => 'l',
        'month'   => 'F',
        0         => 'U'
    );

    while (list(, $value) = each($dateParts)) {
        $value = gmdate($value, $timestamp);
        if (is_numeric($value)) { $value = (int)$value; }
    }

    return $dateParts;
}
?>
