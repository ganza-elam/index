<?php
/**
 * Shared navigation — guests see REPORT and LOG OUT only.
 */
require_once __DIR__ . '/icons.php';
$navIsGuest = isGuestUser();
?>
<div class="nav">
    <?php if (!$navIsGuest): ?>
        <a href="index.php"><?= mi_btn('post_add', 'INSERT DATA') ?></a>
        <a href="admin.php"><?= mi_btn('settings', 'ADMIN PORTAL') ?></a>
    <?php endif; ?>
    <a href="reports.php"><?= mi_btn('assessment', 'REPORT') ?></a>
    <?php if (!$navIsGuest): ?>
        <a href="create-intara.php" style="color: #28a745;"><?= mi_btn('add_location', 'ADD Intara') ?></a>
    <?php endif; ?>
    <a href="logout.php" class="logout" style="color: #dc3545;"><?= mi_btn('logout', 'LOG OUT') ?></a>
</div>