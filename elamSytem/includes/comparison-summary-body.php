<?php
/**
 * Comparison Summary & PDF
 */
$cmpMonthInt = (int) $filter_month;
$cmpMonthLabel = ($cmpMonthInt >= 1 && $cmpMonthInt <= 12) ? ($monthOptions[$cmpMonthInt] ?? '') : '';
$cmpIntaraName = '';
if ($filter_intara !== '') {
    foreach ($intaraList as $i) {
        if ((string) $i['id'] === (string) $filter_intara) {
            $cmpIntaraName = $i['name'];
            break;
        }
    }
}

$hasIntaraMonth = ($filter_month !== '' && $filter_intara !== '');

$pdfFilename = 'comparison_' . ($cmpIntaraName !== '' ? preg_replace('/[^a-z0-9]+/i', '_', $cmpIntaraName) : 'intara')
    . '_' . ($cmpMonthLabel !== '' ? preg_replace('/[^a-z0-9]+/i', '_', $cmpMonthLabel) : 'month')
    . '.pdf';
?>
<style>
.cr-status { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; }
.cr-status-profit { background: #d4edda; color: #155724; }
.cr-status-loss { background: #f8d7da; color: #721c24; }
.cr-status-equal { background: #fff3cd; color: #856404; }
.comparison-pdf-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 20px; }

#comparison-pdf-root {
    background: #fff;
    padding: 12px 0;
    overflow: visible !important;
    width: 100%;
    max-width: none;
}

#comparison-pdf-root .pdf-block,
#comparison-pdf-root .pdf-capture-block,
#comparison-pdf-root .pdf-section-block,
#comparison-pdf-root .pdf-section-header {
    overflow: visible !important;
    margin-bottom: 20px;
    width: 100%;
    background: #fff;
    padding: 4px 0;
}

#comparison-pdf-root .pdf-table-scroll {
    overflow: visible !important;
    width: 100%;
    max-width: none;
}

#comparison-pdf-root table,
#comparison-pdf-root .pdf-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
    font-size: 11px;
    color: #000;
    background: #fff;
}

#comparison-pdf-root .pdf-table--mapato-a {
    font-size: 8px;
}

#comparison-pdf-root #table-grand-totals + .pdf-table-scroll table,
#comparison-pdf-root .pdf-table--wide {
    font-size: 8px;
}

#comparison-pdf-root table th,
#comparison-pdf-root table td,
#comparison-pdf-root .pdf-table th,
#comparison-pdf-root .pdf-table td {
    border: 1px solid #333;
    padding: 4px 5px;
    text-align: left;
    vertical-align: top;
    word-wrap: break-word;
    overflow: visible;
    color: #000;
    background: #fff;
}

#comparison-pdf-root table thead th,
#comparison-pdf-root .pdf-table thead th {
    background: #e3f2fd !important;
    font-weight: 700;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

#comparison-pdf-root .pdf-tfoot-row td {
    background: #e8f5e9 !important;
    font-weight: 700;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

#comparison-pdf-root .pdf-section-title {
    font-size: 14px;
    margin: 0 0 8px;
    color: #000;
}

#comparison-pdf-root .pdf-meta {
    font-size: 12px;
    color: #333;
    margin: 0 0 10px;
}

.pdf-page-break {
    page-break-before: always;
    break-before: page;
}

.no-data-inline { color: #666; font-style: italic; margin-bottom: 16px; }

@media print {
    .comparison-pdf-actions, .sidebar-nav, .filters { display: none !important; }
    #comparison-pdf-root, #comparison-pdf-root * { overflow: visible !important; }
}
</style>

<div class="nav-page-section" data-nav-section="comparison-summary" id="comparison-summary">
    <h1><?= mi('picture_as_pdf', 28) ?> Comparison &amp; PDF</h1>
    <p style="color:#666;margin-bottom:16px;">
        Hitamo <strong>Intara</strong> na <strong>Ukwezi</strong>, ukande Search.
        Mapato A igaragazwa <strong>ku giti cya Itorero</strong> (nk'uko Export Mapato A ibikora).
    </p>

    <div class="comparison-pdf-actions">
        <button type="button" class="btn-icon" id="btnDownloadComparisonPdf" data-pdf-filename="<?= htmlspecialchars($pdfFilename) ?>" <?= !$hasIntaraMonth ? 'disabled' : '' ?>><?= mi_btn('download', 'Download PDF') ?></button>
        <button type="button" class="btn-icon" onclick="window.print()" <?= !$hasIntaraMonth ? 'disabled' : '' ?>><?= mi_btn('print', 'Print') ?></button>
        <span style="font-size:13px;color:#666;"><?= htmlspecialchars($pdfFilename) ?></span>
        <span id="pdf-export-status" style="font-size:13px;color:#1565c0;width:100%;"></span>
    </div>

    <?php if ($filter_month === '' || $filter_intara === ''): ?>
        <div class="alert" style="background:#fff3cd;padding:14px;border-radius:8px;color:#856404;">
            <?php if ($isGuest && $guestIntaraId): ?>
                Hitamo <strong>Ukwezi</strong> hanyuma Search.
            <?php else: ?>
                Hitamo <strong>Intara</strong> na <strong>Ukwezi</strong> hanyuma Search.
            <?php endif; ?>
        </div>
    <?php else: ?>

    <div id="comparison-pdf-root">

        <?php require __DIR__ . '/comparison-detail-records.php'; ?>

        <div class="pdf-section-block">
        <h3 class="pdf-section-title" id="table-pastor-bank"><?= mi('compare_arrows', 22) ?> Comparison — Pastoro vs Bank</h3>
        <?php if (empty($comparisonRows)): ?>
            <p class="no-data-inline">Nta data.</p>
        <?php else: ?>
        <div class="pdf-table-scroll"><?php require __DIR__ . '/comparison-table-pastor-bank.php'; ?></div>
        <?php endif; ?>
        </div>

        <div class="pdf-section-block">
        <h3 class="pdf-section-title" id="table-bank-insert"><?= mi('compare_arrows', 22) ?> Comparison — Bank vs IBYANYUZE MUMA SUCHE</h3>
        <?php if (empty($comparisonInsertRows)): ?>
            <p class="no-data-inline">Nta data.</p>
        <?php else: ?>
        <div class="pdf-table-scroll"><?php require __DIR__ . '/comparison-table-bank-insert.php'; ?></div>
        <?php endif; ?>
        </div>

        <div class="pdf-section-block">
        <h3 class="pdf-section-title" id="table-grand-totals"><?= mi('compare_arrows', 22) ?> Grand Totals</h3>
        <?php if (empty($grandTotalsRows)): ?>
            <p class="no-data-inline">Nta data.</p>
        <?php else: ?>
        <div class="pdf-table-scroll"><?php require __DIR__ . '/comparison-table-grand-totals.php'; ?></div>
        <?php endif; ?>
        </div>

    </div>

    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="includes/comparison-pdf-export.js"></script>
