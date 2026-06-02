<?php

require_once 'config.php';

require_once 'auth.php';

require_once __DIR__ . '/includes/icons.php';



requireLogin();

ensureReceiptTables($pdo);



$currentUser = getCurrentUser();

$userId = (int) $currentUser['id'];

$isGuest = isGuestUser();

$message = '';



if (!$isGuest) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_receipt_stock'])) {

        $rs = (int) ($_POST['stock_start'] ?? 0);

        $re = (int) ($_POST['stock_end'] ?? 0);

        if (addReceiptStock($pdo, $rs, $re, trim($_POST['stock_notes'] ?? ''))) {

            $message = '<div class="alert success">Stock yongewe: ' . htmlspecialchars(receiptRangeLabel($rs, $re)) . '</div>';

        } else {

            $message = '<div class="alert error">Stock ntiyashoboye kongerwa.</div>';

        }

    }



    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_receipt'])) {

        $reqId = (int) ($_POST['request_id'] ?? 0);

        $rs = (int) ($_POST['range_start'] ?? 0);

        $re = (int) ($_POST['range_end'] ?? 0);

        $notes = trim($_POST['assign_notes'] ?? '');

        $result = assignReceiptRequest($pdo, $reqId, $rs, $re, $userId, $notes);

        $message = '<div class="alert ' . ($result['success'] ? 'success' : 'error') . '">' . htmlspecialchars($result['message']) . '</div>';

    }



    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_booklet_return'])) {

        $bookletId = (int) ($_POST['booklet_id'] ?? 0);
        $allPagesReturned = ($_POST['all_pages_returned'] ?? '') === '1';
        $missingPages = trim($_POST['missing_pages'] ?? '');
        $returnComment = trim($_POST['return_admin_comment'] ?? '');

        if (!$allPagesReturned && $missingPages === '') {
            $message = '<div class="alert error">Andika impapuro zitagarutse cyangwa hitamo ko impapuro zose zagarutse.</div>';
        } else {
        $result = approveReceiptBookletReturn($pdo, $bookletId, [
            'all_pages_returned' => $allPagesReturned ? 1 : 0,
            'missing_pages' => $allPagesReturned ? '' : $missingPages,
            'return_admin_comment' => $returnComment,
        ]);

        $gonePageRedirect = max(1, (int) ($_POST['gone_page'] ?? $_POST['booklet_page'] ?? 1));
        $returnedPageRedirect = max(1, (int) ($_POST['returned_page'] ?? 1));
        $tabRedirect = ($_POST['tab'] ?? 'gone') === 'returned' ? 'returned' : 'gone';

        if ($result['success']) {

            $approveUrl = buildReceiptRequestPageUrl($gonePageRedirect, $returnedPageRedirect, $tabRedirect);

            $approveUrl .= (strpos($approveUrl, '?') !== false ? '&' : '?') . 'approved=1#receipt-report';

            header('Location: ' . $approveUrl);

            exit;

        }

        $message = '<div class="alert error">' . htmlspecialchars($result['message']) . '</div>';

        }

    }



    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request_admin'])) {

        $pastorUserId = (int) ($_POST['pastor_user_id'] ?? 0);

        $itoreroId = (int) ($_POST['itorero_id'] ?? 0);

        $result = createReceiptRequestOnBehalf($pdo, $pastorUserId, $itoreroId, $userId);

        $message = '<div class="alert ' . ($result['success'] ? 'success' : 'error') . '">' . htmlspecialchars($result['message']) . '</div>';

    }



    // if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_receipt_itorero_settings'])) {

    //     $itoreroSettingsId = (int) ($_POST['itorero_settings_id'] ?? 0);

    //     $maxBk = (int) ($_POST['receipt_max_booklets'] ?? RECEIPT_DEFAULT_MAX_BOOKLETS);

    //     $canReq = isset($_POST['receipt_can_request']);

    //     $result = updateItoreroReceiptSettings($pdo, $itoreroSettingsId, $maxBk, $canReq);

    //     $message = '<div class="alert ' . ($result['success'] ? 'success' : 'error') . '">' . htmlspecialchars($result['message']) . '</div>';

    // }

}



