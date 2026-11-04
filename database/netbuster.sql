-- Net Busters database (InfinityFree version)
-- Select your database (if0_xxxxxxxx_...) in phpMyAdmin first, then Import.
-- Default logins: admin / password   and   juan / password  (change after first login)

DROP TABLE IF EXISTS tbl_notify;
DROP TABLE IF EXISTS tbl_transactions;
DROP TABLE IF EXISTS tbl_order_items;
DROP TABLE IF EXISTS tbl_orders;
DROP TABLE IF EXISTS tbl_plans;
DROP TABLE IF EXISTS tbl_users;

CREATE TABLE tbl_users (
  user_id     INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,              
  full_name   VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL UNIQUE,
  role        ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tbl_plans (
  plan_id      INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,                     
  plan_name    VARCHAR(100) NOT NULL,
  category     ENUM('Fiber','DSL','Wireless','Business','Add-on') NOT NULL,
  description  TEXT,
  speed_mbps   INT NOT NULL DEFAULT 0,
  price        DECIMAL(10,2) NOT NULL,
  slots        INT NOT NULL DEFAULT 0,          
  status       ENUM('Available','Unavailable') NOT NULL DEFAULT 'Available',
  date_added   DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_orders (
  order_id        INT AUTO_INCREMENT PRIMARY KEY,
  order_no        VARCHAR(30) NOT NULL UNIQUE,
  user_id         INT NOT NULL,
  setup_type      ENUM('Standard','Priority') NOT NULL DEFAULT 'Standard',
  setup_fee       DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal        DECIMAL(10,2) NOT NULL,
  total           DECIMAL(10,2) NOT NULL,
  address         VARCHAR(255) NOT NULL,
  city            VARCHAR(100) NOT NULL,
  contact_no      VARCHAR(20)  NOT NULL,
  devices         INT NOT NULL DEFAULT 1,
  notes           VARCHAR(255) DEFAULT NULL,
  payment_method  ENUM('Card','GCash','Maya','Cash') NOT NULL,
  payment_ref     VARCHAR(30) DEFAULT NULL,      
  payment_status  ENUM('Paid','Pending') NOT NULL DEFAULT 'Pending',
  status          ENUM('Pending','Technician Assigned','Activated','Cancelled') NOT NULL DEFAULT 'Pending',
  technician      VARCHAR(100) DEFAULT NULL,
  tracking_alerts TINYINT(1) NOT NULL DEFAULT 0,
  est_date        DATE NOT NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_order_items (
  item_id    INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  plan_id    INT NOT NULL,
  qty        INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id)  REFERENCES tbl_plans(plan_id)   ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tbl_notify (
  notify_id  INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  plan_id    INT NOT NULL,
  notified   TINYINT(1) NOT NULL DEFAULT 0,
  seen       TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_plan (user_id, plan_id),
  FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES tbl_plans(plan_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_transactions (
  transaction_id   INT AUTO_INCREMENT PRIMARY KEY,
  record_id        INT DEFAULT NULL,            
  user_id          INT DEFAULT NULL,
  action_type      VARCHAR(30) NOT NULL,         
  remarks          VARCHAR(255) DEFAULT NULL,
  transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (record_id) REFERENCES tbl_plans(plan_id) ON DELETE SET NULL,
  FOREIGN KEY (user_id)   REFERENCES tbl_users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;


INSERT INTO tbl_users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Net Busters Admin', 'admin@netbusters.test', 'admin'),
('juan',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz',   'juan@example.com',      'user');

INSERT INTO tbl_plans (user_id, plan_name, category, description, speed_mbps, price, slots, status) VALUES
(1,'Starter Fiber 50','Fiber','Great for browsing, video calls and a small household. Unlimited data, free modem.',50,999.00,25,'Available'),
(1,'Home Fiber 100','Fiber','Smooth HD streaming and online classes for up to 6 devices. Unlimited data, dual-band router.',100,1499.00,30,'Available'),
(1,'Gamer Fiber 300','Fiber','Low-latency fiber for gaming and 4K streaming. Priority routing, Wi-Fi 6 router included.',300,2299.00,12,'Available'),
(1,'Ultra Fiber 1000','Fiber','Gigabit speeds for power users and smart homes. Wi-Fi 6 mesh included.',1000,3999.00,0,'Available'),
(1,'Basic DSL 20','DSL','Budget-friendly connection using existing phone lines for light browsing.',20,699.00,40,'Available'),
(1,'Wireless Lite 30','Wireless','Fixed-wireless service for areas without fiber coverage. Quick install.',30,899.00,18,'Available'),
(1,'Wireless Plus 60','Wireless','Faster fixed-wireless with outdoor antenna and static signal boost.',60,1299.00,10,'Available'),
(1,'Business Pro 200','Business','Symmetrical 200 Mbps with a static IP, 99.5% uptime SLA and business support line.',200,4999.00,8,'Available'),
(1,'Business Dedicated 500','Business','Dedicated line for offices, 500 Mbps symmetrical, 4-hour response SLA.',500,9999.00,5,'Unavailable'),
(1,'Wi-Fi 6 Mesh Pack','Add-on','2-node mesh system that eliminates dead spots in large homes.',0,1800.00,50,'Available'),
(1,'Static IP Address','Add-on','Dedicated public IPv4 address for CCTV, servers and remote work.',0,500.00,100,'Available');

INSERT INTO tbl_transactions (record_id, user_id, action_type, remarks) VALUES
(NULL, 1, 'setup', 'Database imported with seed plans');