<script type="text/javascript">
    /*$(document).ready(function(){
        var data = new Array();//"Core Selectors Attributes Traversing Manipulation CSS Events Effects Ajax Utilities".split(" ");
        $(".worked_text_field").autocomplete(data);
    });*/
</script>
<?php
include_once "api/api.php";

$shift = new shifts;
$shift_number = 0;
?>
<p> Shifts for <?=substr($_GET['date'], 4, 2) . '/' . substr($_GET['date'], 6, 2) . '/' . substr($_GET['date'], 0, 4)?>: </p>
<table border = '1'>
    <tr>
        <td>Shift #</td>
        <td>Time</td>
        <td>Assigned</td>
    </tr>
<?php
$shift_loop = $shift->shift_list(substr($_GET['date'], 6, 2), substr($_GET['date'], 4, 2), substr($_GET['date'], 0, 4));

if (count($shift_loop) > 0)
{
    foreach($shift_loop as $sh)
    {
        $shift_number++;
    ?>
        <tr>
            <td><?=$shift_number?></td>
            <td><?=date('h:i a', mktime(substr($sh['start'], 11, 2), substr($sh['start'], 14, 2), substr($sh['start'], 17, 2), substr($sh['start'], 5, 2), substr($sh['start'], 8, 2), substr($sh['start'], 0, 4)))?> - <?=date('h:i a', mktime(substr($sh['end'], 11, 2), substr($sh['end'], 14, 2), substr($sh['end'], 17, 2), substr($sh['end'], 5, 2), substr($sh['end'], 8, 2), substr($sh['end'], 0, 4)))?></td>
            <td>
    <?php
        $worker_loop = $shift->shift_workers($sh['shift_id']);
        if (count($worker_loop) > 0)
        {
            foreach($worker_loop as $wo)
            {
            ?>
                <input type="checkbox" <?=$wo['worked']?'checked="checked"':''?> name="worked" id="worked_<?=$wo['emp_no']?>_<?=$sh['shift_id']?>" onclick="shift_worked(<?=$wo['emp_no']?>, <?=$sh['shift_id']?>, $('#worked_<?=$wo['emp_no']?>_<?=$sh['shift_id']?>').attr('checked'));" /> <?=$wo['first_name']?> <?=$wo['last_name']?> <a onclick="remove_employee_from_shift(<?=$wo['emp_no']?>, <?=$sh['shift_id']?>); show_shifts(<?=$_GET['date']?>); return false;" href="#" style="float: right">-</a> <br />
                <!--<input type="text" class="worked_text_field"/>-->
            <?php
            }
        }
    ?>
                <form id="employee_<?=$sh['shift_id']?>">
                    <select>
    <?php
        $employee_loop = $shift->active_employees();
        if (count($employee_loop) > 0)
        {
            foreach($employee_loop as $eo)
            {
            ?>
                        <option value="<?=$eo['emp_no']?>"><?=$eo['first_name']?> <?=$eo['last_name']?></option>
            <?php
            }
        }
    ?>
                    </select>
                    <a onclick="add_employee_to_shift($('#employee_<?=$sh['shift_id']?> select').val(), <?=$sh['shift_id']?>); show_shifts(<?=$_GET['date']?>); return false;" href="#">+</a>
                </form>
            </td>
            <td><input type="submit" value="-" onclick="delete_shift(<?=$sh['shift_id']?>, <?=$_GET['date']?>); show_shifts(<?=$_GET['date']?>); return false;" /></td>
        </tr>
    <?php
    }
}
?>
    <tr>
        <form id="add_shift">
            <td><?=++$shift_number?></td>
            <td><input id="start_time" type="time" placeholder="12:00 am" size="6"/> - <input id="end_time" type="time" placeholder="11:59 pm" size="6"/></td>
            <td></td>
            <td><input type="submit" value="+" onclick="add_shift($('#start_time').val(), $('#end_time').val(), <?=$_GET['date']?>); show_shifts(<?=$_GET['date']?>); return false;" /></td>
        </form>
    <tr>
    </tr>
</table>
<div id='empty'></div>
