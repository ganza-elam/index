<?php

/**

 * Gone / returned receipt booklet report with tabs and pagination.

 * Expects: $receiptReportTab, $goneBooklets, $returnedBooklets, $isGuest,

 *          gone/returned pagination vars, $gonePage, $returnedPage

 */

$receiptReportTab = $receiptReportTab ?? 'gone';

$goneTabUrl = buildReceiptRequestPageUrl(1, $returnedPage ?? 1, 'gone') . '#receipt-report';

$returnedTabUrl = buildReceiptRequestPageUrl($gonePage ?? 1, 1, 'returned') . '#receipt-report';

?>

<div class="section receipt-report-section nav-page-section" data-nav-section="receipt-report" id="receipt-report" style="padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:24px;">

    <h3>Raporo — Receipt zagiye &amp; zagarutse</h3>

    <p style="font-size:13px;color:#666;margin-bottom:12px;">

        <strong>Zagiye (gone):</strong> booklet zatanzwe ariko zitagarutse.

        <strong>Zagarutse (returned):</strong> booklet zemewe ko zagarutse.

    </p>



    <div class="receipt-report-tabs" role="tablist">

        <a href="<?= htmlspecialchars($goneTabUrl) ?>"

           class="receipt-report-tab<?= $receiptReportTab === 'gone' ? ' is-active' : '' ?>"

           role="tab"

           aria-selected="<?= $receiptReportTab === 'gone' ? 'true' : 'false' ?>">

            Zagiye (gone) <span class="receipt-tab-count"><?= (int) ($goneTotalRecords ?? 0) ?></span>

        </a>

        <a href="<?= htmlspecialchars($returnedTabUrl) ?>"

           class="receipt-report-tab<?= $receiptReportTab === 'returned' ? ' is-active' : '' ?>"

           role="tab"

           aria-selected="<?= $receiptReportTab === 'returned' ? 'true' : 'false' ?>">

            Zagarutse (returned) <span class="receipt-tab-count"><?= (int) ($returnedTotalRecords ?? 0) ?></span>

        </a>

    </div>



    <?php if ($receiptReportTab === 'gone'): ?>

        <?php if (empty($goneBooklets)): ?>

            <p style="margin-top:16px;">Nta booklet ziri hanze — zose zagarutse cyangwa nta booklet zatanzwe.</p>

        <?php else: ?>

            <div class="table-wrap" style="margin-top:16px;">

            <table style="width:100%;">

                <thead>

                    <tr>

                        <th>Itorero</th>

                        <th>Intara</th>

                        <th>Pastor</th>

                        <th>Booklet</th>

                        <th>Assigned</th>

                        <?php if (!$isGuest): ?>

                            <th>Requested by</th>

                            <th>Ibikorwa</th>

                        <?php endif; ?>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($goneBooklets as $row): ?>

                    <tr>

                        <td><strong><?= htmlspecialchars($row['itorero_name']) ?></strong></td>

                        <td><?= htmlspecialchars($row['intara_name']) ?></td>

                        <td><?= htmlspecialchars($row['pastor_name']) ?></td>

                        <td><strong><?= htmlspecialchars($row['booklet_label']) ?></strong></td>

                        <td style="font-size:12px;"><?= $row['assigned_at'] ? htmlspecialchars(date('d/m/Y', strtotime($row['assigned_at']))) : '—' ?></td>

                        <?php if (!$isGuest): ?>

                            <td style="font-size:12px;">

                                <?php if (!empty($row['requested_by_admin_name'])): ?>

                                    <span class="badge-admin-behalf">Admin: <?= htmlspecialchars($row['requested_by_admin_name']) ?></span>

                                <?php else: ?>

                                    Pastor

                                <?php endif; ?>

                            </td>

                            <td>

                                <details class="receipt-return-approve">

                                    <summary class="edit" style="cursor:pointer;list-style:none;display:inline-block;padding:6px 10px;background:#1976d2;color:#fff;border-radius:6px;">Emeza ko yagarutse</summary>

                                    <form method="POST" class="receipt-return-form" style="margin-top:10px;padding:12px;border:1px solid #ddd;border-radius:8px;background:#fafafa;min-width:280px;">

                                        <input type="hidden" name="booklet_id" value="<?= (int) $row['booklet_id'] ?>">

                                        <input type="hidden" name="gone_page" value="<?= (int) ($gonePage ?? 1) ?>">

                                        <input type="hidden" name="returned_page" value="<?= (int) ($returnedPage ?? 1) ?>">

                                        <input type="hidden" name="tab" value="gone">

                                        <p style="font-weight:600;margin:0 0 8px;"><?= htmlspecialchars($row['booklet_label']) ?></p>

                                        <div class="form-group" style="margin-bottom:8px;">

                                            <label style="display:block;margin-bottom:4px;">Impapuro zose zagarutse?</label>

                                            <label style="margin-right:12px;"><input type="radio" name="all_pages_returned" value="1" required onclick="this.closest('form').querySelector('.missing-pages-wrap').style.display='none'"> Yego</label>

                                            <label><input type="radio" name="all_pages_returned" value="0" required onclick="this.closest('form').querySelector('.missing-pages-wrap').style.display='block'"> Oya</label>

                                        </div>

                                        <div class="form-group missing-pages-wrap" style="display:none;margin-bottom:8px;">

                                            <label>Impapuro zitagarutse (urugero: 12, 15-18):</label>

                                            <input type="text" name="missing_pages" placeholder="Andika numero z'impapuro zitagarutse">

                                        </div>

                                        <div class="form-group" style="margin-bottom:8px;">

                                            <label>Comment (optional):</label>

                                            <textarea name="return_admin_comment" rows="2" placeholder="Ibindi bisobanuro ku gusubiza booklet"></textarea>

                                        </div>

                                        <button type="submit" name="approve_booklet_return" onclick="return validateReceiptReturnForm(this.form);">Bika &amp; Emeza</button>

                                    </form>

                                </details>

                            </td>

                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

            </div>

            <?php

            $receiptPagPage = $gonePage;

            $receiptPagTotalPages = $goneTotalPages;

            $receiptPagTotalRecords = $goneTotalRecords;

            $receiptPagOffset = $goneOffset;

            $receiptPagPerPage = $gonePerPage;

            $receiptPagLabel = 'Gone';

            $receiptPagBuildUrl = function ($p) use ($returnedPage, $receiptReportTab) {

                return buildReceiptRequestPageUrl($p, $returnedPage ?? 1, 'gone') . '#receipt-report';

            };

            require __DIR__ . '/receipt-report-pagination.php';

            ?>

        <?php endif; ?>



    <?php else: ?>

        <?php if (empty($returnedBooklets)): ?>

            <p style="margin-top:16px;">Nta booklet zagarutse zihari mu raporo.</p>

        <?php else: ?>

            <div class="table-wrap" style="margin-top:16px;">

            <table style="width:100%;">

                <thead>

                    <tr>

                        <th>Itorero</th>

                        <th>Intara</th>

                        <th>Pastor</th>

                        <th>Booklet</th>

                        <th>Returned</th>

                        <th>Impapuro</th>

                        <th>Comment</th>

                        <th>Via</th>

                        <?php if (!$isGuest): ?>

                            <th>Requested by</th>

                        <?php endif; ?>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($returnedBooklets as $row): ?>

                    <tr>

                        <td><strong><?= htmlspecialchars($row['itorero_name']) ?></strong></td>

                        <td><?= htmlspecialchars($row['intara_name']) ?></td>

                        <td><?= htmlspecialchars($row['pastor_name']) ?></td>

                        <td><strong><?= htmlspecialchars($row['booklet_label']) ?></strong></td>

                        <td style="font-size:12px;"><?= $row['returned_at'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($row['returned_at']))) : '—' ?></td>

                        <td style="font-size:12px;">

                            <?php if ($row['all_pages_returned'] === null || $row['all_pages_returned'] === ''): ?>

                                —

                            <?php elseif ((int) $row['all_pages_returned'] === 1): ?>

                                <span style="color:#155724;font-weight:600;">Zose</span>

                            <?php else: ?>

                                <span style="color:#c62828;font-weight:600;">Zitagarutse:</span>

                                <?= htmlspecialchars($row['missing_pages'] ?? '—') ?>

                            <?php endif; ?>

                        </td>

                        <td style="font-size:12px;"><?= !empty($row['return_admin_comment']) ? nl2br(htmlspecialchars($row['return_admin_comment'])) : '—' ?></td>

                        <td style="font-size:12px;"><?= htmlspecialchars($row['returned_via'] ?? '—') ?></td>

                        <?php if (!$isGuest): ?>

                            <td style="font-size:12px;">

                                <?php if (!empty($row['requested_by_admin_name'])): ?>

                                    <span class="badge-admin-behalf">Admin: <?= htmlspecialchars($row['requested_by_admin_name']) ?></span>

                                <?php else: ?>

                                    Pastor

                                <?php endif; ?>

                            </td>

                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

            </div>

            <?php

            $receiptPagPage = $returnedPage;

            $receiptPagTotalPages = $returnedTotalPages;

            $receiptPagTotalRecords = $returnedTotalRecords;

            $receiptPagOffset = $returnedOffset;

            $receiptPagPerPage = $returnedPerPage;

            $receiptPagLabel = 'Returned';

            $receiptPagBuildUrl = function ($p) use ($gonePage) {

                return buildReceiptRequestPageUrl($gonePage ?? 1, $p, 'returned') . '#receipt-report';

            };

            require __DIR__ . '/receipt-report-pagination.php';

            ?>

        <?php endif; ?>

    <?php endif; ?>

</div>

