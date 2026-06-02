<?php
/**
 * Record listings for comparison PDF (selected Intara + month).
 * Mapato A grouped by Itorero (same as export_mapato_a). Single tables for compact PDF export.
 */
$mapatoAGrouped = aggregateImibareByItorero($imibareList, $cmpIntaraName);
$mapatoARows = $mapatoAGrouped['rows'];
$mapatoAOverall = $mapatoAGrouped['overall'];
$mapatoAGrandTotal = $mapatoAGrouped['grand_total'];
?>

<div class="pdf-section-header">
    <div class="brand-header" style="margin-bottom:8px;">
        <img class="brand-logo" src="assets/sda.png" alt="" width="48" height="48">
        <div class="brand-text">
            <h2 style="margin:0;font-size:1.1rem;color:#000;">Seventh Day Adventist Church</h2>
            <small style="color:#333;"><?= htmlspecialchars($cmpIntaraName) ?> — <?= htmlspecialchars($cmpMonthLabel) ?></small>
        </div>
    </div>
</div>

<?php if (empty($mapatoARows)): ?>
<div class="pdf-section-block">
    <h3 class="pdf-section-title"><?= mi('table_chart', 22) ?> MAPATO A (IBYANYUZE MUMA SUCHE) — by Itorero</h3>
    <p class="no-data-inline">Nta data.</p>
</div>
<?php else:
    $ovIcyacumiPair = $mapatoAOverall['icyacumi'] + $mapatoAOverall['icyacumi_cya_cms'];
    $ovAmaturoPair = $mapatoAOverall['amaturo'] + $mapatoAOverall['amaturo_bya_cms'];
