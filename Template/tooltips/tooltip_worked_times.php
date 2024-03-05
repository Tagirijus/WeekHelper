<?php
    $table_name = 'Last month';
    $taskTimes = $month_times[-1];
    include(__DIR__ . '/tooltip_worked_times_table.php');
?>

<?php
    $table_name = 'This month';
    $taskTimes = $month_times[0];
    include(__DIR__ . '/tooltip_worked_times_table.php');
?>