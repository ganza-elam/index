<?php
$cmpPastor = 0;
$cmpBank = 0;
$mk = (int) $filter_month;
$monthLabel = $monthOptions[$mk] ?? '-';
?>
<table class="pdf-table">
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
        <?php foreach ($comparisonRows as $row):
            $cmpPastor += $row['pastor_total'];
            $cmpBank += $row['bank_total'];
        ?>
        <tr>
            <td><?= htmlspecialchars($row['intara_name']) ?></td>
            <td><?= htmlspecialchars($monthLabel) ?></td>
            <td><?= number_format($row['pastor_total'], 0) ?></td>
            <td><?= number_format($row['bank_total'], 0) ?></td>
            <td><?= number_format($row['difference'], 0) ?></td>
            <td><span class="cr-status cr-status-<?= $row['status'] ?>"><?= htmlspecialchars($row['status_label']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background:#e3f2fd;font-weight:bold;">
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