?>
<div class="pdf-section-block">
    <h3 class="pdf-section-title"><?= mi('table_chart', 22) ?> MAPATO A (IBYANYUZE MUMA SUCHE) — by Itorero</h3>
    <p class="pdf-meta">Intara: <strong><?= htmlspecialchars($cmpIntaraName) ?></strong> — <?= htmlspecialchars($cmpMonthLabel) ?></p>
    <div class="pdf-table-scroll">
    <table class="pdf-table pdf-table--mapato-a">
        <thead>
            <tr>
                <th>Intara</th>
                <th>Itorero</th>
                <th>Rec.</th>
                <th>Icyacumi</th>
                <th>Ibindi</th>
                <th>Icy. CFMS</th>
                <th>Tot. Icy.</th>
                <th>Amaturo</th>
                <th>Am. CFMS</th>
                <th>Tot. Am.</th>
                <th>Umusaruro</th>
                <th>Ituro</th>
                <th>Filide</th>
                <th>SS</th>
                <th>Ubus.</th>
                <th>Mifem</th>
                <th>JA</th>
                <th>Grand Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mapatoARows as $row):
                $totalIcyacumiPair = $row['icyacumi'] + $row['icyacumi_cya_cms'];
                $totalAmaturoPair = $row['amaturo'] + $row['amaturo_bya_cms'];
            ?>
            <tr>
                <td><?= htmlspecialchars($row['intara_name']) ?></td>
                <td><strong><?= htmlspecialchars($row['itorero_name']) ?></strong></td>
                <td><?= (int) $row['record_count'] ?></td>
                <td><?= number_format($row['icyacumi'], 0) ?></td>
                <td><?= number_format($row['ibindi'], 0) ?></td>
                <td><?= number_format($row['icyacumi_cya_cms'], 0) ?></td>
                <td><?= number_format($totalIcyacumiPair, 0) ?></td>
                <td><?= number_format($row['amaturo'], 0) ?></td>
                <td><?= number_format($row['amaturo_bya_cms'], 0) ?></td>
                <td><?= number_format($totalAmaturoPair, 0) ?></td>
                <td><?= number_format($row['umusaruro'], 0) ?></td>
                <td><?= number_format($row['ituro'], 0) ?></td>
                <td><?= number_format($row['filide'], 0) ?></td>
                <td><?= number_format($row['ss'], 0) ?></td>
                <td><?= number_format($row['ubusonga'], 0) ?></td>
                <td><?= number_format($row['mifem'], 0) ?></td>
                <td><?= number_format($row['ja'], 0) ?></td>
                <td><strong><?= number_format($row['grand_total'], 0) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="pdf-tfoot-row">
                <td colspan="2">TOTAL</td>
                <td><?= (int) $mapatoAOverall['record_count'] ?></td>
                <td><?= number_format($mapatoAOverall['icyacumi'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['ibindi'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['icyacumi_cya_cms'], 0) ?></td>
                <td><?= number_format($ovIcyacumiPair, 0) ?></td>
                <td><?= number_format($mapatoAOverall['amaturo'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['amaturo_bya_cms'], 0) ?></td>
                <td><?= number_format($ovAmaturoPair, 0) ?></td>
                <td><?= number_format($mapatoAOverall['umusaruro'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['ituro'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['filide'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['ss'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['ubusonga'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['mifem'], 0) ?></td>
                <td><?= number_format($mapatoAOverall['ja'], 0) ?></td>
                <td><strong><?= number_format($mapatoAGrandTotal, 0) ?></strong></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if (empty($mapatoPastorList)): ?>
<div class="pdf-section-block">
    <h3 class="pdf-section-title"><?= mi('person', 22) ?> Mapato ya Pastoro</h3>
    <p class="no-data-inline">Nta mapato ya pastoro.</p>
</div>
<?php else:
    $pTotalAll = array_sum(array_map(fn($r) => (float) $r['total'], $mapatoPastorList));
?>
<div class="pdf-section-block">
    <h3 class="pdf-section-title"><?= mi('person', 22) ?> Mapato ya Pastoro — all records</h3>
    <div class="pdf-table-scroll">
    <table class="pdf-table">
        <thead>
            <tr>
                <th>Itorero</th>
                <th>Icyacumi</th>
                <th>CM</th>
                <th>Amaturo</th>
                <th>Revival</th>
                <th>SS</th>
                <th>Inyubako</th>
                <th>Umusaruro</th>
                <th>Udutabo twa JA</th>
                <th>Udutabo twa Mifem</th>
                <?php foreach ($pastorExtraColumns as $col): ?>
                <th><?= htmlspecialchars($col['label']) ?></th>
                <?php endforeach; ?>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mapatoPastorList as $record):
                $meetingDisplay = mapatoPastorMeeting($record);
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($record['itorero_name'] ?? '—') ?></strong></td>
                <td><?= htmlspecialchars($record['icyacumi'] ?? '0') ?></td>
                <td><?= htmlspecialchars($meetingDisplay ?: '0') ?></td>
                <td><?= htmlspecialchars($record['amaturo'] ?? '0') ?></td>
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
                <td><?= !empty($record['created_at']) ? date('d/m/Y', strtotime($record['created_at'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="pdf-tfoot-row">
                <td colspan="<?= 10 + count($pastorExtraColumns) ?>">TOTAL (<?= count($mapatoPastorList) ?>)</td>
                <td><?= number_format($pTotalAll, 0) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if (empty($bankSlipsList)): ?>
<div class="pdf-section-block">
    <h3 class="pdf-section-title"><?= mi('account_balance', 22) ?> Bank Slips</h3>
    <p class="no-data-inline">Nta bank slip.</p>
</div>
<?php else:
    $bTotalAll = array_sum(array_map(fn($s) => (float) $s['amount'], $bankSlipsList));
?>
<div class="pdf-section-block">
    <h3 class="pdf-section-title"><?= mi('account_balance', 22) ?> Bank Slips — all records</h3>
    <div class="pdf-table-scroll">
    <table class="pdf-table">
        <thead>
            <tr>
                <th>Slip No.</th>
                <th>Bank</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bankSlipsList as $slip): ?>
            <tr>
                <td><?= htmlspecialchars($slip['slip_number']) ?></td>
                <td><?= htmlspecialchars($slip['bank_name']) ?></td>
                <td><strong><?= number_format($slip['amount'], 0) ?></strong></td>
                <td><?= !empty($slip['created_at']) ? date('d/m/Y', strtotime($slip['created_at'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="pdf-tfoot-row">
                <td colspan="2">TOTAL (<?= count($bankSlipsList) ?>)</td>
                <td><?= number_format($bTotalAll, 0) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
<?php endif; ?>
