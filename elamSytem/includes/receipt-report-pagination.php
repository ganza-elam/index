<?php
/**
 * Receipt gone/returned report pagination.
 * Expects: $receiptPagPage, $receiptPagTotalPages, $receiptPagTotalRecords,
 *          $receiptPagOffset, $receiptPagPerPage, $receiptPagBuildUrl (callable),
 *          $receiptPagLabel (optional)
 */
if (!isset($receiptPagTotalPages) || $receiptPagTotalPages <= 1) {
    return;
}

$rangeStart = $receiptPagTotalRecords === 0 ? 0 : ($receiptPagOffset + 1);
$rangeEnd = min($receiptPagOffset + $receiptPagPerPage, $receiptPagTotalRecords);
$pagLabel = $receiptPagLabel ?? 'Records';

$paginationItems = [];
if ($receiptPagTotalPages <= 7) {
    for ($p = 1; $p <= $receiptPagTotalPages; $p++) {
        $paginationItems[] = $p;
    }
} else {
    $paginationItems[] = 1;
    $windowStart = max(2, $receiptPagPage - 1);
    $windowEnd = min($receiptPagTotalPages - 1, $receiptPagPage + 1);

    if ($windowStart > 2) {
        $paginationItems[] = 'ellipsis-start';
    }

    for ($p = $windowStart; $p <= $windowEnd; $p++) {
        $paginationItems[] = $p;
    }

    if ($windowEnd < $receiptPagTotalPages - 1) {
        $paginationItems[] = 'ellipsis-end';
    }

    $paginationItems[] = $receiptPagTotalPages;
}

$buildUrl = $receiptPagBuildUrl;
$prevUrl = $receiptPagPage > 1 ? $buildUrl($receiptPagPage - 1) : null;
$nextUrl = $receiptPagPage < $receiptPagTotalPages ? $buildUrl($receiptPagPage + 1) : null;
$firstUrl = $buildUrl(1);
$lastUrl = $buildUrl($receiptPagTotalPages);
?>
<nav class="pagination-bar" aria-label="<?= htmlspecialchars($pagLabel) ?> pagination">
    <div class="pagination-summary">
        <span class="pagination-summary-label"><?= htmlspecialchars($pagLabel) ?></span>
        <span class="pagination-summary-value">
            <strong><?= (int) $rangeStart ?>–<?= (int) $rangeEnd ?></strong>
            <span class="pagination-summary-of">of</span>
            <strong><?= (int) $receiptPagTotalRecords ?></strong>
        </span>
        <span class="pagination-summary-page">Page <?= (int) $receiptPagPage ?> / <?= (int) $receiptPagTotalPages ?></span>
    </div>

    <div class="pagination-controls">
        <div class="pagination-group pagination-group--nav">
            <?php if ($receiptPagPage > 1): ?>
                <a href="<?= htmlspecialchars($firstUrl) ?>" class="pagination-btn pagination-btn--icon" title="First page" aria-label="First page"><?= mi('first_page', 20) ?></a>
                <a href="<?= htmlspecialchars($prevUrl) ?>" class="pagination-btn pagination-btn--text" title="Previous page">
                    <?= mi('chevron_left', 20) ?> <span>Prev</span>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn--icon is-disabled" aria-disabled="true"><?= mi('first_page', 20) ?></span>
                <span class="pagination-btn pagination-btn--text is-disabled" aria-disabled="true">
                    <?= mi('chevron_left', 20) ?> <span>Prev</span>
                </span>
            <?php endif; ?>
        </div>

        <div class="pagination-group pagination-group--pages" role="list">
            <?php foreach ($paginationItems as $item): ?>
                <?php if ($item === 'ellipsis-start' || $item === 'ellipsis-end'): ?>
                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                <?php elseif ((int) $item === $receiptPagPage): ?>
                    <span class="pagination-page is-active" aria-current="page" role="listitem"><?= (int) $item ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($buildUrl((int) $item)) ?>"
                       class="pagination-page"
                       role="listitem"><?= (int) $item ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="pagination-group pagination-group--nav">
            <?php if ($receiptPagPage < $receiptPagTotalPages): ?>
                <a href="<?= htmlspecialchars($nextUrl) ?>" class="pagination-btn pagination-btn--text" title="Next page">
                    <span>Next</span> <?= mi('chevron_right', 20) ?>
                </a>
                <a href="<?= htmlspecialchars($lastUrl) ?>" class="pagination-btn pagination-btn--icon" title="Last page" aria-label="Last page"><?= mi('last_page', 20) ?></a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn--text is-disabled" aria-disabled="true">
                    <span>Next</span> <?= mi('chevron_right', 20) ?>
                </span>
                <span class="pagination-btn pagination-btn--icon is-disabled" aria-disabled="true"><?= mi('last_page', 20) ?></span>
            <?php endif; ?>
        </div>
    </div>
</nav>
