<?php
/**
 * Receipt request / assignment / return tracking (one range per assignment, e.g. 0012500–0012750).
 */

function ensureReceiptTables($pdo) {
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS receipt_stock (
        id int(11) NOT NULL AUTO_INCREMENT,
        range_start int(11) NOT NULL,
        range_end int(11) NOT NULL,
        notes varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS receipt_requests (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        itorero_id int(11) NOT NULL,
        status enum('pending','assigned','acknowledged','completed') NOT NULL DEFAULT 'pending',
        range_start int(11) DEFAULT NULL,
        range_end int(11) DEFAULT NULL,
        admin_notes varchar(500) DEFAULT NULL,
        assigned_by int(11) DEFAULT NULL,
        assigned_at datetime DEFAULT NULL,
        acknowledged_at datetime DEFAULT NULL,
        completed_at datetime DEFAULT NULL,
        created_at datetime DEFAULT current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS receipt_booklets (
        id int(11) NOT NULL AUTO_INCREMENT,
        request_id int(11) NOT NULL,
        booklet_no int(11) NOT NULL,
        range_start int(11) NOT NULL,
        range_end int(11) NOT NULL,
        returned_at datetime DEFAULT NULL,
        returned_via enum('insert_data','admin_approve') DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_receipt_booklet (request_id, booklet_no),
        KEY idx_receipt_booklets_req (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    try {
        $pdo->exec("ALTER TABLE receipt_booklets MODIFY returned_via enum('insert_data','admin_approve') DEFAULT NULL");
    } catch (PDOException $e) {
        // Column may already be updated.
    }

    ensureReceiptRequestAdminColumns($pdo);
    ensureReceiptBookletReturnColumns($pdo);

    $done = true;
}

function ensureReceiptBookletReturnColumns($pdo) {
    static $done = false;
    if ($done) {
        return;
    }
    $cols = $pdo->query("SHOW COLUMNS FROM receipt_booklets")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('return_admin_comment', $cols, true)) {
        $pdo->exec("ALTER TABLE receipt_booklets ADD COLUMN return_admin_comment varchar(1000) DEFAULT NULL AFTER returned_via");
    }
    if (!in_array('all_pages_returned', $cols, true)) {
        $pdo->exec("ALTER TABLE receipt_booklets ADD COLUMN all_pages_returned tinyint(1) DEFAULT NULL AFTER return_admin_comment");
    }
    if (!in_array('missing_pages', $cols, true)) {
        $pdo->exec("ALTER TABLE receipt_booklets ADD COLUMN missing_pages varchar(500) DEFAULT NULL AFTER all_pages_returned");
    }
    $done = true;
}

function ensureReceiptRequestAdminColumns($pdo) {
    static $done = false;
    if ($done) {
        return;
    }
    $cols = $pdo->query("SHOW COLUMNS FROM receipt_requests")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('requested_by_admin_id', $cols, true)) {
        $pdo->exec("ALTER TABLE receipt_requests ADD COLUMN requested_by_admin_id int(11) DEFAULT NULL AFTER user_id");
        $pdo->exec("ALTER TABLE receipt_requests ADD KEY idx_receipt_req_admin (requested_by_admin_id)");
    }
    $done = true;
}

define('RECEIPT_BOOKLETS_PER_PAGE', 15);

/** Minimum digit width when showing receipt numbers (e.g. 12500 → 0012500). */
define('RECEIPT_NUM_MIN_WIDTH', 7);

function receiptNumberWidth($start, $end) {
    $start = (int) $start;
    $end = (int) $end;
    return max(RECEIPT_NUM_MIN_WIDTH, strlen((string) $start), strlen((string) $end));
}

/** Display number with leading zeros preserved (e.g. 12500 → 0012500). */
function receiptFormatNum($n, $width = null) {
    $n = (int) $n;
    if ($width === null) {
        $width = max(RECEIPT_NUM_MIN_WIDTH, strlen((string) $n));
    }
    return str_pad((string) $n, (int) $width, '0', STR_PAD_LEFT);
}

function receiptBookletLabel($start, $end) {
    $width = receiptNumberWidth($start, $end);
    return receiptFormatNum($start, $width) . ' to ' . receiptFormatNum($end, $width);
}

function receiptRangeLabel($start, $end) {
    if ($start === null || $end === null) {
        return '';
    }
    return receiptBookletLabel($start, $end);
}

/**
 * One receipt booklet = full assigned range (not split into sub-ranges).
 * Example: 12500–12750 → single booklet 0012500 to 0012750.
 */
function splitReceiptBooklets($rangeStart, $rangeEnd) {
    $start = (int) $rangeStart;
    $end = (int) $rangeEnd;
    if ($start < 1 || $end < $start) {
        return [];
    }

    return [[
        'booklet_no' => 1,
        'range_start' => $start,
        'range_end' => $end,
    ]];
}

/** Merge legacy multi-part booklets into one row per request range. */
function ensureSingleReceiptBooklet($pdo, $requestId) {
    ensureReceiptTables($pdo);
    $req = getReceiptRequestById($pdo, $requestId);
    if (!$req || $req['range_start'] === null || $req['range_end'] === null) {
        return;
    }

    $booklets = getReceiptBooklets($pdo, $requestId);
    if (count($booklets) <= 1) {
        return;
    }

    $allReturned = true;
    foreach ($booklets as $b) {
        if (empty($b['returned_at'])) {
            $allReturned = false;
            break;
        }
    }

    $pdo->prepare("DELETE FROM receipt_booklets WHERE request_id = ?")->execute([(int) $requestId]);
    $stmt = $pdo->prepare("INSERT INTO receipt_booklets (request_id, booklet_no, range_start, range_end, returned_at, returned_via)
        VALUES (?, 1, ?, ?, ?, ?)");
    $returnedAt = $allReturned ? date('Y-m-d H:i:s') : null;
    $returnedVia = $allReturned ? ($booklets[0]['returned_via'] ?? 'admin_approve') : null;
    $stmt->execute([
        (int) $requestId,
        (int) $req['range_start'],
        (int) $req['range_end'],
        $returnedAt,
        $returnedVia,
    ]);
}

function countReceiptBookletsForRange($rangeStart, $rangeEnd) {
    return count(splitReceiptBooklets($rangeStart, $rangeEnd));
}

function guestCanAccessItorero($pdo, $itoreroId) {
    if (!isGuestUser()) {
        return true;
    }
    $assigned = getGuestIntaraId();
    if ($assigned === null) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id FROM itorero WHERE id = ? AND intara_id = ?");
    $stmt->execute([(int) $itoreroId, $assigned]);
    return (bool) $stmt->fetch();
}

function pastorCanAccessItorero($pdo, $pastorUserId, $itoreroId) {
    ensureUsersIntaraColumn($pdo);
    $stmt = $pdo->prepare("SELECT intara_id, role FROM users WHERE id = ?");
    $stmt->execute([(int) $pastorUserId]);
    $user = $stmt->fetch();
    if (!$user || ($user['role'] ?? '') !== 'guest' || empty($user['intara_id'])) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id FROM itorero WHERE id = ? AND intara_id = ?");
    $stmt->execute([(int) $itoreroId, (int) $user['intara_id']]);
    return (bool) $stmt->fetch();
}

function getGuestPastorsForReceiptAdmin($pdo) {
    ensureUsersIntaraColumn($pdo);
    $stmt = $pdo->query("SELECT u.id, u.username, u.intara_id, i.name AS intara_name
        FROM users u
        LEFT JOIN intara i ON u.intara_id = i.id
        WHERE u.role = 'guest' AND u.intara_id IS NOT NULL
        ORDER BY i.name ASC, u.username ASC");
    return $stmt->fetchAll();
}

function createReceiptRequest($pdo, $userId, $itoreroId, $requestedByAdminId = null) {
    ensureReceiptTables($pdo);
    $requestedByAdminId = $requestedByAdminId ? (int) $requestedByAdminId : null;

    if ($requestedByAdminId) {
        if (!pastorCanAccessItorero($pdo, $userId, $itoreroId)) {
            return ['success' => false, 'message' => 'Pastor ntabwo akwiriye iri Torero.'];
        }
    } elseif (!guestCanAccessItorero($pdo, $itoreroId)) {
        return ['success' => false, 'message' => 'Ntabwo wemerewe gukoresha iri Torero.'];
    }

    $stmt = $pdo->prepare("INSERT INTO receipt_requests (user_id, requested_by_admin_id, itorero_id, status) VALUES (?, ?, ?, 'pending')");
    if ($stmt->execute([(int) $userId, $requestedByAdminId, (int) $itoreroId])) {
        $prefix = $requestedByAdminId ? 'Request yoherejwe ku izina rya pastoro. ' : '';
        return [
            'success' => true,
            'message' => $prefix . 'Request yoherejwe neza kuri iri Torero.',
        ];
    }
    return ['success' => false, 'message' => 'Habaye ikibazo mu kohereza request.'];
}

function createReceiptRequestOnBehalf($pdo, $pastorUserId, $itoreroId, $adminUserId) {
    return createReceiptRequest($pdo, (int) $pastorUserId, (int) $itoreroId, (int) $adminUserId);
}

function addReceiptStock($pdo, $rangeStart, $rangeEnd, $notes = '') {
    ensureReceiptTables($pdo);
    $rangeStart = (int) $rangeStart;
    $rangeEnd = (int) $rangeEnd;
    if ($rangeStart < 1 || $rangeEnd < $rangeStart) {
        return false;
    }
    $stmt = $pdo->prepare("INSERT INTO receipt_stock (range_start, range_end, notes) VALUES (?, ?, ?)");
    return $stmt->execute([$rangeStart, $rangeEnd, $notes]);
}

function getReceiptStock($pdo) {
    ensureReceiptTables($pdo);
    return $pdo->query("SELECT * FROM receipt_stock ORDER BY range_start ASC")->fetchAll();
}

function rangesOverlap($aStart, $aEnd, $bStart, $bEnd) {
    return $aStart <= $bEnd && $bStart <= $aEnd;
}

function isReceiptRangeAvailable($pdo, $start, $end, $excludeRequestId = null) {
    ensureReceiptTables($pdo);
    $start = (int) $start;
    $end = (int) $end;
    $sql = "SELECT id, range_start, range_end FROM receipt_requests
            WHERE status IN ('assigned','acknowledged') AND range_start IS NOT NULL AND range_end IS NOT NULL";
    $stmt = $pdo->query($sql);
    foreach ($stmt->fetchAll() as $row) {
        if ($excludeRequestId && (int) $row['id'] === (int) $excludeRequestId) {
            continue;
        }
        if (rangesOverlap($start, $end, (int) $row['range_start'], (int) $row['range_end'])) {
            return false;
        }
    }
    return true;
}

function createReceiptBookletsForRequest($pdo, $requestId, $rangeStart, $rangeEnd) {
    ensureReceiptTables($pdo);
    $pdo->prepare("DELETE FROM receipt_booklets WHERE request_id = ?")->execute([(int) $requestId]);
    $booklets = splitReceiptBooklets($rangeStart, $rangeEnd);
    $stmt = $pdo->prepare("INSERT INTO receipt_booklets (request_id, booklet_no, range_start, range_end) VALUES (?, ?, ?, ?)");
    foreach ($booklets as $b) {
        $stmt->execute([(int) $requestId, (int) $b['booklet_no'], (int) $b['range_start'], (int) $b['range_end']]);
    }
    return $booklets;
}

function assignReceiptRequest($pdo, $requestId, $rangeStart, $rangeEnd, $adminUserId, $notes = '') {
    ensureReceiptTables($pdo);
    $rangeStart = (int) $rangeStart;
    $rangeEnd = (int) $rangeEnd;
    if ($rangeStart < 1 || $rangeEnd < $rangeStart) {
        return ['success' => false, 'message' => 'Range ntabwo ari yo (urugero: 011 kugeza 040).'];
    }
    if (!isReceiptRangeAvailable($pdo, $rangeStart, $rangeEnd, $requestId)) {
        return ['success' => false, 'message' => 'Iyi range y\'amadosiye isanzwe ikoreshwa.'];
    }
    $stmt = $pdo->prepare("UPDATE receipt_requests SET
        status = 'assigned', range_start = ?, range_end = ?, admin_notes = ?,
        assigned_by = ?, assigned_at = NOW()
        WHERE id = ? AND status = 'pending'");
    if (!$stmt->execute([$rangeStart, $rangeEnd, $notes, (int) $adminUserId, (int) $requestId]) || $stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Ntibyashoboye guha receipt (reba ko request iri pending).'];
    }
    $booklets = createReceiptBookletsForRequest($pdo, $requestId, $rangeStart, $rangeEnd);
    $label = receiptBookletLabel($rangeStart, $rangeEnd);
    return [
        'success' => true,
        'message' => 'Receipt: ' . $label,
        'booklet_count' => count($booklets),
    ];
}

function acknowledgeReceiptRequest($pdo, $requestId, $userId) {
    ensureReceiptTables($pdo);
    $stmt = $pdo->prepare("UPDATE receipt_requests SET status = 'acknowledged', acknowledged_at = NOW()
        WHERE id = ? AND user_id = ? AND status = 'assigned'");
    if ($stmt->execute([(int) $requestId, (int) $userId]) && $stmt->rowCount() > 0) {
        return ['success' => true, 'message' => 'Wemeje ko wabonye receipts.'];
    }
    return ['success' => false, 'message' => 'Ntibyashoboye kwemeza (reba ko receipt yahawe).'];
}

function getReceiptRequestById($pdo, $id) {
    ensureReceiptTables($pdo);
    $stmt = $pdo->prepare("SELECT rr.*, u.username, it.name AS itorero_name, i.name AS intara_name, i.id AS intara_id
        FROM receipt_requests rr
        JOIN users u ON rr.user_id = u.id
        JOIN itorero it ON rr.itorero_id = it.id
        JOIN intara i ON it.intara_id = i.id
        WHERE rr.id = ?");
    $stmt->execute([(int) $id]);
    return $stmt->fetch();
}

function getReceiptBooklets($pdo, $requestId) {
    ensureReceiptTables($pdo);
    $stmt = $pdo->prepare("SELECT * FROM receipt_booklets WHERE request_id = ? ORDER BY booklet_no ASC");
    $stmt->execute([(int) $requestId]);
    return $stmt->fetchAll();
}

function getReceiptRequestsForUser($pdo, $userId) {
    ensureReceiptTables($pdo);
    $stmt = $pdo->prepare("SELECT rr.*, it.name AS itorero_name, i.name AS intara_name
        FROM receipt_requests rr
        JOIN itorero it ON rr.itorero_id = it.id
        JOIN intara i ON it.intara_id = i.id
        WHERE rr.user_id = ?
        ORDER BY rr.created_at DESC");
    $stmt->execute([(int) $userId]);
    return $stmt->fetchAll();
}

function getPendingReceiptRequests($pdo) {
    ensureReceiptTables($pdo);
    return $pdo->query("SELECT rr.*, u.username, it.name AS itorero_name, i.name AS intara_name,
            admin_u.username AS requested_by_admin_name
        FROM receipt_requests rr
        JOIN users u ON rr.user_id = u.id
        JOIN itorero it ON rr.itorero_id = it.id
        JOIN intara i ON it.intara_id = i.id
        LEFT JOIN users admin_u ON rr.requested_by_admin_id = admin_u.id
        WHERE rr.status = 'pending'
        ORDER BY rr.created_at ASC")->fetchAll();
}

function getActiveReceiptRequests($pdo) {
    ensureReceiptTables($pdo);
    return $pdo->query("SELECT rr.*, u.username, it.name AS itorero_name, i.name AS intara_name
        FROM receipt_requests rr
        JOIN users u ON rr.user_id = u.id
        JOIN itorero it ON rr.itorero_id = it.id
        JOIN intara i ON it.intara_id = i.id
        WHERE rr.status IN ('assigned','acknowledged')
        ORDER BY rr.assigned_at DESC")->fetchAll();
}

/** Pastor confirmed receipt received (visible to admin). */
function getAcknowledgedReceiptRequests($pdo) {
    ensureReceiptTables($pdo);
    return $pdo->query("SELECT rr.*, u.username, it.name AS itorero_name, i.name AS intara_name,
            admin_u.username AS requested_by_admin_name
        FROM receipt_requests rr
        JOIN users u ON rr.user_id = u.id
        JOIN itorero it ON rr.itorero_id = it.id
        JOIN intara i ON it.intara_id = i.id
        LEFT JOIN users admin_u ON rr.requested_by_admin_id = admin_u.id
        WHERE rr.acknowledged_at IS NOT NULL
        ORDER BY rr.acknowledged_at DESC")->fetchAll();
}

function markReceiptBookletReturned($pdo, $bookletId, $via = 'admin_approve', array $returnMeta = []) {
    ensureReceiptTables($pdo);
    ensureReceiptBookletReturnColumns($pdo);
    $allowed = ['insert_data', 'admin_approve'];
    if (!in_array($via, $allowed, true)) {
        $via = 'admin_approve';
    }
    $comment = isset($returnMeta['return_admin_comment']) ? trim((string) $returnMeta['return_admin_comment']) : null;
    $allPages = array_key_exists('all_pages_returned', $returnMeta) ? (int) (bool) $returnMeta['all_pages_returned'] : null;
    $missingPages = isset($returnMeta['missing_pages']) ? trim((string) $returnMeta['missing_pages']) : null;
    if ($allPages === 1) {
        $missingPages = null;
    }

    $stmt = $pdo->prepare("UPDATE receipt_booklets SET
            returned_at = NOW(),
            returned_via = ?,
            return_admin_comment = ?,
            all_pages_returned = ?,
            missing_pages = ?
        WHERE id = ? AND returned_at IS NULL");
    $stmt->execute([$via, $comment !== '' ? $comment : null, $allPages, $missingPages !== '' ? $missingPages : null, (int) $bookletId]);
    if ($stmt->rowCount() > 0) {
        $b = $pdo->prepare("SELECT request_id FROM receipt_booklets WHERE id = ?");
        $b->execute([(int) $bookletId]);
        $row = $b->fetch();
        if ($row) {
            maybeCompleteReceiptRequest($pdo, (int) $row['request_id']);
        }
        return true;
    }
    return false;
}

function approveReceiptBookletReturn($pdo, $bookletId, array $returnMeta = []) {
    if (markReceiptBookletReturned($pdo, (int) $bookletId, 'admin_approve', $returnMeta)) {
        return ['success' => true, 'message' => 'Booklet yemejwe ko yagarutse.'];
    }
    return ['success' => false, 'message' => 'Ntibyashoboye kwemeza (reba ko booklet itaragarutse).'];
}

function consolidateOutstandingReceiptBooklets($pdo) {
    ensureReceiptTables($pdo);
    $ids = $pdo->query("SELECT DISTINCT rr.id FROM receipt_requests rr
        INNER JOIN receipt_booklets rb ON rb.request_id = rr.id
        WHERE rb.returned_at IS NULL AND rr.status IN ('assigned','acknowledged')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $requestId) {
        ensureSingleReceiptBooklet($pdo, (int) $requestId);
    }
}

function countOutstandingReceiptBooklets($pdo) {
    ensureReceiptTables($pdo);
    consolidateOutstandingReceiptBooklets($pdo);
    $stmt = $pdo->query("SELECT COUNT(*) FROM receipt_booklets rb
        INNER JOIN receipt_requests rr ON rb.request_id = rr.id
        WHERE rb.returned_at IS NULL AND rr.status IN ('assigned','acknowledged')");
    return (int) $stmt->fetchColumn();
}

function getOutstandingReceiptBookletsPaginated($pdo, $page = 1, $perPage = null) {
    ensureReceiptTables($pdo);
    consolidateOutstandingReceiptBooklets($pdo);
    $perPage = $perPage ?? (int) RECEIPT_BOOKLETS_PER_PAGE;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("SELECT rb.id AS booklet_id, rb.booklet_no, rb.range_start, rb.range_end,
            it.name AS itorero_name, it.id AS itorero_id,
            i.name AS intara_name, u.username AS pastor_name,
            rr.id AS request_id, rr.assigned_at
        FROM receipt_booklets rb
        INNER JOIN receipt_requests rr ON rb.request_id = rr.id
        INNER JOIN itorero it ON rr.itorero_id = it.id
        INNER JOIN intara i ON it.intara_id = i.id
        INNER JOIN users u ON rr.user_id = u.id
        WHERE rb.returned_at IS NULL AND rr.status IN ('assigned','acknowledged')
        ORDER BY it.name ASC, rb.booklet_no ASC
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['booklet_label'] = receiptBookletLabel($row['range_start'], $row['range_end']);
    }
    unset($row);

    return $rows;
}

function buildReceiptRequestPageUrl($gonePage = 1, $returnedPage = 1, $tab = 'gone') {
    $params = [];
    $gonePage = max(1, (int) $gonePage);
    $returnedPage = max(1, (int) $returnedPage);
    if ($tab === 'returned') {
        $params['tab'] = 'returned';
    }
    if ($gonePage > 1) {
        $params['gone_page'] = $gonePage;
    }
    if ($returnedPage > 1) {
        $params['returned_page'] = $returnedPage;
    }
    $query = http_build_query($params);
    return 'receipt-request.php' . ($query !== '' ? '?' . $query : '');
}

/** Base WHERE for receipt booklet reports (optional pastor user_id). */
function receiptBookletReportWhere($userId = null) {
    $where = "1=1";
    $params = [];
    if ($userId !== null) {
        $where .= " AND rr.user_id = ?";
        $params[] = (int) $userId;
    }
    return [$where, $params];
}

function countGoneReceiptBooklets($pdo, $userId = null) {
    ensureReceiptTables($pdo);
    consolidateOutstandingReceiptBooklets($pdo);
    [$where, $params] = receiptBookletReportWhere($userId);
    $sql = "SELECT COUNT(*) FROM receipt_booklets rb
        INNER JOIN receipt_requests rr ON rb.request_id = rr.id
        WHERE rb.returned_at IS NULL AND rr.status IN ('assigned','acknowledged')
        AND {$where}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function getGoneReceiptBookletsPaginated($pdo, $page = 1, $perPage = null, $userId = null) {
    ensureReceiptTables($pdo);
    consolidateOutstandingReceiptBooklets($pdo);
    $perPage = $perPage ?? (int) RECEIPT_BOOKLETS_PER_PAGE;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $perPage;
    [$where, $params] = receiptBookletReportWhere($userId);

    $sql = "SELECT rb.id AS booklet_id, rb.booklet_no, rb.range_start, rb.range_end,
            it.name AS itorero_name, it.id AS itorero_id,
            i.name AS intara_name, u.username AS pastor_name,
            rr.id AS request_id, rr.assigned_at, rr.requested_by_admin_id,
            admin_u.username AS requested_by_admin_name
        FROM receipt_booklets rb
        INNER JOIN receipt_requests rr ON rb.request_id = rr.id
        INNER JOIN itorero it ON rr.itorero_id = it.id
        INNER JOIN intara i ON it.intara_id = i.id
        INNER JOIN users u ON rr.user_id = u.id
        LEFT JOIN users admin_u ON rr.requested_by_admin_id = admin_u.id
        WHERE rb.returned_at IS NULL AND rr.status IN ('assigned','acknowledged')
        AND {$where}
        ORDER BY rr.assigned_at DESC, it.name ASC
        LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $idx = 1;
    foreach ($params as $p) {
        $stmt->bindValue($idx++, $p, PDO::PARAM_INT);
    }
    $stmt->bindValue($idx++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($idx, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['booklet_label'] = receiptBookletLabel($row['range_start'], $row['range_end']);
    }
    unset($row);
    return $rows;
}

function countReturnedReceiptBooklets($pdo, $userId = null) {
    ensureReceiptTables($pdo);
    [$where, $params] = receiptBookletReportWhere($userId);
    $sql = "SELECT COUNT(*) FROM receipt_booklets rb
        INNER JOIN receipt_requests rr ON rb.request_id = rr.id
        WHERE rb.returned_at IS NOT NULL AND {$where}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function getReturnedReceiptBookletsPaginated($pdo, $page = 1, $perPage = null, $userId = null) {
    ensureReceiptTables($pdo);
    $perPage = $perPage ?? (int) RECEIPT_BOOKLETS_PER_PAGE;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $perPage;
    [$where, $params] = receiptBookletReportWhere($userId);

    $sql = "SELECT rb.id AS booklet_id, rb.booklet_no, rb.range_start, rb.range_end,
            rb.returned_at, rb.returned_via, rb.return_admin_comment, rb.all_pages_returned, rb.missing_pages,
            it.name AS itorero_name, i.name AS intara_name, u.username AS pastor_name,
            rr.id AS request_id, rr.assigned_at, rr.requested_by_admin_id,
            admin_u.username AS requested_by_admin_name,
            approver.username AS approved_by_name
        FROM receipt_booklets rb
        INNER JOIN receipt_requests rr ON rb.request_id = rr.id
        INNER JOIN itorero it ON rr.itorero_id = it.id
        INNER JOIN intara i ON it.intara_id = i.id
        INNER JOIN users u ON rr.user_id = u.id
        LEFT JOIN users admin_u ON rr.requested_by_admin_id = admin_u.id
        LEFT JOIN users approver ON rr.assigned_by = approver.id
        WHERE rb.returned_at IS NOT NULL AND {$where}
        ORDER BY rb.returned_at DESC
        LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $idx = 1;
    foreach ($params as $p) {
        $stmt->bindValue($idx++, $p, PDO::PARAM_INT);
    }
    $stmt->bindValue($idx++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($idx, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['booklet_label'] = receiptBookletLabel($row['range_start'], $row['range_end']);
    }
    unset($row);
    return $rows;
}

function findBookletForReceiptNumber($pdo, $requestId, $number) {
    ensureReceiptTables($pdo);
    $number = (int) $number;
    $stmt = $pdo->prepare("SELECT * FROM receipt_booklets
        WHERE request_id = ? AND range_start <= ? AND range_end >= ?
        ORDER BY booklet_no ASC LIMIT 1");
    $stmt->execute([(int) $requestId, $number, $number]);
    return $stmt->fetch();
}

function maybeCompleteReceiptRequest($pdo, $requestId) {
    $status = getReceiptReturnStatus($pdo, $requestId);
    if ($status['all_returned']) {
        $stmt = $pdo->prepare("UPDATE receipt_requests SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([(int) $requestId]);
    }
}

function getReceiptReturnStatus($pdo, $requestId) {
    ensureReceiptTables($pdo);
    $req = getReceiptRequestById($pdo, $requestId);
    $booklets = getReceiptBooklets($pdo, $requestId);

    if ($req && $req['range_start'] !== null && empty($booklets)) {
        createReceiptBookletsForRequest($pdo, $requestId, $req['range_start'], $req['range_end']);
        $booklets = getReceiptBooklets($pdo, $requestId);
    }

    if ($req && $req['range_start'] !== null) {
        ensureSingleReceiptBooklet($pdo, $requestId);
        $booklets = getReceiptBooklets($pdo, $requestId);
    }

    if (!$req || empty($booklets)) {
        return [
            'total' => 0,
            'returned' => 0,
            'missing_booklets' => [],
            'all_returned' => true,
            'range_label' => '',
            'booklets' => [],
        ];
    }

    $missing = [];
    $returned = 0;
    $list = [];
    foreach ($booklets as $b) {
        $label = receiptBookletLabel($b['range_start'], $b['range_end']);
        $isReturned = !empty($b['returned_at']);
        if ($isReturned) {
            $returned++;
        } else {
            $missing[] = [
                'booklet_no' => (int) $b['booklet_no'],
                'label' => $label,
                'range_start' => (int) $b['range_start'],
                'range_end' => (int) $b['range_end'],
            ];
        }
        $list[] = [
            'booklet_no' => (int) $b['booklet_no'],
            'label' => $label,
            'returned' => $isReturned,
        ];
    }

    return [
        'total' => count($booklets),
        'returned' => $returned,
        'missing_booklets' => $missing,
        'all_returned' => count($missing) === 0,
        'range_label' => receiptRangeLabel($req['range_start'], $req['range_end']),
        'booklets' => $list,
    ];
}

function getActiveReceiptRequestForItorero($pdo, $itoreroId) {
    ensureReceiptTables($pdo);
    $stmt = $pdo->prepare("SELECT * FROM receipt_requests
        WHERE itorero_id = ? AND status IN ('assigned','acknowledged')
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([(int) $itoreroId]);
    return $stmt->fetch();
}

function getItoreroReceiptWarning($pdo, $itoreroId) {
    $req = getActiveReceiptRequestForItorero($pdo, $itoreroId);
    if (!$req) {
        return null;
    }
    $status = getReceiptReturnStatus($pdo, $req['id']);
    if ($status['all_returned']) {
        return null;
    }
    $missingLabels = array_map(fn($m) => $m['label'], $status['missing_booklets']);
    return [
        'request_id' => (int) $req['id'],
        'itorero_id' => (int) $itoreroId,
        'range_label' => $status['range_label'],
        'missing_booklets' => $status['missing_booklets'],
        'missing_labels' => $missingLabels,
        'missing_count' => count($status['missing_booklets']),
        'total' => $status['total'],
        'returned' => $status['returned'],
    ];
}

/** Admin INSERT DATA: mark one booklet returned when Lesi matches a number in that booklet. */
function processLesiReceiptReturn($pdo, $itoreroId, $lesi) {
    if (!$itoreroId || trim($lesi) === '') {
        return null;
    }
    $req = getActiveReceiptRequestForItorero($pdo, $itoreroId);
    if (!$req) {
        return null;
    }

    $lesi = trim($lesi);
    $numbers = [];

    if (preg_match('/^\d+$/', $lesi)) {
        $numbers[] = (int) $lesi;
    } elseif (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/i', $lesi, $m)) {
        $a = (int) $m[1];
        $b = (int) $m[2];
        if ($a > $b) {
            [$a, $b] = [$b, $a];
        }
        $numbers[] = $a;
        $numbers[] = $b;
    } else {
        return null;
    }

    $marked = [];
    foreach ($numbers as $num) {
        $booklet = findBookletForReceiptNumber($pdo, $req['id'], $num);
        if ($booklet && empty($booklet['returned_at'])) {
            markReceiptBookletReturned($pdo, (int) $booklet['id']);
            $marked[] = receiptBookletLabel($booklet['range_start'], $booklet['range_end']);
        }
    }
    return $marked;
}

function getItoreroWithOutstandingReceipts($pdo, $intaraId = null) {
    ensureReceiptTables($pdo);
    $sql = "SELECT DISTINCT rr.itorero_id, it.name AS itorero_name, i.name AS intara_name, rr.id AS request_id,
            rr.range_start, rr.range_end
        FROM receipt_requests rr
        JOIN itorero it ON rr.itorero_id = it.id
        JOIN intara i ON it.intara_id = i.id
        WHERE rr.status IN ('assigned','acknowledged')";
    $params = [];
    if ($intaraId) {
        $sql .= " AND it.intara_id = ?";
        $params[] = (int) $intaraId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $st = getReceiptReturnStatus($pdo, $row['request_id']);
        if (!$st['all_returned']) {
            $row['missing_count'] = count($st['missing_booklets']);
            $row['range_label'] = $st['range_label'];
            $row['missing_labels'] = array_map(fn($m) => $m['label'], $st['missing_booklets']);
            $rows[] = $row;
        }
    }
    return $rows;
}