if (!$isGuest && isset($_GET['approved'])) {

    $message = '<div class="alert success">Booklet yemejwe ko yagarutse.</div>';

}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {

    if (!$isGuest) {

        $message = '<div class="alert error">Gusa pastoro (guest) ashobora kohereza request.</div>';

    } else {

        $itoreroId = (int) ($_POST['itorero_id'] ?? 0);

        $result = createReceiptRequest($pdo, $userId, $itoreroId);

        $message = '<div class="alert ' . ($result['success'] ? 'success' : 'error') . '">' . htmlspecialchars($result['message']) . '</div>';

    }

}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledge_request'])) {

    $reqId = (int) ($_POST['request_id'] ?? 0);

    $result = acknowledgeReceiptRequest($pdo, $reqId, $userId);

    $message = '<div class="alert ' . ($result['success'] ? 'success' : 'error') . '">' . htmlspecialchars($result['message']) . '</div>';

}



$itoreroList = [];

if ($isGuest && getGuestIntaraId() !== null) {

    $itoreroList = getItoreroByIntara($pdo, getGuestIntaraId());

    // foreach ($itoreroList as &$itRow) {

    //     $itRow['receipt_quota'] = getItoreroReceiptQuota($pdo, (int) $itRow['id']);

    // }

    // unset($itRow);

}



$myRequests = $isGuest ? getReceiptRequestsForUser($pdo, $userId) : [];



$pendingReceiptRequests = [];

$itoreroReceiptAdminList = [];

$receiptStockList = [];

$goneBooklets = [];

$returnedBooklets = [];

$receiptReportTab = ($_GET['tab'] ?? 'gone') === 'returned' ? 'returned' : 'gone';

$gonePage = 1;

$goneTotalPages = 1;

$goneTotalRecords = 0;

$goneOffset = 0;

$returnedPage = 1;

$returnedTotalPages = 1;

$returnedTotalRecords = 0;

$returnedOffset = 0;

$receiptPerPage = (int) RECEIPT_BOOKLETS_PER_PAGE;

$guestPastorsList = [];

$allItoreroForAdmin = [];

$acknowledgedReceiptRequests = [];



$reportUserId = $isGuest ? $userId : null;



$legacyGonePage = max(1, (int) ($_GET['booklet_page'] ?? 1));

$gonePage = max(1, (int) ($_GET['gone_page'] ?? $legacyGonePage));

$returnedPage = max(1, (int) ($_GET['returned_page'] ?? 1));



$goneTotalRecords = countGoneReceiptBooklets($pdo, $reportUserId);

$goneTotalPages = max(1, (int) ceil($goneTotalRecords / $receiptPerPage));

if ($gonePage > $goneTotalPages) {

    $gonePage = $goneTotalPages;

}

$goneOffset = ($gonePage - 1) * $receiptPerPage;

$goneBooklets = getGoneReceiptBookletsPaginated($pdo, $gonePage, $receiptPerPage, $reportUserId);



$returnedTotalRecords = countReturnedReceiptBooklets($pdo, $reportUserId);

$returnedTotalPages = max(1, (int) ceil($returnedTotalRecords / $receiptPerPage));

if ($returnedPage > $returnedTotalPages) {

    $returnedPage = $returnedTotalPages;

}

$returnedOffset = ($returnedPage - 1) * $receiptPerPage;

$returnedBooklets = getReturnedReceiptBookletsPaginated($pdo, $returnedPage, $receiptPerPage, $reportUserId);



if (!$isGuest) {

    $pendingReceiptRequests = getPendingReceiptRequests($pdo);

    $receiptStockList = getReceiptStock($pdo);

    // $itoreroReceiptAdminList = getItoreroListForReceiptAdmin($pdo);

    $guestPastorsList = getGuestPastorsForReceiptAdmin($pdo);

    $allItoreroForAdmin = getAllItorero($pdo);

    $acknowledgedReceiptRequests = getAcknowledgedReceiptRequests($pdo);

}

?>

<!DOCTYPE html>

<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php require __DIR__ . '/includes/material-icons-head.php'; ?>

    <link rel="stylesheet" href="styles.css">

</head>

<body class="app-body" data-default-nav-section="<?= $isGuest ? 'request-form' : 'receipt-stock' ?>">

<?php require __DIR__ . '/includes/nav.php'; ?>

