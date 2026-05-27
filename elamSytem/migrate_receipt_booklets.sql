USE elam_system;

CREATE TABLE IF NOT EXISTS receipt_booklets (
  id int(11) NOT NULL AUTO_INCREMENT,
  request_id int(11) NOT NULL,
  booklet_no int(11) NOT NULL,
  range_start int(11) NOT NULL,
  range_end int(11) NOT NULL,
  returned_at datetime DEFAULT NULL,
  returned_via enum('insert_data') DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_receipt_booklet (request_id, booklet_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
