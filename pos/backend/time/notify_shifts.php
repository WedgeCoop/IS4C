<?php
/*
The following script will send out e-mails to employees/members in advance of their shifts.
Set the variables below to the information relevant to your store.
The easiest way to automate this is by setting up a cronjob in UNIX to run this, or a scheduled task in Windows.
It currently does not support a mechanism to have opt-out.  The only way to do that right now is by removing the
user's profile.
*/
include_once "api/swift/swift_required.php";

$store_name = '';
$days_to_remind = 2;
$email = '';
$phone_number = '';
$username = 'email@domain.com';
$password = 'password';

$mysql_connection = mysql_connect("localhost", "root");
if (!$mysql_connection) {
    echo "SQL Connection Failed<br /> " . mysql_error();
    exit;
}

if (!mysql_select_db("time")) {
    echo "Unable to open time schema<br /> " . mysql_error();
    exit;
}


$notification_days = 2;

$sql_query = "
    SELECT *
        FROM is4c_op.employees
            JOIN time.employee_shifts
                USING (emp_no)
            JOIN time.shifts
                USING (shift_id)
        WHERE email IS NOT NULL
            AND DATE_FORMAT(start, '%Y%m%d') = DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL " . $notification_days . " DAY), '%Y%m%d');
    ";

    $shift_results = mysql_query($sql_query);

    $shift_list = array();
    while ($row = mysql_fetch_assoc($shift_results))
    {
        $message = Swift_Message::newInstance()
            ->setSubject('Upcoming shift at ' . $store_name . '.')
            ->setFrom(array(email => $store_name))
            ->setTo(array($row['email']))
            ->setBody('This is a friendly reminder that your shift at ' . $store_name . ' is coming up.  You are scheduled to volunteer from ' . $row["start"] . ' to ' . $row["end"] . '.  If you have any questions please contact us at ' . $phone_number . '.  If you wish to unsubscribe from these e-mails, please contact a keyholder.')
            ->addPart('<p>This is a friendly reminder that your shift at ' . $store_name . ' is coming up.  You are scheduled to volunteer from ' . $row["start"] . ' to ' . $row["end"] . '.  If you have any questions please contact us at ' . $phone_number . '.</p>  If you wish to unsubscribe from these e-mails, please contact a keyholder.', 'text/html');
            // This is configured to use a gmail account.  The smtp transport information should be updated to match you e-mail provider.
            $transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, "ssl")
                ->setUsername($username)
                ->setPassword($password);

            $mailer = Swift_Mailer::newInstance($transport);

            echo $mailer->send($message);
    }
?>
