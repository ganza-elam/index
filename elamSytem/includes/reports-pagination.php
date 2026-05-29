<?php
/**
 * Reports table pagination bar.
 * Expects: $currentPage, $totalPages, $totalRecords, $offset, $perPage,
 *          $filter_intara, $filter_itorero, $filter_month
 */
if (!isset($totalPages) || $totalPages <= 1) {
    return;
}

$rangeStart = $totalRecords === 0 ? 0 : ($offset + 1);
$rangeEnd = min($offset + $perPage, $totalRecords);

/** Build page number list with ellipsis markers. */
$paginationItems = [];
if ($totalPages <= 7) {
    for ($p = 1; $p <= $totalPages; $p++) {
        $paginationItems[] = $p;
    }
} else {
    $paginationItems[] = 1;
    $windowStart = max(2, $currentPage - 1);
    $windowEnd = min($totalPages - 1, $currentPage + 1);

    if ($windowStart > 2) {
        $paginationItems[] = 'ellipsis-start';
    }

    for ($p = $windowStart; $p <= $windowEnd; $p++) {
        $paginationItems[] = $p;
    }

    if ($windowEnd < $totalPages - 1) {
        $paginationItems[] = 'ellipsis-end';
    }

    $paginationItems[] = $totalPages;
}

$paginationReportType = $reportType ?? 'insert_data';
$paginationSearch = $filter_search ?? '';
$paginationSection = $_GET['section'] ?? 'inserted-data-table';

$prevUrl = $currentPage > 1
    ? buildReportsPageUrl($currentPage - 1, $filter_intara, $filter_itorero, $filter_month, $paginationReportType, $paginationSearch, $paginationSection)
    : null;
$nextUrl = $currentPage < $totalPages
    ? buildReportsPageUrl($currentPage + 1, $filter_intara, $filter_itorero, $filter_month, $paginationReportType, $paginationSearch, $paginationSection)
    : null;
$firstUrl = buildReportsPageUrl(1, $filter_intara, $filter_itorero, $filter_month, $paginationReportType, $paginationSearch, $paginationSection);
$lastUrl = buildReportsPageUrl($totalPages, $filter_intara, $filter_itorero, $filter_month, $paginationReportType, $paginationSearch, $paginationSection);
// And inside the loop:
buildReportsPageUrl((int) $item, $filter_intara, $filter_itorero, $filter_month, $paginationReportType, $paginationSearch, $paginationSection)
?>
<nav class="pagination-bar" aria-label="Pagination">
    <div class="pagination-summary">
        <span class="pagination-summary-label">Records</span>
        <span class="pagination-summary-value">
            <strong><?= number_format($rangeStart) ?>–<?= number_format($rangeEnd) ?></strong>
            <span class="pagination-summary-of">of</span>
            <strong><?= number_format($totalRecords) ?></strong>
        </span>
        <span class="pagination-summary-page">Page <?= $currentPage ?> / <?= $totalPages ?></span>
    </div>

    <div class="pagination-controls">
        <div class="pagination-group pagination-group--nav">
            <?php if ($currentPage > 1): ?>
                <a href="<?= htmlspecialchars($firstUrl) ?>" class="pagination-btn pagination-btn--icon" title="First page" aria-label="First page"><?= mi('first_page', 20) ?></a>
                <a href="<?= htmlspecialchars($prevUrl) ?>" class="pagination-btn pagination-btn--text" title="Previous page">
                    <?= mi('chevron_left', 20) ?>
                    <span>Prev</span>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn--icon is-disabled" aria-disabled="true"><?= mi('first_page', 20) ?></span>
                <span class="pagination-btn pagination-btn--text is-disabled" aria-disabled="true">
                    <?= mi('chevron_left', 20) ?>
                    <span>Prev</span>
                </span>
            <?php endif; ?>
        </div>

        <div class="pagination-group pagination-group--pages" role="list">
            <?php foreach ($paginationItems as $item): ?>
                <?php if ($item === 'ellipsis-start' || $item === 'ellipsis-end'): ?>
                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                <?php elseif ((int) $item === $currentPage): ?>
                    <span class="pagination-page is-active" aria-current="page" role="listitem"><?= (int) $item ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(buildReportsPageUrl((int) $item, $filter_intara, $filter_itorero, $filter_month, $paginationReportType, $paginationSearch, $paginationHash)) ?>"
                       class="pagination-page"
                       role="listitem"
                       aria-label="Page <?= (int) $item ?>"><?= (int) $item ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="pagination-group pagination-group--nav">
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= htmlspecialchars($nextUrl) ?>" class="pagination-btn pagination-btn--text" title="Next page">
                    <span>Next</span>
                    <?= mi('chevron_right', 20) ?>
                </a>
                <a href="<?= htmlspecialchars($lastUrl) ?>" class="pagination-btn pagination-btn--icon" title="Last page" aria-label="Last page"><?= mi('last_page', 20) ?></a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn--text is-disabled" aria-disabled="true">
                    <span>Next</span>
                    <?= mi('chevron_right', 20) ?>
                </span>
                <span class="pagination-btn pagination-btn--icon is-disabled" aria-disabled="true"><?= mi('last_page', 20) ?></span>
            <?php endif; ?>
        </div>
    </div>
</nav>
