<?php
/**
 * Correct Report view on reports.php
 */
$crMeetingTotal = $categoryTotals['meeting'] ?? 0;
?>
<style>
.cr-status { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; }
.cr-status-profit { background: #d4edda; color: #155724; }
.cr-status-loss { background: #f8d7da; color: #721c24; }
.cr-status-equal { background: #fff3cd; color: #856404; }
</style>

<div class="nav-page-section" data-nav-section="correct-report-summary" id="correct-report-summary">
<div class="summary-cards">
    <div class="card">
        <h3>Mapato ya Pastoro</h3>
        <div class="value"><?= count($mapatoPastorList) ?></div>
    </div>
    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <h3>Grand Total (Pastoro)</h3>
        <div class="value"><?= number_format($grandTotal, 0) ?></div>
    </div>
    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <h3>Bank Slips</h3>
        <div class="value"><?= count($bankSlipsList) ?></div>
    </div>
    <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
        <h3>Total Bank Amount</h3>
        <div class="value"><?= number_format($bankSlipsTotal, 0) ?></div>
    </div>
</div>

<h3>Category Totals (Mapato ya Pastoro)</h3>
<?php if ($filter_month === ''): ?>
<div class="alert" style="background:#fff3cd;padding:12px;border-radius:8px;margin-bottom:16px;color:#856404;">
    Hitamo <strong>Ukwezi</strong> hejuru, ukande <strong>Search</strong>, kugira ngo urebe Mapato ya Pastoro n'amafaranga yayo.
</div>
<?php else: ?>
<div class="category-summary">
    <div class="cat-item"><div class="label">Icyacumi</div><div class="value"><?= number_format($categoryTotals['icyacumi'], 0) ?></div></div>
    <div class="cat-item"><div class="label">CM (Meeting)</div><div class="value"><?= number_format($crMeetingTotal, 0) ?></div></div>
                <div class="cat-item"><div class="label">Amaturo (RECU+CFMS)</div><div class="value"><?= number_format($categoryTotals['total_amaturo_pair'] ?? 0, 0) ?></div></div>
                <div class="cat-item"><div class="label">Amaturo ÷2</div><div class="value"><?= number_format($categoryTotals['total_amaturo_half'] ?? 0, 0) ?></div></div>
    <div class="cat-item"><div class="label">Revival</div><div class="value"><?= number_format($categoryTotals['revival'], 0) ?></div></div>
    <div class="cat-item"><div class="label">SS Lesson</div><div class="value"><?= number_format($categoryTotals['ss'], 0) ?></div></div>
    <div class="cat-item"><div class="label">Inyubako</div><div class="value"><?= number_format($categoryTotals['filide'], 0) ?></div></div>
    <div class="cat-item"><div class="label">Umusaruro</div><div class="value"><?= number_format($categoryTotals['umusaruro'], 0) ?></div></div>
    <div class="cat-item"><div class="label">Udutabo twa JA</div><div class="value"><?= number_format($categoryTotals['ituro'], 0) ?></div></div>
    <div class="cat-item"><div class="label">Udutabo twa Mifem</div><div class="value"><?= number_format($categoryTotals['mifem'] ?? 0, 0) ?></div></div>
    <?php foreach ($pastorExtraColumns as $col): ?>
    <div class="cat-item"><div class="label"><?= htmlspecialchars($col['label']) ?></div><div class="value"><?= number_format($pastorExtraTotals[$col['slug']] ?? 0, 0) ?></div></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="nav-page-section" data-nav-section="comparison-pastor-bank" id="comparison-pastor-bank">
<h3><?= mi('compare_arrows', 22) ?> Comparison: Mapato ya Pastoro vs Bank Slip</h3>
<p style="color:#666;margin-bottom:12px;">Hitamo <strong>Intara</strong> na <strong>Ukwezi</strong> hejuru, ukande <strong>Search</strong>. Surplus = amafaranga muri banki ararenze mapato ya pastoro. Deficit = mapato ya pastoro ararenze banki.</p>

<?php
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
$cmpSingleRow = null;
if ($filter_intara !== '' && $filter_month !== '' && !empty($comparisonRows)) {
    $cmpSingleRow = $comparisonRows[0];
}
?>

<?php if ($filter_month === ''): ?>
    <div class="no-data"><p>Hitamo <strong>Ukwezi</strong> kugira ngo urebe comparison.</p></div>
<?php elseif ($filter_intara === ''): ?>
    <div class="alert" style="background:#fff3cd;padding:12px;border-radius:8px;margin-bottom:16px;color:#856404;">
        Hitamo <strong>Intara</strong> kugira ngo urebe comparison y'iyo Intara, cyangwa ureke ubusa urebe Intara zose zifite data.
    </div>
<?php endif; ?>

<?php if ($cmpSingleRow): ?>
    <div class="cr-compare-highlight" style="background:linear-gradient(135deg,#e3f2fd,#f3e5f5);padding:20px;border-radius:10px;margin-bottom:20px;text-align:center;">
        <h4 style="margin:0 0 12px;"><?= htmlspecialchars($cmpIntaraName) ?> — <?= htmlspecialchars($cmpMonthLabel) ?></h4>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:24px;">
            <div><small>Mapato ya Pastoro</small><div style="font-size:1.5rem;font-weight:700;"><?= number_format($cmpSingleRow['pastor_total'], 0) ?></div></div>
            <div><small>Bank Slip Total</small><div style="font-size:1.5rem;font-weight:700;"><?= number_format($cmpSingleRow['bank_total'], 0) ?></div></div>
            <div><small>Difference</small><div style="font-size:1.5rem;font-weight:700;"><?= number_format($cmpSingleRow['difference'], 0) ?></div></div>
            <div><small>Status</small><div style="margin-top:4px;"><span class="cr-status cr-status-<?= $cmpSingleRow['status'] ?>"><?= htmlspecialchars($cmpSingleRow['status_label']) ?></span></div></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($filter_month !== '' && empty($comparisonRows)): ?>
    <div class="no-data"><p>Nta data y'iyi Intara/Ukwezi — ongeraho mapato ya pastoro cyangwa bank slip.</p></div>
<?php elseif ($filter_month !== '' && !empty($comparisonRows)): ?>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Intara</th>
                <th>Ukwezi</th>
                <th>Total Pastoro</th>
                <th>Total Bank</th>
                <th>Difference</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $cmpPastor = 0;
            $cmpBank = 0;
            $mk = (int) $filter_month;
            $monthLabel = $monthOptions[$mk] ?? '-';
            foreach ($comparisonRows as $row):
                $cmpPastor += $row['pastor_total'];
                $cmpBank += $row['bank_total'];
                $statusClass = 'cr-status-' . $row['status'];
            ?>
            <tr>
                <td><?= htmlspecialchars($row['intara_name']) ?></td>
                <td><?= htmlspecialchars($monthLabel) ?></td>
                <td><?= number_format($row['pastor_total'], 0) ?></td>
                <td><?= number_format($row['bank_total'], 0) ?></td>
                <td><?= number_format($row['difference'], 0) ?></td>
                <td><span class="cr-status <?= $statusClass ?>"><?= htmlspecialchars($row['status_label']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e3f2fd; font-weight: bold;">
                <td colspan="2">TOTAL</td>
                <td><?= number_format($cmpPastor, 0) ?></td>
                <td><?= number_format($cmpBank, 0) ?></td>
                <td><?= number_format($cmpBank - $cmpPastor, 0) ?></td>
                <td><?php
                    $d = $cmpBank - $cmpPastor;
                    if (abs($d) < 0.01) echo '<span class="cr-status cr-status-equal">Equal</span>';
                    elseif ($d > 0) echo '<span class="cr-status cr-status-profit">Surplus</span>';
                    else echo '<span class="cr-status cr-status-loss">Deficit</span>';
                ?></td>
            </tr>
        </tfoot>
    </table>
    </div>
<?php endif; ?>
</div>

<div class="nav-page-section" data-nav-section="comparison-grand-totals" id="comparison-grand-totals">
<h3 style="margin-top:0;"><?= mi('compare_arrows', 22) ?> Grand Totals: Pastoro, IBYANYUZE MUMA SUCHE &amp; Bank Slip</h3>
<p style="color:#666;margin-bottom:12px;">Gereranya grand total zose: <strong>Mapato ya Pastoro</strong> (IBYAKIRIWE KURI RAPORT), <strong>IBYANYUZE MUMA SUCHE</strong> (raporo yinjijwe), na <strong>Bank Slip</strong>. Surplus/Deficit/Equal = ukuze ugereranya (Bank − indi source).</p>

<?php
$cmpGrandSingle = null;
if ($filter_intara !== '' && $filter_month !== '' && !empty($grandTotalsRows)) {
    $cmpGrandSingle = $grandTotalsRows[0];
}
?>

<?php if ($filter_month === ''): ?>
    <div class="no-data"><p>Hitamo <strong>Ukwezi</strong> kugira ngo urebe grand totals comparison.</p></div>
<?php elseif ($cmpGrandSingle): ?>
    <div class="cr-compare-highlight" style="background:linear-gradient(135deg,#fff8e1,#e8f5e9);padding:20px;border-radius:10px;margin-bottom:20px;text-align:center;">
        <h4 style="margin:0 0 12px;"><?= htmlspecialchars($cmpIntaraName) ?> — <?= htmlspecialchars($cmpMonthLabel) ?></h4>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:20px;">
            <div><small>Mapato Pastoro</small><div style="font-size:1.35rem;font-weight:700;"><?= number_format($cmpGrandSingle['pastor_total'], 0) ?></div></div>
            <div><small>IBYANYUZE MUMA SUCHE</small><div style="font-size:1.35rem;font-weight:700;"><?= number_format($cmpGrandSingle['insert_total'], 0) ?></div></div>
            <div><small>Bank Slip</small><div style="font-size:1.35rem;font-weight:700;"><?= number_format($cmpGrandSingle['bank_total'], 0) ?></div></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($filter_month !== '' && empty($grandTotalsRows)): ?>
    <div class="no-data"><p>Nta data y'iyi Intara/Ukwezi — ongeraho mapato ya pastoro, IBYANYUZE MUMA SUCHE cyangwa bank slip.</p></div>
<?php elseif ($filter_month !== '' && !empty($grandTotalsRows)): ?>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Intara</th>
                <th>Ukwezi</th>
                <th>Mapato Pastoro</th>
                <th>IBYANYUZE MUMA SUCHE</th>
                <th>Bank Slip</th>
                <th>Bank − Pastoro</th>
                <th>Status</th>
                <th>Bank − INSERT</th>
                <th>Status</th>
                <th>Pastoro − INSERT</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $gtPastor = 0;
            $gtInsert = 0;
            $gtBank = 0;
            $mkGt = (int) $filter_month;
            $monthLabelGt = $monthOptions[$mkGt] ?? '-';
            foreach ($grandTotalsRows as $row):
                $gtPastor += $row['pastor_total'];
                $gtInsert += $row['insert_total'];
                $gtBank += $row['bank_total'];
            ?>
            <tr>
                <td><?= htmlspecialchars($row['intara_name']) ?></td>
                <td><?= htmlspecialchars($monthLabelGt) ?></td>
                <td><?= number_format($row['pastor_total'], 0) ?></td>
                <td><?= number_format($row['insert_total'], 0) ?></td>
                <td><?= number_format($row['bank_total'], 0) ?></td>
                <td><?= number_format($row['diff_bank_pastor'], 0) ?></td>
                <td><span class="cr-status cr-status-<?= $row['status_bank_pastor'] ?>"><?= htmlspecialchars($row['status_label_bank_pastor']) ?></span></td>
                <td><?= number_format($row['diff_bank_insert'], 0) ?></td>
                <td><span class="cr-status cr-status-<?= $row['status_bank_insert'] ?>"><?= htmlspecialchars($row['status_label_bank_insert']) ?></span></td>
                <td><?= number_format($row['diff_pastor_insert'], 0) ?></td>
                <td><span class="cr-status cr-status-<?= $row['status_pastor_insert'] ?>"><?= htmlspecialchars($row['status_label_pastor_insert']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #fff8e1; font-weight: bold;">
                <td colspan="2">TOTAL</td>
                <td><?= number_format($gtPastor, 0) ?></td>
                <td><?= number_format($gtInsert, 0) ?></td>
                <td><?= number_format($gtBank, 0) ?></td>
                <td><?= number_format($gtBank - $gtPastor, 0) ?></td>
                <td><?php
                    $d = $gtBank - $gtPastor;
                    if (abs($d) < 0.01) echo '<span class="cr-status cr-status-equal">Equal</span>';
                    elseif ($d > 0) echo '<span class="cr-status cr-status-profit">Surplus</span>';
                    else echo '<span class="cr-status cr-status-loss">Deficit</span>';
                ?></td>
                <td><?= number_format($gtBank - $gtInsert, 0) ?></td>
                <td><?php
                    $d = $gtBank - $gtInsert;
                    if (abs($d) < 0.01) echo '<span class="cr-status cr-status-equal">Equal</span>';
                    elseif ($d > 0) echo '<span class="cr-status cr-status-profit">Surplus</span>';
                    else echo '<span class="cr-status cr-status-loss">Deficit</span>';
                ?></td>
                <td><?= number_format($gtPastor - $gtInsert, 0) ?></td>
                <td><?php
                    $d = $gtPastor - $gtInsert;
                    if (abs($d) < 0.01) echo '<span class="cr-status cr-status-equal">Equal</span>';
                    elseif ($d > 0) echo '<span class="cr-status cr-status-profit">Surplus</span>';
                    else echo '<span class="cr-status cr-status-loss">Deficit</span>';
                ?></td>
            </tr>
        </tfoot>
    </table>
    </div>
<?php endif; ?>
</div>

<div class="nav-page-section" data-nav-section="comparison-bank-insert" id="comparison-bank-insert">
<h3><?= mi('compare_arrows', 22) ?> Igereranya ku Itorero: IBYAKIRIWE KURI RAPORT vs IBYANYUZE MUMA SUCHE</h3>
<p style="color:#666;margin-bottom:12px;">
    Iyi table igaragaza ku rwego rw'Itorero: Icyacumi (RECU + CFMS) na Amaturo (RECU + CFMS)
    hagati ya <strong>IBYAKIRIWE KURI RAPORT</strong> na <strong>IBYANYUZE MUMA SUCHE</strong>.
    Ku <strong>Amaturo</strong>: RECU, CFMS na Total (RECU + CFMS) byerekana amafaranga yose; igice cya <strong>÷2</strong> ni Total ÷ 2 ku mpande zombi.
    Amaturo Difference = (IBYANYUZE MUMA SUCHE ÷2 − IBYAKIRIWE KURI RAPORT ÷2).
</p>
<?php if ($filter_month === ''): ?>
    <div class="no-data"><p>Hitamo <strong>Ukwezi</strong> kugira ngo ubone igereranya ku Itorero.</p></div>
<?php elseif (empty($itoreroComparisonRows)): ?>
    <div class="no-data"><p>Nta data ihari kuri ayo mafilter.</p></div>
<?php else: ?>
    <div style="margin-bottom:10px;">
        <button type="button" class="btn-icon" onclick="downloadTableToExcel('itorero-offerings-comparison-table', 'igereranya_itorero_ibyakiriwe_n_ibyanyuze')">
            <?= mi_btn('download', 'Download Igereranya ku Itorero') ?>
        </button>
    </div>
    <div class="table-wrap">
    <table id="itorero-offerings-comparison-table">
        <thead>
            <tr>
                <th rowspan="2">Intara</th>
                <th rowspan="2">Itorero</th>
                <th colspan="3">IBYAKIRIWE KURI RAPORT — Icyacumi</th>
                <th colspan="4">IBYAKIRIWE KURI RAPORT — Amaturo</th>
                <th colspan="3">IBYANYUZE MUMA SUCHE — Icyacumi</th>
                <th colspan="4">IBYANYUZE MUMA SUCHE — Amaturo</th>
                <th colspan="2">Icyacumi Diff</th>
                <th colspan="2">Amaturo Diff (÷2)</th>
            </tr>
            <tr>
                <th>RECU</th><th>CFMS</th><th>Total</th>
                <th>RECU</th><th>CFMS</th><th>Total</th><th>÷2</th>
                <th>RECU</th><th>CFMS</th><th>Total</th>
                <th>RECU</th><th>CFMS</th><th>Total</th><th>÷2</th>
                <th>Difference</th><th>Status</th>
                <th>Difference</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tot = [
                'p_ir' => 0, 'p_ic' => 0, 'p_it' => 0,
                'p_ar' => 0, 'p_ac' => 0, 'p_at' => 0, 'p_ah' => 0,
                'm_ir' => 0, 'm_ic' => 0, 'm_it' => 0,
                'm_ar' => 0, 'm_ac' => 0, 'm_at' => 0, 'm_ah' => 0,
                'd_i' => 0, 'd_a' => 0,
            ];
            foreach ($itoreroComparisonRows as $row):
                $tot['p_ir'] += $row['pastor_icyacumi_recu']; $tot['p_ic'] += $row['pastor_icyacumi_cfms']; $tot['p_it'] += $row['pastor_icyacumi_total'];
                $tot['p_ar'] += $row['pastor_amaturo_recu']; $tot['p_ac'] += $row['pastor_amaturo_cfms']; $tot['p_at'] += $row['pastor_amaturo_total'];
                $tot['p_ah'] += $row['pastor_amaturo_half'];
                $tot['m_ir'] += $row['insert_icyacumi_recu']; $tot['m_ic'] += $row['insert_icyacumi_cfms']; $tot['m_it'] += $row['insert_icyacumi_total'];
                $tot['m_ar'] += $row['insert_amaturo_recu']; $tot['m_ac'] += $row['insert_amaturo_cfms']; $tot['m_at'] += $row['insert_amaturo_total'];
                $tot['m_ah'] += $row['insert_amaturo_half'];
                $tot['d_i'] += $row['diff_icyacumi']; $tot['d_a'] += $row['diff_amaturo'];
            ?>
            <tr>
                <td><?= htmlspecialchars($row['intara_name']) ?></td>
                <td><?= htmlspecialchars($row['itorero_name']) ?></td>
                <td><?= number_format($row['pastor_icyacumi_recu'], 0) ?></td>
                <td><?= number_format($row['pastor_icyacumi_cfms'], 0) ?></td>
                <td><strong><?= number_format($row['pastor_icyacumi_total'], 0) ?></strong></td>
                <td><?= number_format($row['pastor_amaturo_recu'], 0) ?></td>
                <td><?= number_format($row['pastor_amaturo_cfms'], 0) ?></td>
                <td><strong><?= number_format($row['pastor_amaturo_total'], 0) ?></strong></td>
                <td><strong><?= number_format($row['pastor_amaturo_half'], 0) ?></strong></td>
                <td><?= number_format($row['insert_icyacumi_recu'], 0) ?></td>
                <td><?= number_format($row['insert_icyacumi_cfms'], 0) ?></td>
                <td><strong><?= number_format($row['insert_icyacumi_total'], 0) ?></strong></td>
                <td><?= number_format($row['insert_amaturo_recu'], 0) ?></td>
                <td><?= number_format($row['insert_amaturo_cfms'], 0) ?></td>
                <td><strong><?= number_format($row['insert_amaturo_total'], 0) ?></strong></td>
                <td><strong><?= number_format($row['insert_amaturo_half'], 0) ?></strong></td>
                <td><?= number_format($row['diff_icyacumi'], 0) ?></td>
                <td><span class="cr-status cr-status-<?= $row['status_icyacumi'] ?>"><?= htmlspecialchars($row['status_icyacumi_label']) ?></span></td>
                <td><?= number_format($row['diff_amaturo'], 0) ?></td>
                <td><span class="cr-status cr-status-<?= $row['status_amaturo'] ?>"><?= htmlspecialchars($row['status_amaturo_label']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#e8f5e9;font-weight:bold;">
                <td colspan="2">TOTAL</td>
                <td><?= number_format($tot['p_ir'], 0) ?></td><td><?= number_format($tot['p_ic'], 0) ?></td><td><?= number_format($tot['p_it'], 0) ?></td>
                <td><?= number_format($tot['p_ar'], 0) ?></td><td><?= number_format($tot['p_ac'], 0) ?></td><td><?= number_format($tot['p_at'], 0) ?></td><td><?= number_format($tot['p_ah'], 0) ?></td>
                <td><?= number_format($tot['m_ir'], 0) ?></td><td><?= number_format($tot['m_ic'], 0) ?></td><td><?= number_format($tot['m_it'], 0) ?></td>
                <td><?= number_format($tot['m_ar'], 0) ?></td><td><?= number_format($tot['m_ac'], 0) ?></td><td><?= number_format($tot['m_at'], 0) ?></td><td><?= number_format($tot['m_ah'], 0) ?></td>
                <td><?= number_format($tot['d_i'], 0) ?></td>
                <td><?php [$sI,$lI] = correctReportStatusFromDiff($tot['d_i']); ?><span class="cr-status cr-status-<?= $sI ?>"><?= $lI ?></span></td>
                <td><?= number_format($tot['m_ah'] - $tot['p_ah'], 0) ?></td>
                <td><?php [$sA,$lA] = correctReportStatusFromDiff($tot['m_ah'] - $tot['p_ah']); ?><span class="cr-status cr-status-<?= $sA ?>"><?= $lA ?></span></td>
            </tr>
        </tfoot>
    </table>
    </div>
<?php endif; ?>
</div>

<div class="nav-page-section" data-nav-section="mapato-pastor-table" id="mapato-pastor-table">
<h3><?= mi('table_chart', 22) ?> Mapato from the Pastor</h3>
<?php if ($filter_month === ''): ?>
    <div class="no-data">
        <p><?= mi('event', 32) ?> Hitamo <strong>Ukwezi</strong> hejuru, ukande <strong>Search</strong>, kugira ngo urebe Mapato ya Pastoro.</p>
    </div>
<?php elseif (empty($mapatoPastorList)): ?>
    <div class="no-data">
        <p><?= mi('inbox', 32) ?> Nta mapato ya pastoro ihari</p>
        <p><a href="correct-report.php?section=pastor">Ongeraho mapato ya pastoro</a></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Intara</th>
                <th>Itorero</th>
                <th>Ukwezi</th>
                <th>Icyacumi</th>
                <th>Icyacumi CFMS</th>
                <th>CM (Meeting)</th>
                <th>Amaturo</th>
                <th>Amaturo CFMS</th>
                <th>Amaturo (RECU+CFMS)</th>
                <th>Amaturo ÷2</th>
                <th>Revival</th>
                <th>SS Lesson</th>
                <th>Inyubako</th>
                <th>Umusaruro</th>
                <th>Udutabo twa JA</th>
                <th>Udutabo twa Mifem</th>
                <?php foreach ($pastorExtraColumns as $col): ?>
                <th><?= htmlspecialchars($col['label']) ?></th>
                <?php endforeach; ?>
                <th>Total</th>
                <th>Itariki</th>
                <?php if (!$isGuest): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagedPastorList as $record):
                $meetingDisplay = mapatoPastorMeeting($record);
            ?>
            <tr>
                <td><?= htmlspecialchars($record['intara_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($record['itorero_name'] ?? '-') ?></td>
                <td><?php
                    $mk = (int) ($record['month'] ?? 0);
                    echo $mk >= 1 && $mk <= 12 ? htmlspecialchars($monthOptions[$mk]) : '-';
                ?></td>
                <td><?= htmlspecialchars($record['icyacumi'] ?? '0') ?></td>
                <td><?= htmlspecialchars($record['icyacumi_cya_cms'] ?? '0') ?></td>
                <td><?= htmlspecialchars($meetingDisplay ?: '0') ?></td>
                <td><?= htmlspecialchars($record['amaturo'] ?? '0') ?></td>
                <td><?= htmlspecialchars($record['amaturo_bya_cms'] ?? '0') ?></td>
                <?php
                    $amaRecu = extractSum($record['amaturo'] ?? '0');
                    $amaCfms = extractSum($record['amaturo_bya_cms'] ?? '0');
                    $amaPair = $amaRecu + $amaCfms;
                    $amaHalf = $amaPair / 2;
                ?>
                <td><strong><?= number_format($amaPair, 0) ?></strong></td>
                <td><strong><?= number_format($amaHalf, 0) ?></strong></td>
                <td><?= htmlspecialchars($record['revival'] ?? '0') ?></td>
                <td><?= htmlspecialchars($record['ss'] ?? '0') ?></td>
                <td><?= htmlspecialchars($record['filide'] ?? '0') ?></td>
                <td><?= htmlspecialchars($record['umusaruro'] ?? '0') ?></td>
                <td><?= htmlspecialchars($record['ituro'] ?? '0') ?></td>
                <td><?= htmlspecialchars($record['mifem'] ?? '0') ?></td>
                <?php foreach ($pastorExtraColumns as $col): ?>
                <td><?= mapatoPastorExtraFieldDisplay($record, $col['slug']) ?></td>
                <?php endforeach; ?>
                <td><strong><?= number_format($record['total'], 0) ?></strong></td>
                <td class="date"><?php
                    $tz = new DateTimeZone('Africa/Kigali');
                    $dt = new DateTime($record['created_at']);
                    $dt->setTimezone($tz);
                    echo $dt->format('d/m/Y H:i');
                ?></td>
                <?php if (!$isGuest): ?>
                <td>
                    <a href="edit-mapato-pastor.php?id=<?= (int) $record['id'] ?>" class="btn-icon" style="margin-right:8px;"><?= mi_btn('edit', 'Edit', 16) ?></a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Urashaka gusiba iyi record?')">
                        <input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>">
                        <button type="submit" name="delete_pastor_record" class="delete btn-icon"><?= mi_btn('delete', 'Delete', 16) ?></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #ffecb3; color:#000; font-weight: 900;">
                <td colspan="3">TOTAL</td>
                <td><?= number_format($categoryTotals['icyacumi'], 0) ?></td>
                <td><?= number_format($categoryTotals['icyacumi_cya_cms'] ?? 0, 0) ?></td>
                <td><?= number_format($crMeetingTotal, 0) ?></td>
                <td><?= number_format($categoryTotals['amaturo'], 0) ?></td>
                <td><?= number_format($categoryTotals['amaturo_bya_cms'] ?? 0, 0) ?></td>
                <td><?= number_format($categoryTotals['total_amaturo_pair'] ?? 0, 0) ?></td>
                <td><?= number_format($categoryTotals['total_amaturo_half'] ?? 0, 0) ?></td>
                <td><?= number_format($categoryTotals['revival'], 0) ?></td>
                <td><?= number_format($categoryTotals['ss'], 0) ?></td>
                <td><?= number_format($categoryTotals['filide'], 0) ?></td>
                <td><?= number_format($categoryTotals['umusaruro'], 0) ?></td>
                <td><?= number_format($categoryTotals['ituro'], 0) ?></td>
                <td><?= number_format($categoryTotals['mifem'] ?? 0, 0) ?></td>
                <?php foreach ($pastorExtraColumns as $col): ?>
                <td><?= number_format($pastorExtraTotals[$col['slug']] ?? 0, 0) ?></td>
                <?php endforeach; ?>
                <td><?= number_format($grandTotal, 0) ?></td>
                <td></td>
                <?php if (!$isGuest): ?><td></td><?php endif; ?>
            </tr>
        </tfoot>
    </table>
    </div>
    <?php require __DIR__ . '/reports-pagination.php'; ?>
<?php endif; ?>
</div>

<div class="nav-page-section" data-nav-section="bank-slips-table" id="bank-slips-table">
<h3><?= mi('account_balance', 22) ?> Bank Slips</h3>
<?php if (empty($bankSlipsList)): ?>
    <div class="no-data">
        <p>Nta bank slip ihari</p>
        <p><a href="correct-report.php?section=bank">Ongeraho bank slip</a></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Intara</th>
                <th>Ukwezi</th>
                <th>Slip Number</th>
                <th>Bank Name</th>
                <th>Amount</th>
                <th>Itariki</th>
                <?php if (!$isGuest): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bankSlipsList as $slip): ?>
            <tr>
                <td><?= htmlspecialchars($slip['intara_name'] ?? '-') ?></td>
                <td><?php
                    $mk = (int) ($slip['month'] ?? 0);
                    echo $mk >= 1 && $mk <= 12 ? htmlspecialchars($monthOptions[$mk]) : '-';
                ?></td>
                <td><?= htmlspecialchars($slip['slip_number']) ?></td>
                <td><?= htmlspecialchars($slip['bank_name']) ?></td>
                <td><strong><?= number_format($slip['amount'], 0) ?></strong></td>
                <td class="date"><?php
                    $tz = new DateTimeZone('Africa/Kigali');
                    $dt = new DateTime($slip['created_at']);
                    $dt->setTimezone($tz);
                    echo $dt->format('d/m/Y H:i');
                ?></td>
                <?php if (!$isGuest): ?>
                <td>
                    <a href="edit-bank-slip.php?id=<?= (int) $slip['id'] ?>" class="btn-icon" style="margin-right:8px;"><?= mi_btn('edit', 'Edit', 16) ?></a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Urashaka gusiba iyi bank slip?')">
                        <input type="hidden" name="record_id" value="<?= (int) $slip['id'] ?>">
                        <button type="submit" name="delete_bank_slip" class="delete btn-icon"><?= mi_btn('delete', 'Delete', 16) ?></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e8f5e9; font-weight: bold;">
                <td colspan="4">TOTAL</td>
                <td><?= number_format($bankSlipsTotal, 0) ?></td>
                <td></td>
                <?php if (!$isGuest): ?><td></td><?php endif; ?>
            </tr>
        </tfoot>
    </table>
    </div>
<?php endif; ?>
</div>
