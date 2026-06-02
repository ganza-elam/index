<?php
/**
 * Left sidebar navigation with section dropdowns.
 */
require_once __DIR__ . '/icons.php';

$navIsGuest = isGuestUser();
$navScript = basename($_SERVER['PHP_SELF'] ?? '');
$navReportType = $_GET['report_type'] ?? '';
$navSection = $_GET['section'] ?? '';
$navIsCorrectReportView = ($navScript === 'reports.php' && $navReportType === 'correct_report');
$navIsComparisonSummaryView = ($navScript === 'reports.php' && $navReportType === 'comparison_summary');
$navIsInsertReportView = ($navScript === 'reports.php' && $navReportType === 'insert_data');
$navIsCorrectEntry = in_array($navScript, ['correct-report.php', 'edit-mapato-pastor.php', 'edit-bank-slip.php'], true);
$navIsReceiptRequest = ($navScript === 'receipt-request.php');
$navIsAdmin = ($navScript === 'admin.php');
$navIsInsertData = ($navScript === 'index.php');
$navIsTransFunds = ($navScript === 'trans-funds.php');
$navTransFundsView = $_GET['view'] ?? 'amaturo';

function navLinkClass($active) {
    return $active ? 'sidebar-link is-active' : 'sidebar-link';
}

function navDropdownOpen($active) {
    return $active ? ' sidebar-dropdown is-open' : ' sidebar-dropdown';
}

function navSubActive($script, $extra = null) {
    global $navScript, $navReportType, $navSection;
    if ($extra === null) {
        return $navScript === $script;
    }
    if (isset($extra['report_type'])) {
        return $navScript === $script && $navReportType === $extra['report_type'];
    }
    if (isset($extra['section'])) {
        return $navScript === $script && $navSection === $extra['section'];
    }
    return false;
}

