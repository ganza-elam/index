<?php
/**
 * Receipt request — outstanding booklets pagination.
 * Expects: $bookletPage, $bookletTotalPages, $bookletTotalRecords, $bookletOffset, $bookletPerPage
 */
if (!isset($bookletTotalPages) || $bookletTotalPages <= 1) {
    return;
}

$rangeStart = $bookletTotalRecords === 0 ? 0 : ($bookletOffset + 1);
$rangeEnd = min($bookletOffset + $bookletPerPage, $bookletTotalRecords);

$paginationItems = [];
if ($bookletTotalPages <= 7) {
    for ($p = 1; $p <= $bookletTotalPages; $p++) {
        $paginationItems[] = $p;
    }
} else {
    $paginationItems[] = 1;
    $windowStart = max(2, $bookletPage - 1);
    $windowEnd = min($bookletTotalPages - 1, $bookletPage + 1);

    if ($windowStart > 2) {
        $paginationItems[] = 'ellipsis-start';
    }

    for ($p = $windowStart; $p <= $windowEnd; $p++) {
        $paginationItems[] = $p;
    }

    if ($windowEnd < $bookletTotalPages - 1) {
        $paginationItems[] = 'ellipsis-end';
    }

    $paginationItems[] = $bookletTotalPages;
}

$prevUrl = $bookletPage > 1 ? buildReceiptRequestPageUrl($bookletPage - 1) : null;
$nextUrl = $bookletPage < $bookletTotalPages ? buildReceiptRequestPageUrl($bookletPage + 1) : null;
$firstUrl = buildReceiptRequestPageUrl(1);
$lastUrl = buildReceiptRequestPageUrl($bookletTotalPages);
?>
<nav class="pagination-bar" aria-label="Outstanding booklets pagination">
    <div class="pagination-summary">
        <span class="pagination-summary-label">Booklets</span>
        <span class="pagination-summary-value">
            <strong><?= (int) $rangeStart ?>–<?= (int) $rangeEnd ?></strong>
            <span class="pagination-summary-of">of</span>
            <strong><?= (int) $bookletTotalRecords ?></strong>
        </span>
        <span class="pagination-summary-page">Page <?= (int) $bookletPage ?> / <?= (int) $bookletTotalPages ?></span>
    </div>

    <div class="pagination-controls">
        <div class="pagination-group pagination-group--nav">
            <?php if ($bookletPage > 1): ?>
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
                <?php elseif ((int) $item === $bookletPage): ?>
                    <span class="pagination-page is-active" aria-current="page" role="listitem"><?= (int) $item ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(buildReceiptRequestPageUrl((int) $item)) ?>"
                       class="pagination-page"
                       role="listitem"><?= (int) $item ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="pagination-group pagination-group--nav">
            <?php if ($bookletPage < $bookletTotalPages): ?>
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
