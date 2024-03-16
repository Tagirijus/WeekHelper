<!-- KanboardTabs menu -->
<div class="kanboardtab">
    <button class="kanboardtabbutton kanboardbuttonactive" onclick="switchTab(event, 'Months')">Months</button>
    <button class="kanboardtabbutton" onclick="switchTab(event, 'Weeks')">Weeks</button>
</div>

<!-- KanboardTabs content -->
<div id="Months" class="kanboardtabcontent kanboardtabcontentshow">
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
</div>

<div id="Weeks" class="kanboardtabcontent kanboardtabcontenthide">
    WEEKS HERE
</div>