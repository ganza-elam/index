USE elam_system;

CREATE TABLE IF NOT EXISTS receipt_stock (
  id int(11) NOT NULL AUTO_INCREMENT,
  range_start int(11) NOT NULL,
  range_end int(11) NOT NULL,
  notes varchar(255) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS receipt_requests (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS receipt_returns (
  id int(11) NOT NULL AUTO_INCREMENT,
  request_id int(11) NOT NULL,
  receipt_number int(11) NOT NULL,
  returned_via enum('insert_data','manual') NOT NULL DEFAULT 'manual',
  returned_at datetime DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uq_receipt_return (request_id, receipt_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
