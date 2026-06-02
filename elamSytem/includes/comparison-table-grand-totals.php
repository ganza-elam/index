<?php
$gtPastor = 0;
$gtInsert = 0;
$gtBank = 0;
$mkGt = (int) $filter_month;
$monthLabelGt = $monthOptions[$mkGt] ?? '-';
?>
<table class="pdf-table pdf-table--wide">
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
        <?php foreach ($grandTotalsRows as $row):
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
        <tr style="background:#fff8e1;font-weight:bold;">
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
