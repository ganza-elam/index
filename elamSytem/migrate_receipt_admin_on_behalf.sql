-- Admin can request receipt booklet on behalf of a pastor (guest)
ALTER TABLE receipt_requests
  ADD COLUMN IF NOT EXISTS requested_by_admin_id int(11) DEFAULT NULL AFTER user_id;

-- MySQL < 8.0: run manually if IF NOT EXISTS fails:
-- ALTER TABLE receipt_requests ADD COLUMN requested_by_admin_id int(11) DEFAULT NULL AFTER user_id;
-- ALTER TABLE receipt_requests ADD KEY idx_receipt_req_admin (requested_by_admin_id);
