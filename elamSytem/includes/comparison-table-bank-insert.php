<?php
$cmpInsert = 0;
$cmpBank2 = 0;
$mk = (int) $filter_month;
$monthLabel = $monthOptions[$mk] ?? '-';
?>
<table class="pdf-table">
    <thead>
        <tr>
            <th>Intara</th>
            <th>Ukwezi</th>
            <th>IBYANYUZE MUMA SUCHE Total</th>
            <th>Bank Slip Total</th>
            <th>Difference (Bank − INSERT)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($comparisonInsertRows as $row):
            $cmpInsert += $row['insert_total'];
            $cmpBank2 += $row['bank_total'];
        ?>
        <tr>
            <td><?= htmlspecialchars($row['intara_name']) ?></td>
            <td><?= htmlspecialchars($monthLabel) ?></td>
            <td><?= number_format($row['insert_total'], 0) ?></td>
            <td><?= number_format($row['bank_total'], 0) ?></td>
            <td><?= number_format($row['difference'], 0) ?></td>
            <td><span class="cr-status cr-status-<?= $row['status'] ?>"><?= htmlspecialchars($row['status_label']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background:#e3f2fd;font-weight:bold;">
            <td colspan="2">TOTAL</td>
            <td><?= number_format($cmpInsert, 0) ?></td>
            <td><?= number_format($cmpBank2, 0) ?></td>
            <td><?= number_format($cmpBank2 - $cmpInsert, 0) ?></td>
            <td><?php
                $d = $cmpBank2 - $cmpInsert;
                if (abs($d) < 0.01) echo '<span class="cr-status cr-status-equal">Equal</span>';
                elseif ($d > 0) echo '<span class="cr-status cr-status-profit">Surplus</span>';
                else echo '<span class="cr-status cr-status-loss">Deficit</span>';
            ?></td>
        </tr>
    </tfoot>
</table>