<div class="container">

    <div class="brand-header">

        <img class="brand-logo" src="assets/sda.png" alt="Adventist logo">

        <div class="brand-text">

            <h2>Seventh Day Adventist Church</h2>

            <small>Receipt request</small>

        </div>

    </div>



    <p style="text-align:right;color:#666;">May the Lord be with you <b><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></b></p>

    <?= $message ?>



    <h2 class="page-title"><?= mi('receipt_long', 28) ?> RECEIPT REQUEST</h2>



    <?php if ($isGuest): ?>

        <p style="color:#666;margin-bottom:16px;font-size:14px;">

            Admin aguha receipts nk'urugero rimwe: <strong>0012500 to 0012750</strong> (ntibigabanywa).

            <!-- Buri <strong>Itorero</strong> gifite limit y'ubwayo (urugero 5 booklet) — Itorero kindi ntigikingiwe. -->

            Igaruka ry'ibitabo Admin ayemeza kuri iyi page — reba raporo yawe hepfo.

        </p>

        <?php if (empty($itoreroList)): ?>

            <div class="alert error">Konti yawe nta Itorero ifite. Saba admin.</div>

        <?php else: ?>

            <div class="section nav-page-section" data-nav-section="request-form" id="request-form" style="padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:24px;">

                <h3>Saba receipt (Pastor)</h3>

                <form method="POST" style="max-width:500px;">

                    <div class="form-group">

                        <label>Hitamo Itorero:</label>

                        <select name="itorero_id" id="itorero_request_select" required>

                            <option value="">-- Hitamo Itorero --</option>

                            <!-- <?php foreach ($itoreroList as $it):

                                $q = $it['receipt_quota'] ?? getItoreroReceiptQuota($pdo, (int) $it['id']);

                                $optLabel = $it['name'] . ' (' . $q['used_slots'] . '/' . $q['max_booklets'] . ' booklet';

                                if (!$q['can_request']) {

                                    $optLabel .= ' — blocked';

                                } elseif ($q['available_slots'] <= 0) {

                                    $optLabel .= ' — full';

                                } else {

                                    $optLabel .= ', ' . $q['available_slots'] . ' available';

                                }

                                $optLabel .= ')';

                            ?>

                                <option value="<?= (int) $it['id'] ?>"><?= htmlspecialchars($optLabel) ?></option> -->

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- <p id="itorero_quota_hint" style="font-size:13px;color:#666;margin-top:8px;"></p> -->

                    <button type="submit" name="create_request">Ohereza request kuri Admin</button>

                </form>

                <!-- <?php
                $itoreroQuotasJs = [];
                foreach ($itoreroList as $it) {
                    $q = $it['receipt_quota'] ?? getItoreroReceiptQuota($pdo, (int) $it['id']);
                    $itoreroQuotasJs[(string) $it['id']] = [
                        'max' => (int) $q['max_booklets'],
                        'used' => (int) $q['used_slots'],
                        'available' => (int) $q['available_slots'],
                        'can_request' => !empty($q['can_request']),
                        'can_create' => !empty($q['can_create']),
                    ];
                }
                ?>
                <script>

                (function() {

                    const quotas = <?= json_encode($itoreroQuotasJs) ?>;

                    const sel = document.getElementById('itorero_request_select');

                    const hint = document.getElementById('itorero_quota_hint');

                    function updateHint() {

                        const id = sel.value;

                        if (!id || !quotas[id]) {

                            hint.textContent = '';

                            return;

                        }

                        const q = quotas[id];

                        if (!q.can_request) {

                            hint.innerHTML = '<span style="color:#c62828;">Admin yaguze gusaba kuri iri Torero.</span>';

                        } else if (!q.can_create) {

                            hint.innerHTML = '<span style="color:#856404;">Iri Torero rifite booklet ' + q.max + ' zose. Garura imwe cyangwa hitamo Itorero kindi.</span>';

                        } else {

                            hint.textContent = 'Iri Torero: ' + q.used + '/' + q.max + ' zikoreshwa — ' + q.available + ' ushobora gusaba.';

                        }

                    }

                    sel.addEventListener('change', updateHint);

                    updateHint();

                })();

                </script> -->

            </div>

        <?php endif; ?>



        <div class="nav-page-section" data-nav-section="my-requests" id="my-requests">
        <h3>Requests zawe (pending / assigned)</h3>

        <?php if (empty($myRequests)): ?>

            <div class="no-data"><p>Nta request uhari.</p></div>

        <?php else: ?>

            <?php foreach ($myRequests as $req): ?>

                <?php $ret = ($req['range_start'] !== null) ? getReceiptReturnStatus($pdo, $req['id']) : null; ?>

                <div class="section" style="padding:16px;border:1px solid #ddd;border-radius:8px;margin-bottom:12px;">

                    <p><strong><?= htmlspecialchars($req['itorero_name']) ?></strong> (<?= htmlspecialchars($req['intara_name']) ?>)</p>

                    <p>Status: <strong><?= htmlspecialchars(strtoupper($req['status'])) ?></strong></p>

                    <?php if ($req['range_start'] !== null): ?>

                        <p>Assignment: <strong><?= htmlspecialchars(receiptBookletLabel($req['range_start'], $req['range_end'])) ?></strong>

                        <?php if ($ret && $ret['all_returned']): ?>

                            <span style="color:#155724;font-weight:600;"> — Returned</span>

                        <?php elseif ($ret): ?>

                            <span style="color:#c62828;font-weight:600;"> — Not returned</span>

                        <?php endif; ?></p>

                        <?php if ($ret && !$ret['all_returned']): ?>

                        <div class="alert error" style="margin-top:10px;text-align:left;">

                            <strong>Warning:</strong> Receipt ntiyagarutse — <?= htmlspecialchars($ret['range_label']) ?>

                        </div>

                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if ($req['admin_notes']): ?>

                        <p><em><?= htmlspecialchars($req['admin_notes']) ?></em></p>

                    <?php endif; ?>



                    <?php if ($req['status'] === 'assigned'): ?>

                        <form method="POST" style="margin-top:10px;">

                            <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">

                            <button type="submit" name="acknowledge_request">Nemeza ko nabonye receipts</button>

                        </form>

                    <?php elseif ($req['status'] === 'completed'): ?>

                        <p class="alert success" style="margin-top:8px;">Receipts zose zagarutse neza.</p>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

        </div>

        <?php
        $gonePerPage = $receiptPerPage;
        $returnedPerPage = $receiptPerPage;
        require __DIR__ . '/includes/receipt-booklet-report.php';
        ?>

    <?php else: ?>

        <p style="font-size:13px;color:#666;margin-bottom:16px;">

            Buri receipt ni urutonde rumwe (ntirigabanywa): urugero <strong>0012500 to 0012750</strong>.

            Andika From / To (12500–12750) — system izerekana n'inyuguti z'imbere (0012500).

            Admin ayemeza ko booklet yagarutse — iracyaboneka mu rutonde rwa <strong>Pastor yemeje booklet</strong> (status: Yagarutse).

        </p>



        <div class="section nav-page-section" data-nav-section="receipt-stock" id="receipt-stock" style="padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:24px;">

            <h3>Stock (receipt ranges)</h3>

            <form method="POST" class="form-row" style="margin-bottom:12px;">

                <input type="number" name="stock_start" placeholder="From (e.g. 12500)" min="1" required>

                <input type="number" name="stock_end" placeholder="To (e.g. 12750)" min="1" required>

                <input type="text" name="stock_notes" placeholder="Notes">

                <button type="submit" name="add_receipt_stock">Add to stock</button>

            </form>

            <?php if (!empty($receiptStockList)): ?>

            <p style="font-size:13px;color:#666;">Stock: <?php

                echo implode('; ', array_map(fn($s) => receiptRangeLabel($s['range_start'], $s['range_end']), $receiptStockList));

            ?></p>

            <?php endif; ?>

        </div>



        <div class="section nav-page-section" data-nav-section="itorero-receipt-limits" id="itorero-receipt-limits" style="padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:24px;">

            <h3><?= mi('church', 22) ?> Itorero — booklet limits</h3>

            <p style="font-size:13px;color:#666;margin-bottom:12px;">Buri Itorero gifite limit y'ubwayo (urugero 5 booklet). Itorero rimwe ntirigira indi. Iyo booklet igarutse kuri iri Torero, pastoro ashobora gusaba indi kuri iyo Torero gusa.</p>

            <?php if (empty($itoreroReceiptAdminList)): ?>

                <p>Nta Itorero rihari.</p>

            <?php else: ?>

            <div class="table-wrap">

            <table style="width:100%;">

                <thead>

                    <tr>

                        <th>Itorero</th>

                        <th>Intara</th>

                        <th>In use</th>

                        <th>Max</th>

                        <th>Can request</th>

                        <th>Igenamiterere</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($itoreroReceiptAdminList as $itLim): ?>

                <tr>

                    <td><strong><?= htmlspecialchars($itLim['itorero_name']) ?></strong></td>

                    <td><?= htmlspecialchars($itLim['intara_name'] ?? '—') ?></td>

                    <td><?= (int) $itLim['used_slots'] ?> / <?= (int) $itLim['receipt_max_booklets'] ?></td>

                    <td colspan="3">

                        <form method="POST" class="form-row" style="margin:0;flex-wrap:wrap;gap:8px;">

                            <input type="hidden" name="itorero_settings_id" value="<?= (int) $itLim['id'] ?>">

                            <input type="number" name="receipt_max_booklets" value="<?= (int) $itLim['receipt_max_booklets'] ?>" min="1" max="50" style="width:70px;" required>

                            <label style="display:inline-flex;align-items:center;gap:4px;margin:0;">

                                <input type="checkbox" name="receipt_can_request" value="1" <?= !empty($itLim['receipt_can_request']) ? 'checked' : '' ?>>

                                Emera gusaba

                            </label>

                            <button type="submit" name="save_receipt_itorero_settings">Bika</button>

                        </form>

                    </td>

                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

            </div>

            <?php endif; ?>

        </div>



        <div class="section nav-page-section" data-nav-section="admin-request-behalf" id="admin-request-behalf" style="padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:24px;">

            <h3>Saba booklet ku izina rya Pastor (Admin)</h3>

            <p style="font-size:13px;color:#666;margin-bottom:12px;">Request igena kuri pastoro ariko Admin ni we uyisaba. Pastor azabona request mu rutonde rwe.</p>

            <?php if (empty($guestPastorsList)): ?>

                <p>Nta pastoro (guest) bahari — kora konti ya guest mbere.</p>

            <?php else: ?>

                <form method="POST" style="max-width:560px;">

                    <div class="form-group">

                        <label>Pastor (guest):</label>

                        <select name="pastor_user_id" id="admin_pastor_select" required>

                            <option value="">-- Hitamo Pastor --</option>

                            <?php foreach ($guestPastorsList as $gp): ?>

                                <option value="<?= (int) $gp['id'] ?>" data-intara="<?= (int) $gp['intara_id'] ?>">

                                    <?= htmlspecialchars($gp['username']) ?> (<?= htmlspecialchars($gp['intara_name'] ?? 'Intara') ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label>Itorero:</label>

                        <select name="itorero_id" id="admin_behalf_itorero" required>

                            <option value="">-- Hitamo Itorero --</option>

                            <?php foreach ($allItoreroForAdmin as $it): ?>

                                <option value="<?= (int) $it['id'] ?>" data-intara="<?= (int) $it['intara_id'] ?>">

                                    <?= htmlspecialchars($it['name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <button type="submit" name="create_request_admin">Ohereza request ku izina rya Pastor</button>

                </form>

                <script>

                (function() {

                    const pastorSel = document.getElementById('admin_pastor_select');

                    const itoreroSel = document.getElementById('admin_behalf_itorero');

                    if (!pastorSel || !itoreroSel) return;

                    function filterItorero() {

                        const intaraId = pastorSel.selectedOptions[0]?.dataset.intara || '';

                        itoreroSel.querySelectorAll('option').forEach(function(opt) {

                            if (!opt.value) return;

                            opt.hidden = intaraId !== '' && opt.dataset.intara !== intaraId;

                        });

                        if (itoreroSel.selectedOptions[0]?.hidden) {

                            itoreroSel.value = '';

                        }

                    }

                    pastorSel.addEventListener('change', filterItorero);

                    filterItorero();

                })();

                </script>

            <?php endif; ?>

        </div>



        <div class="section nav-page-section" data-nav-section="pastor-acknowledged" id="pastor-acknowledged" style="padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:24px;">

            <h3><?= mi('check_circle', 22) ?> Pastor yemeje ko yafashe booklet</h3>

            <p style="font-size:13px;color:#666;margin-bottom:12px;">Iyi list igaragaza pastoro bemeye ko babonye receipt/booklet. Records zirabikwa n'inyuma y'uko admin yemeje ko booklet yagarutse.</p>

            <?php if (empty($acknowledgedReceiptRequests)): ?>

                <p>Nta pastoro wemeje booklet ubu.</p>

            <?php else: ?>

                <div class="table-wrap">

                <table style="width:100%;">

                    <thead>

                        <tr>

                            <th>Pastor</th>

                            <th>Itorero</th>

                            <th>Intara</th>

                            <th>Booklet</th>

                            <th>Yemejwe</th>

                            <th>Status</th>

                            <th>Requested by</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($acknowledgedReceiptRequests as $ar): ?>

                        <tr>

                            <td><strong><?= htmlspecialchars($ar['username']) ?></strong></td>

                            <td><?= htmlspecialchars($ar['itorero_name']) ?></td>

                            <td><?= htmlspecialchars($ar['intara_name']) ?></td>

                            <td><strong><?= htmlspecialchars(receiptBookletLabel($ar['range_start'], $ar['range_end'])) ?></strong></td>

                            <td style="font-size:12px;"><?= !empty($ar['acknowledged_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($ar['acknowledged_at']))) : '—' ?></td>

                            <td style="font-size:12px;">
                                <?php if (!empty($ar['booklet_returned_at'])): ?>
                                    <span style="color:#155724;font-weight:600;">Yagarutse</span>
                                    <br><small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ar['booklet_returned_at']))) ?></small>
                                <?php elseif (($ar['status'] ?? '') === 'completed'): ?>
                                    <span style="color:#155724;font-weight:600;">Yagarutse</span>
                                <?php else: ?>
                                    <span style="color:#856404;">Iracyafite</span>
                                <?php endif; ?>
                            </td>

                            <td style="font-size:12px;">

                                <?php if (!empty($ar['requested_by_admin_name'])): ?>

                                    <span class="badge-admin-behalf">Admin: <?= htmlspecialchars($ar['requested_by_admin_name']) ?></span>

                                <?php else: ?>

                                    Pastor

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

                </div>

            <?php endif; ?>

        </div>



        <div class="section nav-page-section" data-nav-section="pending-requests" id="pending-requests" style="padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:24px;">

            <h3>Pending requests</h3>

            <?php if (empty($pendingReceiptRequests)): ?>

                <p>Nta request itegereje.</p>

            <?php else: ?>

                <?php foreach ($pendingReceiptRequests as $pr): ?>

                <div style="border:1px solid #eee;padding:14px;border-radius:8px;margin-bottom:12px;">

                    <p><strong><?= htmlspecialchars($pr['username']) ?></strong> — <?= htmlspecialchars($pr['itorero_name']) ?> (<?= htmlspecialchars($pr['intara_name']) ?>)</p>

                    <?php if (!empty($pr['requested_by_admin_name'])): ?>

                        <p style="font-size:12px;color:#1565c0;"><span class="badge-admin-behalf">Yasabwe na Admin: <?= htmlspecialchars($pr['requested_by_admin_name']) ?></span></p>

                    <?php endif; ?>

                    <p style="font-size:12px;color:#666;">Requested: <?= htmlspecialchars($pr['created_at']) ?></p>

                    <form method="POST" class="form-row" style="margin-top:10px;">

                        <input type="hidden" name="request_id" value="<?= (int) $pr['id'] ?>">

                        <input type="number" name="range_start" placeholder="From (e.g. 12500)" min="1" required>

                        <input type="number" name="range_end" placeholder="To (e.g. 12750)" min="1" required>

                        <input type="text" name="assign_notes" placeholder="Notes">

                        <button type="submit" name="assign_receipt">Give receipt</button>

                    </form>

                </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>



        <?php
        $gonePerPage = $receiptPerPage;
        $returnedPerPage = $receiptPerPage;
        require __DIR__ . '/includes/receipt-booklet-report.php';
        ?>

    <?php endif; ?>

</div>

<script>
function validateReceiptReturnForm(form) {
    var allYes = form.querySelector('input[name="all_pages_returned"][value="1"]');
    var allNo = form.querySelector('input[name="all_pages_returned"][value="0"]');
    if (!allYes.checked && !allNo.checked) {
        alert('Hitamo niba impapuro zose zagarutse.');
        return false;
    }
    if (allNo.checked) {
        var missing = form.querySelector('input[name="missing_pages"]');
        if (!missing.value.trim()) {
            alert('Andika impapuro zitagarutse.');
            missing.focus();
            return false;
        }
    }
    return confirm('Emeza ko iyi booklet yagarutse?');
}
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

</body>

</html>