function renderNavDropdown($label, $iconName, $parentActive, $items) {
    $openClass = $parentActive ? ' is-open' : '';
    $activeClass = $parentActive ? ' is-active' : '';
    ?>
    <div class="sidebar-dropdown<?= $openClass ?>">
        <button type="button" class="sidebar-dropdown-toggle<?= $activeClass ?>" aria-expanded="<?= $parentActive ? 'true' : 'false' ?>">
            <?= mi($iconName, 20) ?>
            <span class="sidebar-dropdown-label"><?= htmlspecialchars($label) ?></span>
            <span class="material-icons sidebar-dropdown-chevron" aria-hidden="true">expand_more</span>
        </button>
        <div class="sidebar-dropdown-menu" role="menu">
            <?php foreach ($items as $item):
                $subActive = !empty($item['active']);
            ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="sidebar-dropdown-item<?= $subActive ? ' is-active' : '' ?>" role="menuitem">
                <?= htmlspecialchars($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>
<div class="app-shell">
<aside class="sidebar-nav" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img src="assets/sda.png" alt="" class="sidebar-logo" width="40" height="40">
        <span>SDA Stewardship</span>
    </div>
    <nav class="sidebar-menu">
        <?php if (!$navIsGuest): ?>
            <a href="index.php" class="<?= navLinkClass($navIsInsertData) ?>"><?= mi_btn('post_add', 'IBYANYUZE MUMA SUCHE') ?></a>

            <?php
            renderNavDropdown('IBYAKIRIWE KURI RAPORT', 'edit_note', $navIsCorrectEntry, [
                ['label' => 'Insert Mapato from Pastor', 'href' => 'correct-report.php?section=pastor#section-pastor', 'active' => navSubActive('correct-report.php', ['section' => 'pastor'])],
                ['label' => 'Take Bank Slip', 'href' => 'correct-report.php?section=bank#section-bank', 'active' => navSubActive('correct-report.php', ['section' => 'bank'])],
            ]);
            renderNavDropdown('RAPORO YIBYAKIRIWE', 'fact_check', $navIsCorrectReportView || $navIsComparisonSummaryView, [
                ['label' => 'Comparison Summary & PDF', 'href' => 'reports.php?report_type=comparison_summary#comparison-summary', 'active' => $navIsComparisonSummaryView],
                ['label' => 'Comparison (Pastoro vs Bank)', 'href' => 'reports.php?report_type=correct_report#comparison-pastor-bank', 'active' => false],
                ['label' => 'Grand Totals (All sources)', 'href' => 'reports.php?report_type=correct_report#comparison-grand-totals', 'active' => false],
                ['label' => 'Bank vs IBYANYUZE MUMA SUCHE', 'href' => 'reports.php?report_type=correct_report#comparison-bank-insert', 'active' => false],
                ['label' => 'Mapato from Pastor', 'href' => 'reports.php?report_type=correct_report#mapato-pastor-table', 'active' => false],
                ['label' => 'Bank Slips', 'href' => 'reports.php?report_type=correct_report#bank-slips-table', 'active' => false],
            ]);
            renderNavDropdown('TRANS-FUNDS', 'swap_horiz', $navIsTransFunds, [
                ['label' => 'Amaturo (Offerings)', 'href' => 'trans-funds.php?view=amaturo', 'active' => $navIsTransFunds && $navTransFundsView === 'amaturo'],
                ['label' => 'Icyacumi (Tithe)', 'href' => 'trans-funds.php?view=icyacumi', 'active' => $navIsTransFunds && $navTransFundsView === 'icyacumi'],
            ]);
            renderNavDropdown('RECEIPT REQUEST', 'receipt_long', $navIsReceiptRequest, [
                ['label' => 'Stock (receipt ranges)', 'href' => 'receipt-request.php#receipt-stock', 'active' => $navIsReceiptRequest],
                ['label' => 'Itorero — booklet limits', 'href' => 'receipt-request.php#itorero-receipt-limits', 'active' => false],
                ['label' => 'Saba booklet (Admin)', 'href' => 'receipt-request.php#admin-request-behalf', 'active' => false],
                ['label' => 'Pending requests', 'href' => 'receipt-request.php#pending-requests', 'active' => false],
                ['label' => 'Pastor yemeje booklet', 'href' => 'receipt-request.php#pastor-acknowledged', 'active' => false],
                ['label' => 'Receipt report (gone / returned)', 'href' => 'receipt-request.php#receipt-report', 'active' => false],
            ]);
            renderNavDropdown('ADMIN PORTAL', 'settings', $navIsAdmin, [
                ['label' => 'Add new User', 'href' => 'admin.php#add-user', 'active' => $navIsAdmin],
                ['label' => 'List of Users', 'href' => 'admin.php#users-list', 'active' => false],
                ['label' => 'Add Intara', 'href' => 'admin.php#add-intara', 'active' => false],
                ['label' => 'Add Itorero', 'href' => 'admin.php#add-itorero', 'active' => false],
                ['label' => 'List of Intara', 'href' => 'admin.php#intara-list', 'active' => false],
                ['label' => 'List of Itorero', 'href' => 'admin.php#itorero-list', 'active' => false],
            ]);
            renderNavDropdown('REPORT', 'assessment', $navIsInsertReportView, [
                ['label' => 'Summary & totals', 'href' => 'reports.php#report-summary', 'active' => false],
                ['label' => 'Admin activity chart', 'href' => 'reports.php#admin-insert-chart', 'active' => false],
                ['label' => 'Inserted data table', 'href' => 'reports.php#inserted-data-table', 'active' => false],
            ]);
            ?>
        <?php else: ?>
            <?php
            renderNavDropdown('REPORT', 'assessment', $navIsInsertReportView, [
                ['label' => 'Summary & totals', 'href' => 'reports.php#report-summary', 'active' => false],
                ['label' => 'Inserted data table', 'href' => 'reports.php#inserted-data-table', 'active' => false],
            ]);
            renderNavDropdown('RAPORO YIBYAKIRIWE', 'fact_check', $navIsCorrectReportView || $navIsComparisonSummaryView, [
                ['label' => 'Comparison Summary & PDF', 'href' => 'reports.php?report_type=comparison_summary#comparison-summary', 'active' => $navIsComparisonSummaryView],
                ['label' => 'Comparison (Pastoro vs Bank)', 'href' => 'reports.php?report_type=correct_report#comparison-pastor-bank', 'active' => false],
                ['label' => 'Grand Totals (All sources)', 'href' => 'reports.php?report_type=correct_report#comparison-grand-totals', 'active' => false],
                ['label' => 'Mapato from Pastor', 'href' => 'reports.php?report_type=correct_report#mapato-pastor-table', 'active' => false],
                ['label' => 'Bank Slips', 'href' => 'reports.php?report_type=correct_report#bank-slips-table', 'active' => false],
            ]);
            renderNavDropdown('TRANS-FUNDS', 'swap_horiz', $navIsTransFunds, [
                ['label' => 'Amaturo (Offerings)', 'href' => 'trans-funds.php?view=amaturo', 'active' => $navIsTransFunds && $navTransFundsView === 'amaturo'],
                ['label' => 'Icyacumi (Tithe)', 'href' => 'trans-funds.php?view=icyacumi', 'active' => $navIsTransFunds && $navTransFundsView === 'icyacumi'],
            ]);
            renderNavDropdown('RECEIPT REQUEST', 'receipt_long', $navIsReceiptRequest, [
                ['label' => 'Request receipt', 'href' => 'receipt-request.php#request-form', 'active' => $navIsReceiptRequest],
                ['label' => 'My requests (raporo)', 'href' => 'receipt-request.php#my-requests', 'active' => false],
                ['label' => 'Receipt report (gone / returned)', 'href' => 'receipt-request.php#receipt-report', 'active' => false],
            ]);
            ?>
        <?php endif; ?>
        <?php if (!$navIsGuest): ?>
            <a href="create-intara.php" class="<?= navLinkClass($navScript === 'create-intara.php' || $navScript === 'create-itorero.php') ?> sidebar-link--green"><?= mi_btn('add_location', 'ADD Intara') ?></a>
        <?php endif; ?>
        <a href="logout.php" class="sidebar-link sidebar-link--logout"><?= mi_btn('logout', 'LOG OUT') ?></a>
    </nav>
</aside>
<main class="app-main">
<script src="includes/nav-menu.js" defer></script>
<?php if (in_array($navScript, ['reports.php', 'receipt-request.php', 'admin.php'], true)): ?>
<script src="includes/page-sections.js" defer></script>
<?php endif; ?>
