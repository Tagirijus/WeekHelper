<li <?= $this->app->checkMenuSelection('WeekHelperController', 'showLevelHoverAsPage') ?>>
    <a href="/weekhelper/dashboard_level/<?= $level ?>"><?php if ($caption != 'level_' . $level): ?>
        <?= $caption ?>
    <?php else: ?>
        Level <?= $level ?>
    <?php endif ?></a>
</li>
