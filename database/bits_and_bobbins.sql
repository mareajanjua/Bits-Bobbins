-- ============================================================
-- Retail Dealer E-Commerce Application â€” Database Schema
-- Engine: MySQL 8.x (InnoDB). Adjust types if using Postgres/SQL Server.
-- Cart is intentionally NOT modeled here â€” it lives in session
-- storage and only becomes persistent rows at checkout.
-- ============================================================

-- ---------- ADMIN ----------
CREATE TABLE admin (
    admin_id        INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    profile_photo   VARCHAR(255),
    shop_name       VARCHAR(150),
    shop_address    TEXT,
    notify_returns          BOOLEAN DEFAULT TRUE,
    notify_failed_payments  BOOLEAN DEFAULT TRUE,
    notify_low_stock        BOOLEAN DEFAULT TRUE,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO admin (
    username,
    email,
    password_hash,
    shop_name,
    shop_address,
    notify_returns,
    notify_failed_payments,
    notify_low_stock
) VALUES (
    'admin',
    'admin@gmail.com',
    '$2y$10$yHxuvV4/aABm2niHKWSNo.fOByHZpT1Zy6jdX0P6ocOp8VjEFJSqy',
    'Bits&Bobbins',
    '',
    TRUE,
    TRUE,
    TRUE
);

-- ---------- EMPLOYEE (login by email, admin-created, self password change only) ----------
CREATE TABLE employee (
    employee_id     INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20),
    profile_photo   VARCHAR(255),
    status          ENUM('active','inactive') DEFAULT 'active',
    created_by      INT NOT NULL,               -- admin_id
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(admin_id)
) ENGINE=InnoDB;

-- ---------- CUSTOMER ----------
CREATE TABLE customer (
    customer_id     INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20),
    profile_photo   VARCHAR(255),
    registered_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE customer_address (
    address_id      INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    address_line1   VARCHAR(150) NOT NULL,
    address_line2   VARCHAR(150),
    city            VARCHAR(80)  NOT NULL,
    state           VARCHAR(80)  NOT NULL,
    postal_code     VARCHAR(15)  NOT NULL,
    is_default      BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- CATEGORY (2-digit product code) ----------
CREATE TABLE category (
    category_code   CHAR(2) PRIMARY KEY,     -- e.g. '01'
    category_name   VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------- SUBCATEGORY (optional under category) ----------
CREATE TABLE subcategory (
    subcategory_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_code     CHAR(2) NOT NULL,
    subcategory_name  VARCHAR(80) NOT NULL,
    FOREIGN KEY (category_code) REFERENCES category(category_code) ON DELETE CASCADE,
    UNIQUE (category_code, subcategory_name)
) ENGINE=InnoDB;

-- ---------- PRODUCT (7-digit product_id = code(2) + number(5)) ----------
CREATE TABLE product (
    product_id      CHAR(7) PRIMARY KEY,      -- category_code || product_number
    category_code   CHAR(2) NOT NULL,
    subcategory_id  BIGINT UNSIGNED NULL,
    product_number  CHAR(5) NOT NULL,
    product_name    VARCHAR(150) NOT NULL,
    description     TEXT,
    price           DECIMAL(10,2) NOT NULL,
    image_front     VARCHAR(255) NULL,
    image_hover     VARCHAR(255) NULL,
    has_warranty    BOOLEAN DEFAULT FALSE,
    warranty_months INT NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_code) REFERENCES category(category_code),
    FOREIGN KEY (subcategory_id) REFERENCES subcategory(subcategory_id),
    UNIQUE (category_code, product_number),
    FULLTEXT (product_name, description)      -- advanced search requirement
) ENGINE=InnoDB;

CREATE INDEX idx_product_price ON product(price);

-- ---------- PRODUCT DETAIL (database-backed product detail accordions) ----------
CREATE TABLE product_detail (
    detail_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id     CHAR(7) NOT NULL,
    title          VARCHAR(160) NOT NULL,
    body           TEXT NOT NULL,
    display_order  INT NOT NULL DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_product_detail_product ON product_detail(product_id);

-- ---------- STOCK ----------
CREATE TABLE stock (
    product_id          CHAR(7) PRIMARY KEY,
    quantity_available  INT NOT NULL DEFAULT 0,
    last_restocked_at   DATETIME,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- DELIVERY TYPE (1-digit code) ----------
CREATE TABLE delivery_type (
    delivery_code   CHAR(1) PRIMARY KEY,     -- e.g. '1'=Courier,'2'=VPP,'3'=Registered Post
    delivery_name   VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

INSERT INTO delivery_type (delivery_code, delivery_name) VALUES
('1', 'Courier'),
('2', 'VPP'),
('3', 'Registered Post');

-- ---------- ORDER HEADER ----------
CREATE TABLE orders (
    order_id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    customer_id         INT NOT NULL,
    shipping_address_id INT NOT NULL,
    order_date          DATETIME DEFAULT CURRENT_TIMESTAMP,
    order_status        ENUM('placed','payment_pending','payment_cleared',
                              'dispatched','delivered','cancelled') DEFAULT 'placed',
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id),
    FOREIGN KEY (shipping_address_id) REFERENCES customer_address(address_id)
) ENGINE=InnoDB;

CREATE INDEX idx_orders_date ON orders(order_date);

-- ---------- ORDER ITEM (this is where the 16-digit order number lives) ----------
CREATE TABLE order_item (
    order_item_id   BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT NOT NULL,
    product_id      CHAR(7) NOT NULL,
    delivery_code   CHAR(1) NOT NULL,
    item_sequence   CHAR(8) NOT NULL,          -- app-generated running number
    order_number    CHAR(16) GENERATED ALWAYS AS
                        (CONCAT(delivery_code, product_id, item_sequence)) STORED UNIQUE,
    quantity        INT NOT NULL DEFAULT 1,
    unit_price      DECIMAL(10,2) NOT NULL,    -- price captured at time of order
    item_status     ENUM('placed','payment_pending','payment_cleared',
                          'dispatched','delivered','cancelled',
                          'return_requested','returned','replace_requested','replaced')
                     DEFAULT 'placed',
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id),
    FOREIGN KEY (delivery_code) REFERENCES delivery_type(delivery_code)
) ENGINE=InnoDB;

CREATE INDEX idx_orderitem_status ON order_item(item_status);
CREATE INDEX idx_orderitem_delivery ON order_item(delivery_code);

-- ---------- PAYMENT ----------
CREATE TABLE payment (
    payment_id      BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT NOT NULL,
    payment_method  ENUM('credit_card','cheque','vpp_cod','dd') NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_status  ENUM('pending','cleared','failed') DEFAULT 'pending',
    payment_date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Never store a full card number â€” PCI-DSS. Keep only a gateway token/ref + last 4.
CREATE TABLE payment_credit_card (
    payment_id       BIGINT PRIMARY KEY,
    card_last4       CHAR(4) NOT NULL,
    card_holder_name VARCHAR(100) NOT NULL,
    gateway_txn_ref  VARCHAR(100) NOT NULL,
    auth_code        VARCHAR(50),
    FOREIGN KEY (payment_id) REFERENCES payment(payment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payment_cheque (
    payment_id       BIGINT PRIMARY KEY,
    cheque_number    VARCHAR(30) NOT NULL,
    bank_name        VARCHAR(100) NOT NULL,
    cheque_date      DATE NOT NULL,
    clearance_date   DATE,
    FOREIGN KEY (payment_id) REFERENCES payment(payment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payment_dd (
    payment_id       BIGINT PRIMARY KEY,
    dd_number        VARCHAR(30) NOT NULL,
    bank_name        VARCHAR(100) NOT NULL,
    dd_date          DATE NOT NULL,
    clearance_date   DATE,
    FOREIGN KEY (payment_id) REFERENCES payment(payment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- DISPATCH / DELIVERY TRACKING (per order_item) ----------
CREATE TABLE dispatch (
    dispatch_id             BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_item_id           BIGINT NOT NULL,
    dispatched_by           INT NOT NULL,        -- employee_id
    dispatch_date            DATETIME,
    expected_delivery_date  DATE,
    actual_delivery_date    DATE,
    courier_tracking_number VARCHAR(60),
    FOREIGN KEY (order_item_id) REFERENCES order_item(order_item_id) ON DELETE CASCADE,
    FOREIGN KEY (dispatched_by) REFERENCES employee(employee_id)
) ENGINE=InnoDB;

-- ---------- WARRANTY ----------
CREATE TABLE warranty_card (
    warranty_id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_item_id       BIGINT NOT NULL UNIQUE,
    warranty_start_date DATE NOT NULL,
    warranty_end_date   DATE NOT NULL,
    terms               TEXT,
    FOREIGN KEY (order_item_id) REFERENCES order_item(order_item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- RETURN / REPLACE (7-day window enforced in application logic
--            against dispatch.actual_delivery_date) ----------
CREATE TABLE return_replace_request (
    request_id      BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_item_id   BIGINT NOT NULL,
    request_type    ENUM('return','replace') NOT NULL,
    request_date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    reason          TEXT,
    status          ENUM('requested','approved','rejected','completed') DEFAULT 'requested',
    refund_amount   DECIMAL(10,2),
    processed_by    INT,                 -- employee_id
    resolved_date   DATETIME,
    FOREIGN KEY (order_item_id) REFERENCES order_item(order_item_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES employee(employee_id)
) ENGINE=InnoDB;

-- ---------- FEEDBACK ----------
CREATE TABLE feedback (
    feedback_id     BIGINT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    order_id        BIGINT NULL,
    product_id      CHAR(7) NULL,
    message         TEXT NOT NULL,
    rating          TINYINT CHECK (rating BETWEEN 1 AND 5),
    submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at     DATETIME NULL,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id),
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES product(product_id)
) ENGINE=InnoDB;

-- ---------- FAQ ----------
CREATE TABLE faq (
    faq_id          INT AUTO_INCREMENT PRIMARY KEY,
    question        VARCHAR(255) NOT NULL,
    answer          TEXT NOT NULL,
    created_by      INT NOT NULL,        -- admin_id
    display_order   INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES admin(admin_id)
) ENGINE=InnoDB;

-- ---------- DEMO DATA SEED (exported from local bitsandbobbins) ----------
-- These rows make a fresh database demo-ready.
-- Login passwords:
-- Admin: admin@gmail.com / admin123
-- Employees: employee123
-- Customers: customer123
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT INTO `employee` (`employee_id`, `email`, `password_hash`, `full_name`, `phone`, `status`, `created_by`, `created_at`, `profile_photo`) VALUES (1,'employee@gmail.com','$2y$10$OJ/zOfofMA4esm19tFRYyepJlyNbVvfarOhx2MZUeZp9Jxg7DUR9a','Employee','03000000000','active',1,'2026-09-16 06:25:35',NULL);
INSERT INTO `employee` (`employee_id`, `email`, `password_hash`, `full_name`, `phone`, `status`, `created_by`, `created_at`, `profile_photo`) VALUES (2,'saratayyab@gmail.com','$2y$10$OJ/zOfofMA4esm19tFRYyepJlyNbVvfarOhx2MZUeZp9Jxg7DUR9a','SaraTayyab','03307051333','active',1,'2026-09-16 14:07:18','assets/dashboard/uploads/employees/employee-2.jfif');
INSERT INTO `employee` (`employee_id`, `email`, `password_hash`, `full_name`, `phone`, `status`, `created_by`, `created_at`, `profile_photo`) VALUES (3,'mareaajanjua@gmail.com','$2y$10$OJ/zOfofMA4esm19tFRYyepJlyNbVvfarOhx2MZUeZp9Jxg7DUR9a','Marea Janjua',NULL,'active',1,'2026-09-22 05:47:04',NULL);

INSERT INTO `customer` (`customer_id`, `email`, `password_hash`, `full_name`, `phone`, `profile_photo`, `registered_at`, `status`) VALUES (1,'fouziaali@gmail.com','$2y$10$ZsE6cla/oZvtj3CKV5v88.RZlLYOhzLeKosVgJTSq48vsfjpPJHLe','FOUZIA ALI',NULL,'assets/frontend/uploads/customers/customer-1.jfif','2026-09-21 10:20:05','active');
INSERT INTO `customer` (`customer_id`, `email`, `password_hash`, `full_name`, `phone`, `profile_photo`, `registered_at`, `status`) VALUES (2,'hadiali@gmail.com','$2y$10$ZsE6cla/oZvtj3CKV5v88.RZlLYOhzLeKosVgJTSq48vsfjpPJHLe','Hadi ali',NULL,'assets/frontend/uploads/customers/customer-2.jfif','2026-09-21 18:10:35','active');
INSERT INTO `customer` (`customer_id`, `email`, `password_hash`, `full_name`, `phone`, `profile_photo`, `registered_at`, `status`) VALUES (3,'shumailaAfnan@gmail.com','$2y$10$ZsE6cla/oZvtj3CKV5v88.RZlLYOhzLeKosVgJTSq48vsfjpPJHLe','Shumaila Afnan',NULL,'assets/frontend/uploads/customers/customer-3.jfif','2026-09-21 18:20:43','active');
INSERT INTO `customer` (`customer_id`, `email`, `password_hash`, `full_name`, `phone`, `profile_photo`, `registered_at`, `status`) VALUES (4,'RidaTariq@gmail.com','$2y$10$ZsE6cla/oZvtj3CKV5v88.RZlLYOhzLeKosVgJTSq48vsfjpPJHLe','Rida Tariq',NULL,'assets/frontend/uploads/customers/customer-4.jfif','2026-09-21 18:25:58','active');
INSERT INTO `customer` (`customer_id`, `email`, `password_hash`, `full_name`, `phone`, `profile_photo`, `registered_at`, `status`) VALUES (5,'Abdulmannan@gmail.com','$2y$10$ZsE6cla/oZvtj3CKV5v88.RZlLYOhzLeKosVgJTSq48vsfjpPJHLe','Abdul mannan',NULL,'assets/frontend/uploads/customers/customer-5.jfif','2026-09-22 12:17:53','active');
INSERT INTO `customer` (`customer_id`, `email`, `password_hash`, `full_name`, `phone`, `profile_photo`, `registered_at`, `status`) VALUES (6,'umarjanjua@gmail.com','$2y$10$ZsE6cla/oZvtj3CKV5v88.RZlLYOhzLeKosVgJTSq48vsfjpPJHLe','umar janjua',NULL,'assets/frontend/uploads/customers/customer-6.jfif','2026-09-22 12:21:32','active');

INSERT INTO `category` (`category_code`, `category_name`) VALUES ('02','Art&Craft');
INSERT INTO `category` (`category_code`, `category_name`) VALUES ('06','Bags&Wallets');
INSERT INTO `category` (`category_code`, `category_name`) VALUES ('04','Beauty & Skincare');
INSERT INTO `category` (`category_code`, `category_name`) VALUES ('01','Dolls & Accessories');
INSERT INTO `category` (`category_code`, `category_name`) VALUES ('03','Gifts&Stationary');
INSERT INTO `category` (`category_code`, `category_name`) VALUES ('05','Kids (General & Lifestyle)');

INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (13,'01','Diverse & Inclusive Dolls');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (11,'01','Dollhouses');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (14,'01','Roleplay Sets');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (17,'02','Eco Play');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (15,'02','Mess-Free Creativity');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (16,'02','Skill Building');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (20,'03','Digital Gift Cards');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (19,'03','Gifting Essentials');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (18,'03','Organization & Desk Decor');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (22,'04','Clean Cosmetics');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (21,'04','Gentle Skincare');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (23,'04','Tools & Accessories');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (26,'05','Everyday Travel');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (25,'05','Room Decor & Comfort');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (24,'05','Tech & STEM');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (28,'06','Everyday Bags');
INSERT INTO `subcategory` (`subcategory_id`, `category_code`, `subcategory_name`) VALUES (27,'06','Wallets & Organizers');

INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0100001','01',11,'00001','Mini Bedroom Furniture Set','A miniature bed, wardrobe, side table and accessories made to furnish a favourite dollhouse room.',10500.00,'assets/store/products/0100001-image-front-1789970268.jfif','assets/store/products/0100001-image-hover-1789970268.jfif',1,3,1,'2026-09-17 04:39:22','2026-09-21 05:57:48');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0100002','01',13,'00002','Curly Hair Fashion Doll','A stylish doll with textured curly hair, removable outfit and accessories for imaginative styling play',13000.00,'assets/store/products/0100002-image-front-1789969705.jfif','assets/store/products/0100002-image-hover-1789969705.jfif',1,3,1,'2026-09-17 04:41:27','2026-09-21 05:48:25');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0100003','01',11,'00003','Wooden Pastel Dollhouse','A charming multi-room wooden dollhouse with soft pastel details made for creative everyday play.',12000.00,'assets/store/products/0100003-image-front-1789970121.jfif','assets/store/products/0100003-image-hover-1789970121.jfif',1,3,1,'2026-09-17 04:43:30','2026-09-21 05:55:21');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0100004','01',13,'00004','Multicultural Everyday Doll','A beautifully detailed everyday doll designed to encourage inclusive and imaginative storytelling.',13500.00,'assets/store/products/0100004-image-front-1789969900.jfif','assets/store/products/0100004-image-hover-1789969900.jfif',1,3,1,'2026-09-17 04:49:31','2026-09-21 05:51:40');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0100005','01',14,'00005','Mini Café & Bakery Play Set','A pretend café set with pastries, serving pieces and accessories for creating a tiny bakery at home.',10000.00,'assets/store/products/0100005-image-front-1789970419.jfif','assets/store/products/0100005-image-hover-1789970419.jfif',1,4,1,'2026-09-21 06:00:19','2026-09-21 06:00:19');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0100006','01',14,'00006','Little Doctor Roleplay Set','A playful medical kit with pretend tools that encourages imaginative doctor and patient roleplay',6000.00,'assets/store/products/0100006-image-front-1789970588.jfif','assets/store/products/0100006-image-hover-1789970588.jfif',1,4,1,'2026-09-21 06:03:08','2026-09-21 06:03:08');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200001','02',17,'00001','Plant & Paint Mini Garden Kit','A hands-on mini gardening kit that lets little makers paint, plant and grow their own tiny garden.',4000.00,'assets/store/products/0200001-image-front-1789803439.jfif','assets/store/products/0200001-image-hover-1789803439.jfif',1,3,1,'2026-09-19 07:37:19','2026-09-19 07:37:19');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200002','02',17,'00002','Recycled Paper Craft Box','A colourful collection of recycled papers, shapes and craft essentials made for open-ended creative play.',3500.00,'assets/store/products/0200002-image-front-1789805005.jfif','assets/store/products/0200002-image-hover-1789805005.jfif',1,0,1,'2026-09-19 08:03:25','2026-09-19 08:03:25');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200003','02',17,'00003','Wooden Animal Painting Set','Smooth wooden animal figures with paints and brushes for an easy afternoon art activity.',5500.00,'assets/store/products/0200003-image-front-1789805300.jfif','assets/store/products/0200003-image-hover-1789805300.jfif',1,3,1,'2026-09-19 08:08:20','2026-09-19 08:08:20');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200004','02',15,'00004','Water Reveal Coloring Book','Reusable colouring pages that magically reveal colours using only water no paint, stains or cleanup.',5400.00,'assets/store/products/0200004-image-front-1789968466.jfif','assets/store/products/0200004-image-hover-1789968466.jfif',1,0,1,'2026-09-21 05:27:46','2026-09-21 05:27:46');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200005','02',15,'00005','Reusable Magic Doodle Mat','A large reusable drawing mat for endless doodling using water-filled pens.',3400.00,'assets/store/products/0200005-image-front-1789968690.jfif','assets/store/products/0200005-image-hover-1789968690.jfif',1,3,1,'2026-09-21 05:31:30','2026-09-21 05:31:30');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200006','02',15,'00006','No-Spill Paint Stick Set','Smooth twist-up paint sticks that give kids bold colour without brushes, water or messy paint pots.',7600.00,'assets/store/products/0200006-image-front-1789968878.jfif','assets/store/products/0200006-image-hover-1789968878.jfif',1,4,1,'2026-09-21 05:34:38','2026-09-21 05:34:38');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200007','02',15,'00007','Peel & Stick Mosaic Kit','An easy sticker mosaic activity set designed to build creativity, pattern recognition and focus.',5600.00,'assets/store/products/0200007-image-front-1789969020.jfif','assets/store/products/0200007-image-hover-1789969021.jfif',1,3,1,'2026-09-21 05:37:01','2026-09-21 05:37:01');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200008','02',16,'00008','Beginner Crochet Kit','A complete starter set with yarn, hook and easy instructions for learning basic crochet techniques..',4500.00,'assets/store/products/0200008-image-front-1789969171.jfif','assets/store/products/0200008-image-hover-1789969171.jfif',1,5,1,'2026-09-21 05:39:31','2026-09-21 05:39:31');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200009','02',16,'00009','Friendship Bracelet Maker Set','Colourful threads, beads and tools for designing personalised friendship bracelets.',1200.00,'assets/store/products/0200009-image-front-1789969387.jfif','assets/store/products/0200009-image-hover-1789969387.jfif',1,3,1,'2026-09-21 05:43:07','2026-09-21 05:43:07');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0200010','02',16,'00010','Clay Sculpting Starter Set','Soft modelling clay and beginner tools for shaping mini figures, charms and creative objects',4500.00,'assets/store/products/0200010-image-front-1789969565.jfif','assets/store/products/0200010-image-hover-1789969565.jfif',1,3,1,'2026-09-21 05:46:05','2026-09-21 05:46:05');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300001','03',20,'00001','Digital Gift Card','A digital gift card delivered electronically for when you want them to choose exactly what they love.',12000.00,'assets/store/products/0300001-image-front-1789970881.jfif','assets/store/products/0300001-image-hover-1789970881.jfif',0,NULL,1,'2026-09-21 06:08:01','2026-09-21 06:08:01');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300002','03',20,'00002','Birthday digital card','A flexible digital gift for birthdays, celebrations or thoughtful little surprises.',1200.00,'assets/store/products/0300002-image-front-1789971099.jfif','assets/store/products/0300002-image-hover-1789971099.jfif',0,NULL,1,'2026-09-21 06:11:39','2026-09-21 06:11:39');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300003','03',19,'00003','Celebration Gift Box','A premium reusable gift box designed to make birthdays and special moments feel extra thoughtful',2000.00,'assets/store/products/0300003-image-front-1789971208.jfif','assets/store/products/0300003-image-hover-1789971208.jfif',0,NULL,1,'2026-09-21 06:13:28','2026-09-21 06:13:28');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300004','03',19,'00004','Gift Wrap Set','Coordinating wrapping paper, ribbons and tags for turning any present into something special.',2000.00,'assets/store/products/0300004-image-front-1789971746.jfif','assets/store/products/0300004-image-hover-1789971746.jfif',0,NULL,1,'2026-09-21 06:22:26','2026-09-21 06:22:26');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300005','03',19,'00005','Mini Greeting Card Bundle','A set of illustrated mini cards for birthdays, thank-yous and little everyday messages.',4500.00,'assets/store/products/0300005-image-front-1789971896.jfif','assets/store/products/0300005-image-hover-1789971896.jfif',0,NULL,1,'2026-09-21 06:24:56','2026-09-21 06:24:56');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300006','03',18,'00006','Weekly Planner','A clean multi-compartment organizer for stationery, notes and everyday desk essentials.',9699.86,'assets/store/products/0300006-image-front-1789972187.jfif','assets/store/products/0300006-image-hover-1789972187.jfif',0,NULL,1,'2026-09-21 06:27:20','2026-09-21 06:29:47');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300007','03',18,'00007','Acrylic Desktop Organizer','A clean multi-compartment organizer for stationery, notes and everyday desk essentials.',10000.00,'assets/store/products/0300007-image-front-1789972041.jfif','assets/store/products/0300007-image-hover-1789972041.jfif',1,3,1,'2026-09-21 06:27:21','2026-09-21 06:27:21');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0300008','03',18,'00008','Ceramic Trinket Tray','A minimal ceramic tray for jewellery, keys, clips and other tiny desk or bedside essentials.',4500.00,'assets/store/products/0300008-image-front-1789972310.jfif','assets/store/products/0300008-image-hover-1789972310.jfif',0,NULL,1,'2026-09-21 06:31:50','2026-09-21 06:31:50');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0400001','04',22,'00001','Tinted Lip Balm','A comfortable moisturising balm that adds a soft wash of everyday colour to the lips.',5500.00,'assets/store/products/0400001-image-front-1789972526.jfif','assets/store/products/0400001-image-hover-1789972526.jfif',0,NULL,1,'2026-09-21 06:35:26','2026-09-21 06:35:26');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0400002','04',22,'00002','Hydrating Lip Oil','A glossy conditioning lip oil designed to leave lips looking smooth, juicy and hydrated.',7000.00,'assets/store/products/0400002-image-front-1789972767.jfif','assets/store/products/0400002-image-hover-1789972767.jfif',0,NULL,1,'2026-09-21 06:39:27','2026-09-21 06:39:27');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0400003','04',21,'00003','Gentle Jelly Cleanser','A soft gel cleanser that removes everyday buildup while leaving skin feeling fresh and comfortable.',12000.00,'assets/store/products/0400003-image-front-1789972911.jfif','assets/store/products/0400003-image-hover-1789972911.jfif',0,NULL,1,'2026-09-21 06:41:51','2026-09-21 06:41:51');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0400004','04',21,'00004','Soothing Face Mist','A refreshing facial mist for a quick boost of lightweight hydration throughout the day.',13000.00,'assets/store/products/0400004-image-front-1789973041.jfif','assets/store/products/0400004-image-hover-1789973041.jfif',0,NULL,1,'2026-09-21 06:44:01','2026-09-21 06:44:01');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0400005','04',23,'00005','Makeup Sponge Set','A set of soft blending sponges for smooth application of liquid and cream makeup.',1300.00,'assets/store/products/0400005-image-front-1789973167.jfif','assets/store/products/0400005-image-hover-1789973167.jfif',0,NULL,1,'2026-09-21 06:46:07','2026-09-21 06:46:07');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0400006','04',23,'00006','Compact Vanity Mirror','A portable folding mirror designed for makeup touch-ups at home or on the go.',1600.00,'assets/store/products/0400006-image-front-1789973269.jfif','assets/store/products/0400006-image-hover-1789973269.jfif',0,NULL,1,'2026-09-21 06:47:49','2026-09-21 06:47:49');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0400007','04',23,'00007','Makeup Brush Set','A versatile brush collection covering everyday face, cheek and eye makeup essentials',1700.00,'assets/store/products/0400007-image-front-1789973418.jfif','assets/store/products/0400007-image-hover-1789973418.jfif',0,NULL,1,'2026-09-21 06:50:18','2026-09-21 06:50:18');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500001','05',26,'00001','Kids Mini Backpack','A lightweight child-sized backpack with room for snacks, toys and everyday little essentials.',7000.00,'assets/store/products/0500001-image-front-1789975410.jfif','assets/store/products/0500001-image-hover-1789975410.jfif',0,NULL,1,'2026-09-21 07:23:30','2026-09-21 07:23:30');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500002','05',26,'00002','Kids Lunch Box','A compact insulated lunch carrier made to keep school snacks and meals neatly packed.',3000.00,'assets/store/products/0500002-image-front-1789975654.jfif','assets/store/products/0500002-image-hover-1789975654.jfif',0,NULL,1,'2026-09-21 07:27:34','2026-09-21 07:27:34');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500003','05',26,'00003','Water Bottle with keychain','An easy-carry reusable bottle with a practical strap designed for school and days out',3000.00,'assets/store/products/0500003-image-front-1789975998.jfif','assets/store/products/0500003-image-hover-1789975998.jfif',0,NULL,1,'2026-09-21 07:33:18','2026-09-21 07:33:18');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500004','05',24,'00004','Magnetic Building Tiles','Colourful magnetic tiles for building shapes, structures and imaginative 3D creations',5000.00,'assets/store/products/0500004-image-front-1789976190.jfif','assets/store/products/0500004-image-hover-1789976190.jfif',1,5,1,'2026-09-21 07:36:30','2026-09-21 07:36:30');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500005','05',24,'00005','Mini Science Experiment Lab','An activity kit packed with simple experiments designed to make early science exciting and approachable.',19000.00,'assets/store/products/0500005-image-front-1789976313.jfif','assets/store/products/0500005-image-hover-1789976313.jfif',1,8,1,'2026-09-21 07:38:33','2026-09-21 07:38:33');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500006','05',25,'00006','Cozy Teddy Bear','A super-soft classic teddy designed for cuddles, bedtime and everyday comfort.',5000.00,'assets/store/products/0500006-image-front-1789976417.jfif','assets/store/products/0500006-image-hover-1789976417.jfif',0,NULL,1,'2026-09-21 07:40:17','2026-09-21 07:40:17');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500007','05',25,'00007','Night Light stand','A soft-glow decorative night light that brings a warm and playful touch to kids\' rooms',6000.00,'assets/store/products/0500007-image-front-1789976517.jfif','assets/store/products/0500007-image-hover-1789976517.jfif',1,5,1,'2026-09-21 07:41:57','2026-09-21 07:41:57');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0500008','05',25,'00008','Checkered Cushion','A soft decorative cushion with a playful check pattern for beds, reading corners and playrooms.',4000.00,'assets/store/products/0500008-image-front-1789976644.jfif','assets/store/products/0500008-image-hover-1789976644.jfif',1,4,1,'2026-09-21 07:44:04','2026-09-21 07:44:04');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0600001','06',27,'00001','Compact Card Holder','A slim everyday card holder with dedicated slots for cards and small essentials.',13000.00,'assets/store/products/0600001-image-front-1789973747.jfif','assets/store/products/0600001-image-hover-1789973747.jfif',0,NULL,1,'2026-09-21 06:55:47','2026-09-21 06:55:47');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0600002','06',27,'00002','Travel Passport Organizer','A practical travel wallet with organised space for passports, cards, tickets and documents',1000.00,'assets/store/products/0600002-image-front-1789973935.jfif','assets/store/products/0600002-image-hover-1789973935.jfif',0,NULL,1,'2026-09-21 06:58:55','2026-09-21 06:58:55');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0600003','06',28,'00003','Classic Mini Shoulder Bag','A structured mini shoulder bag made for everyday outfits and grab-and-go essentials',14000.00,'assets/store/products/0600003-image-front-1789974099.jfif','assets/store/products/0600003-image-hover-1789974099.jfif',0,NULL,1,'2026-09-21 07:01:39','2026-09-21 07:01:39');
INSERT INTO `product` (`product_id`, `category_code`, `subcategory_id`, `product_number`, `product_name`, `description`, `price`, `image_front`, `image_hover`, `has_warranty`, `warranty_months`, `is_active`, `created_at`, `updated_at`) VALUES ('0600004','06',28,'00004','Everyday Tote Bags','A roomy lightweight tote for classes, errands, books and everyday carry.',2999.88,'assets/store/products/0600004-image-front-1789974324.jfif','assets/store/products/0600004-image-hover-1789974324.jfif',0,NULL,1,'2026-09-21 07:05:24','2026-09-21 07:05:24');

INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (1,1,'Main Road Gulshan Hadeed, Gulshan e Hadeed Phase 1 Phase 1 Bin Qasim Town, Karachi',NULL,'karachi','sindh','75010',0);
INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (2,2,'B-II/48 gulshan e iqbal phase 1 & 2','B-II/48 gulshan e iqbal phase 1 & 2','karachi','sindh','75010',0);
INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (3,3,'Main Road DHA','Main Road DHA bukhari','karachi','sindh','75010',0);
INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (4,4,'B-II/48 gulshan e hadeed phase 1 & 2','B-II/48 gulshan e hadeed phase 1 & 2','karachi','sindh','75010',0);
INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (5,5,'B-II/48 gulshan e hadeed phase 1 & 2','B-II/48 gulshan e hadeed phase 1 & 2','karachi','sindh','75010',0);
INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (6,6,'C-II/48 DHA phase 1 & 2','C-II/48 Dha phase 1 & 2','karachi','sindh','75010',0);
INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (7,5,'b-II / DHA',NULL,'karachi','sindh','7506',0);
INSERT INTO `customer_address` (`address_id`, `customer_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`) VALUES (8,5,'b-II / DHA',NULL,'karachi','sindh','7506',0);

INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0100001',10,'2026-09-21 05:57:48');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0100002',25,'2026-09-21 05:48:25');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0100003',35,'2026-09-21 05:55:21');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0100004',18,'2026-09-21 05:51:40');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0100005',17,'2026-09-21 06:00:19');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0100006',35,'2026-09-21 06:03:08');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200001',12,'2026-09-19 07:37:19');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200002',10,'2026-09-19 08:03:25');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200003',12,'2026-09-19 08:08:20');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200004',13,'2026-09-21 05:27:46');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200005',20,'2026-09-21 05:31:30');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200006',19,'2026-09-21 05:34:38');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200007',20,'2026-09-21 05:37:01');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200008',12,'2026-09-21 05:39:31');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200009',4,'2026-09-21 05:43:07');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0200010',12,'2026-09-21 05:46:05');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300001',13,'2026-09-21 06:08:01');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300002',4,'2026-09-21 06:11:39');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300003',12,'2026-09-21 06:13:28');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300004',13,'2026-09-21 06:22:26');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300005',14,'2026-09-21 06:24:56');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300006',10,'2026-09-21 06:29:47');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300007',10,'2026-09-21 06:27:21');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0300008',11,'2026-09-21 06:31:50');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0400001',15,'2026-09-21 06:35:26');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0400002',15,'2026-09-21 06:39:27');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0400003',19,'2026-09-21 06:41:51');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0400004',0,'2026-09-21 06:44:01');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0400005',13,'2026-09-21 06:46:07');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0400006',6,'2026-09-21 06:47:49');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0400007',19,'2026-09-21 06:50:18');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500001',0,'2026-09-21 07:23:30');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500002',13,'2026-09-21 07:27:34');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500003',14,'2026-09-21 07:33:18');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500004',14,'2026-09-21 07:36:31');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500005',40,'2026-09-21 07:38:33');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500006',12,'2026-09-21 07:40:17');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500007',11,'2026-09-21 07:41:57');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0500008',14,'2026-09-21 07:44:04');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0600001',10,'2026-09-21 06:55:47');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0600002',12,'2026-09-21 06:58:55');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0600003',29,'2026-09-21 07:01:39');
INSERT INTO `stock` (`product_id`, `quantity_available`, `last_restocked_at`) VALUES ('0600004',12,'2026-09-21 07:05:24');

INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (1,'0100001','What makes Mini Bedroom Furniture Set special?','Mini Bedroom Furniture Set is selected for the Dolls & Accessories collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:45:52','2026-09-21 15:45:52');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (2,'0100001','Who is Mini Bedroom Furniture Set best for?','This pick works beautifully for shoppers browsing Dollhouses, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:45:52','2026-09-21 15:45:52');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (3,'0100001','What should I know before ordering?','A miniature bed, wardrobe, side table and accessories made to furnish a favourite dollhouse room.',3,'2026-09-21 15:45:52','2026-09-21 15:45:52');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (4,'0100001','Is it available right now?','Mini Bedroom Furniture Set is currently available with 10 piece(s) ready for checkout.',4,'2026-09-21 15:45:52','2026-09-21 15:45:52');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (5,'0100001','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:45:52','2026-09-21 15:45:52');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (6,'0200001','What makes Plant & Paint Mini Garden Kit special?','Plant & Paint Mini Garden Kit is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (7,'0200001','Who is Plant & Paint Mini Garden Kit best for?','This pick works beautifully for shoppers browsing Eco Play, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (8,'0200001','What should I know before ordering?','A hands-on mini gardening kit that lets little makers paint, plant and grow their own tiny garden.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (9,'0200001','Is it available right now?','Plant & Paint Mini Garden Kit is currently available with 12 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (10,'0200001','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (11,'0200002','What makes Recycled Paper Craft Box special?','Recycled Paper Craft Box is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (12,'0200002','Who is Recycled Paper Craft Box best for?','This pick works beautifully for shoppers browsing Eco Play, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (13,'0200002','What should I know before ordering?','A colourful collection of recycled papers, shapes and craft essentials made for open-ended creative play.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (14,'0200002','Is it available right now?','Recycled Paper Craft Box is currently available with 10 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (15,'0200002','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (16,'0200003','What makes Wooden Animal Painting Set special?','Wooden Animal Painting Set is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (17,'0200003','Who is Wooden Animal Painting Set best for?','This pick works beautifully for shoppers browsing Eco Play, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (18,'0200003','What should I know before ordering?','Smooth wooden animal figures with paints and brushes for an easy afternoon art activity.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (19,'0200003','Is it available right now?','Wooden Animal Painting Set is currently available with 12 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (20,'0200003','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (21,'0200004','What makes Water Reveal Coloring Book special?','Water Reveal Coloring Book is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (22,'0200004','Who is Water Reveal Coloring Book best for?','This pick works beautifully for shoppers browsing Mess-Free Creativity, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (23,'0200004','What should I know before ordering?','Reusable colouring pages that magically reveal colours using only water no paint, stains or cleanup.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (24,'0200004','Is it available right now?','Water Reveal Coloring Book is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (25,'0200004','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (26,'0200005','What makes Reusable Magic Doodle Mat special?','Reusable Magic Doodle Mat is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (27,'0200005','Who is Reusable Magic Doodle Mat best for?','This pick works beautifully for shoppers browsing Mess-Free Creativity, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (28,'0200005','What should I know before ordering?','A large reusable drawing mat for endless doodling using water-filled pens.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (29,'0200005','Is it available right now?','Reusable Magic Doodle Mat is currently available with 20 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (30,'0200005','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (31,'0200006','What makes No-Spill Paint Stick Set special?','No-Spill Paint Stick Set is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (32,'0200006','Who is No-Spill Paint Stick Set best for?','This pick works beautifully for shoppers browsing Mess-Free Creativity, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (33,'0200006','What should I know before ordering?','Smooth twist-up paint sticks that give kids bold colour without brushes, water or messy paint pots.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (34,'0200006','Is it available right now?','No-Spill Paint Stick Set is currently available with 19 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (35,'0200006','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (36,'0200007','What makes Peel & Stick Mosaic Kit special?','Peel & Stick Mosaic Kit is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (37,'0200007','Who is Peel & Stick Mosaic Kit best for?','This pick works beautifully for shoppers browsing Mess-Free Creativity, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (38,'0200007','What should I know before ordering?','An easy sticker mosaic activity set designed to build creativity, pattern recognition and focus.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (39,'0200007','Is it available right now?','Peel & Stick Mosaic Kit is currently available with 20 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (40,'0200007','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (41,'0200008','What makes Beginner Crochet Kit special?','Beginner Crochet Kit is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (42,'0200008','Who is Beginner Crochet Kit best for?','This pick works beautifully for shoppers browsing Skill Building, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (43,'0200008','What should I know before ordering?','A complete starter set with yarn, hook and easy instructions for learning basic crochet techniques..',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (44,'0200008','Is it available right now?','Beginner Crochet Kit is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (45,'0200008','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (46,'0200009','What makes Friendship Bracelet Maker Set special?','Friendship Bracelet Maker Set is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (47,'0200009','Who is Friendship Bracelet Maker Set best for?','This pick works beautifully for shoppers browsing Skill Building, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (48,'0200009','What should I know before ordering?','Colourful threads, beads and tools for designing personalised friendship bracelets.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (49,'0200009','Is it available right now?','Friendship Bracelet Maker Set is currently available with 5 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (50,'0200009','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (51,'0200010','What makes Clay Sculpting Starter Set special?','Clay Sculpting Starter Set is selected for the Art&Craft collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (52,'0200010','Who is Clay Sculpting Starter Set best for?','This pick works beautifully for shoppers browsing Skill Building, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (53,'0200010','What should I know before ordering?','Soft modelling clay and beginner tools for shaping mini figures, charms and creative objects',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (54,'0200010','Is it available right now?','Clay Sculpting Starter Set is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (55,'0200010','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (56,'0600001','What makes Compact Card Holder special?','Compact Card Holder is selected for the Bags&Wallets collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (57,'0600001','Who is Compact Card Holder best for?','This pick works beautifully for shoppers browsing Wallets & Organizers, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (58,'0600001','What should I know before ordering?','A slim everyday card holder with dedicated slots for cards and small essentials.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (59,'0600001','Is it available right now?','Compact Card Holder is currently available with 10 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (60,'0600001','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (61,'0600002','What makes Travel Passport Organizer special?','Travel Passport Organizer is selected for the Bags&Wallets collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (62,'0600002','Who is Travel Passport Organizer best for?','This pick works beautifully for shoppers browsing Wallets & Organizers, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (63,'0600002','What should I know before ordering?','A practical travel wallet with organised space for passports, cards, tickets and documents',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (64,'0600002','Is it available right now?','Travel Passport Organizer is currently available with 12 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (65,'0600002','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (66,'0600003','What makes Classic Mini Shoulder Bag special?','Classic Mini Shoulder Bag is selected for the Bags&Wallets collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (67,'0600003','Who is Classic Mini Shoulder Bag best for?','This pick works beautifully for shoppers browsing Everyday Bags, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (68,'0600003','What should I know before ordering?','A structured mini shoulder bag made for everyday outfits and grab-and-go essentials',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (69,'0600003','Is it available right now?','Classic Mini Shoulder Bag is currently available with 30 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (70,'0600003','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (71,'0600004','What makes Everyday Tote Bags special?','Everyday Tote Bags is selected for the Bags&Wallets collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (72,'0600004','Who is Everyday Tote Bags best for?','This pick works beautifully for shoppers browsing Everyday Bags, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (73,'0600004','What should I know before ordering?','A roomy lightweight tote for classes, errands, books and everyday carry.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (74,'0600004','Is it available right now?','Everyday Tote Bags is currently available with 12 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (75,'0600004','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (76,'0400001','What makes Tinted Lip Balm special?','Tinted Lip Balm is selected for the Beauty & Skincare collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (77,'0400001','Who is Tinted Lip Balm best for?','This pick works beautifully for shoppers browsing Clean Cosmetics, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (78,'0400001','What should I know before ordering?','A comfortable moisturising balm that adds a soft wash of everyday colour to the lips.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (79,'0400001','Is it available right now?','Tinted Lip Balm is currently available with 16 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (80,'0400001','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (81,'0400002','What makes Hydrating Lip Oil special?','Hydrating Lip Oil is selected for the Beauty & Skincare collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (82,'0400002','Who is Hydrating Lip Oil best for?','This pick works beautifully for shoppers browsing Clean Cosmetics, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (83,'0400002','What should I know before ordering?','A glossy conditioning lip oil designed to leave lips looking smooth, juicy and hydrated.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (84,'0400002','Is it available right now?','Hydrating Lip Oil is currently available with 15 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (85,'0400002','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (86,'0400003','What makes Gentle Jelly Cleanser special?','Gentle Jelly Cleanser is selected for the Beauty & Skincare collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (87,'0400003','Who is Gentle Jelly Cleanser best for?','This pick works beautifully for shoppers browsing Gentle Skincare, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (88,'0400003','What should I know before ordering?','A soft gel cleanser that removes everyday buildup while leaving skin feeling fresh and comfortable.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (89,'0400003','Is it available right now?','Gentle Jelly Cleanser is currently available with 19 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (90,'0400003','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (91,'0400004','What makes Soothing Face Mist special?','Soothing Face Mist is selected for the Beauty & Skincare collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (92,'0400004','Who is Soothing Face Mist best for?','This pick works beautifully for shoppers browsing Gentle Skincare, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (93,'0400004','What should I know before ordering?','A refreshing facial mist for a quick boost of lightweight hydration throughout the day.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (94,'0400004','Is it available right now?','Soothing Face Mist is currently out of stock, but you can check back for the next Bits&Bobbins restock.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (95,'0400004','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (96,'0400005','What makes Makeup Sponge Set special?','Makeup Sponge Set is selected for the Beauty & Skincare collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (97,'0400005','Who is Makeup Sponge Set best for?','This pick works beautifully for shoppers browsing Tools & Accessories, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (98,'0400005','What should I know before ordering?','A set of soft blending sponges for smooth application of liquid and cream makeup.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (99,'0400005','Is it available right now?','Makeup Sponge Set is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (100,'0400005','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (101,'0400006','What makes Compact Vanity Mirror special?','Compact Vanity Mirror is selected for the Beauty & Skincare collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (102,'0400006','Who is Compact Vanity Mirror best for?','This pick works beautifully for shoppers browsing Tools & Accessories, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (103,'0400006','What should I know before ordering?','A portable folding mirror designed for makeup touch-ups at home or on the go.',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (104,'0400006','Is it available right now?','Compact Vanity Mirror is currently available with 7 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (105,'0400006','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (106,'0400007','What makes Makeup Brush Set special?','Makeup Brush Set is selected for the Beauty & Skincare collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (107,'0400007','Who is Makeup Brush Set best for?','This pick works beautifully for shoppers browsing Tools & Accessories, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (108,'0400007','What should I know before ordering?','A versatile brush collection covering everyday face, cheek and eye makeup essentials',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (109,'0400007','Is it available right now?','Makeup Brush Set is currently available with 19 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (110,'0400007','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (111,'0100002','What makes Curly Hair Fashion Doll special?','Curly Hair Fashion Doll is selected for the Dolls & Accessories collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (112,'0100002','Who is Curly Hair Fashion Doll best for?','This pick works beautifully for shoppers browsing Diverse & Inclusive Dolls, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (113,'0100002','What should I know before ordering?','A stylish doll with textured curly hair, removable outfit and accessories for imaginative styling play',3,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (114,'0100002','Is it available right now?','Curly Hair Fashion Doll is currently available with 25 piece(s) ready for checkout.',4,'2026-09-21 15:46:27','2026-09-21 15:46:27');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (115,'0100002','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (116,'0100003','What makes Wooden Pastel Dollhouse special?','Wooden Pastel Dollhouse is selected for the Dolls & Accessories collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (117,'0100003','Who is Wooden Pastel Dollhouse best for?','This pick works beautifully for shoppers browsing Dollhouses, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (118,'0100003','What should I know before ordering?','A charming multi-room wooden dollhouse with soft pastel details made for creative everyday play.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (119,'0100003','Is it available right now?','Wooden Pastel Dollhouse is currently available with 35 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (120,'0100003','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (121,'0100004','What makes Multicultural Everyday Doll special?','Multicultural Everyday Doll is selected for the Dolls & Accessories collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (122,'0100004','Who is Multicultural Everyday Doll best for?','This pick works beautifully for shoppers browsing Diverse & Inclusive Dolls, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (123,'0100004','What should I know before ordering?','A beautifully detailed everyday doll designed to encourage inclusive and imaginative storytelling.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (124,'0100004','Is it available right now?','Multicultural Everyday Doll is currently available with 20 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (125,'0100004','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (126,'0100005','What makes Mini Café & Bakery Play Set special?','Mini Café & Bakery Play Set is selected for the Dolls & Accessories collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (127,'0100005','Who is Mini Café & Bakery Play Set best for?','This pick works beautifully for shoppers browsing Roleplay Sets, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (128,'0100005','What should I know before ordering?','A pretend café set with pastries, serving pieces and accessories for creating a tiny bakery at home.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (129,'0100005','Is it available right now?','Mini Café & Bakery Play Set is currently available with 17 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (130,'0100005','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (131,'0100006','What makes Little Doctor Roleplay Set special?','Little Doctor Roleplay Set is selected for the Dolls & Accessories collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (132,'0100006','Who is Little Doctor Roleplay Set best for?','This pick works beautifully for shoppers browsing Roleplay Sets, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (133,'0100006','What should I know before ordering?','A playful medical kit with pretend tools that encourages imaginative doctor and patient roleplay',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (134,'0100006','Is it available right now?','Little Doctor Roleplay Set is currently available with 35 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (135,'0100006','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (136,'0300001','What makes Digital Gift Card special?','Digital Gift Card is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (137,'0300001','Who is Digital Gift Card best for?','This pick works beautifully for shoppers browsing Digital Gift Cards, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (138,'0300001','What should I know before ordering?','A digital gift card delivered electronically for when you want them to choose exactly what they love.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (139,'0300001','Is it available right now?','Digital Gift Card is currently available with 14 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (140,'0300001','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (141,'0300002','What makes Birthday digital card special?','Birthday digital card is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (142,'0300002','Who is Birthday digital card best for?','This pick works beautifully for shoppers browsing Digital Gift Cards, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (143,'0300002','What should I know before ordering?','A flexible digital gift for birthdays, celebrations or thoughtful little surprises.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (144,'0300002','Is it available right now?','Birthday digital card is currently available with 4 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (145,'0300002','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (146,'0300003','What makes Celebration Gift Box special?','Celebration Gift Box is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (147,'0300003','Who is Celebration Gift Box best for?','This pick works beautifully for shoppers browsing Gifting Essentials, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (148,'0300003','What should I know before ordering?','A premium reusable gift box designed to make birthdays and special moments feel extra thoughtful',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (149,'0300003','Is it available right now?','Celebration Gift Box is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (150,'0300003','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (151,'0300004','What makes Gift Wrap Set special?','Gift Wrap Set is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (152,'0300004','Who is Gift Wrap Set best for?','This pick works beautifully for shoppers browsing Gifting Essentials, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (153,'0300004','What should I know before ordering?','Coordinating wrapping paper, ribbons and tags for turning any present into something special.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (154,'0300004','Is it available right now?','Gift Wrap Set is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (155,'0300004','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (156,'0300005','What makes Mini Greeting Card Bundle special?','Mini Greeting Card Bundle is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (157,'0300005','Who is Mini Greeting Card Bundle best for?','This pick works beautifully for shoppers browsing Gifting Essentials, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (158,'0300005','What should I know before ordering?','A set of illustrated mini cards for birthdays, thank-yous and little everyday messages.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (159,'0300005','Is it available right now?','Mini Greeting Card Bundle is currently available with 14 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (160,'0300005','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (161,'0300006','What makes Weekly Planner special?','Weekly Planner is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (162,'0300006','Who is Weekly Planner best for?','This pick works beautifully for shoppers browsing Organization & Desk Decor, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (163,'0300006','What should I know before ordering?','A clean multi-compartment organizer for stationery, notes and everyday desk essentials.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (164,'0300006','Is it available right now?','Weekly Planner is currently available with 10 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (165,'0300006','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (166,'0300007','What makes Acrylic Desktop Organizer special?','Acrylic Desktop Organizer is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (167,'0300007','Who is Acrylic Desktop Organizer best for?','This pick works beautifully for shoppers browsing Organization & Desk Decor, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (168,'0300007','What should I know before ordering?','A clean multi-compartment organizer for stationery, notes and everyday desk essentials.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (169,'0300007','Is it available right now?','Acrylic Desktop Organizer is currently available with 10 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (170,'0300007','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (171,'0300008','What makes Ceramic Trinket Tray special?','Ceramic Trinket Tray is selected for the Gifts&Stationary collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (172,'0300008','Who is Ceramic Trinket Tray best for?','This pick works beautifully for shoppers browsing Organization & Desk Decor, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (173,'0300008','What should I know before ordering?','A minimal ceramic tray for jewellery, keys, clips and other tiny desk or bedside essentials.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (174,'0300008','Is it available right now?','Ceramic Trinket Tray is currently available with 12 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (175,'0300008','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (176,'0500001','What makes Kids Mini Backpack special?','Kids Mini Backpack is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (177,'0500001','Who is Kids Mini Backpack best for?','This pick works beautifully for shoppers browsing Everyday Travel, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (178,'0500001','What should I know before ordering?','A lightweight child-sized backpack with room for snacks, toys and everyday little essentials.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (179,'0500001','Is it available right now?','Kids Mini Backpack is currently out of stock, but you can check back for the next Bits&Bobbins restock.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (180,'0500001','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (181,'0500002','What makes Kids Lunch Box special?','Kids Lunch Box is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (182,'0500002','Who is Kids Lunch Box best for?','This pick works beautifully for shoppers browsing Everyday Travel, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (183,'0500002','What should I know before ordering?','A compact insulated lunch carrier made to keep school snacks and meals neatly packed.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (184,'0500002','Is it available right now?','Kids Lunch Box is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (185,'0500002','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (186,'0500003','What makes Water Bottle with keychain special?','Water Bottle with keychain is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (187,'0500003','Who is Water Bottle with keychain best for?','This pick works beautifully for shoppers browsing Everyday Travel, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (188,'0500003','What should I know before ordering?','An easy-carry reusable bottle with a practical strap designed for school and days out',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (189,'0500003','Is it available right now?','Water Bottle with keychain is currently available with 14 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (190,'0500003','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (191,'0500004','What makes Magnetic Building Tiles special?','Magnetic Building Tiles is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (192,'0500004','Who is Magnetic Building Tiles best for?','This pick works beautifully for shoppers browsing Tech & STEM, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (193,'0500004','What should I know before ordering?','Colourful magnetic tiles for building shapes, structures and imaginative 3D creations',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (194,'0500004','Is it available right now?','Magnetic Building Tiles is currently available with 14 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (195,'0500004','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (196,'0500005','What makes Mini Science Experiment Lab special?','Mini Science Experiment Lab is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (197,'0500005','Who is Mini Science Experiment Lab best for?','This pick works beautifully for shoppers browsing Tech & STEM, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (198,'0500005','What should I know before ordering?','An activity kit packed with simple experiments designed to make early science exciting and approachable.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (199,'0500005','Is it available right now?','Mini Science Experiment Lab is currently available with 40 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (200,'0500005','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (201,'0500006','What makes Cozy Teddy Bear special?','Cozy Teddy Bear is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (202,'0500006','Who is Cozy Teddy Bear best for?','This pick works beautifully for shoppers browsing Room Decor & Comfort, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (203,'0500006','What should I know before ordering?','A super-soft classic teddy designed for cuddles, bedtime and everyday comfort.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (204,'0500006','Is it available right now?','Cozy Teddy Bear is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (205,'0500006','Does this product include warranty support?','This item does not list a warranty, but our team can still help with order and product questions.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (206,'0500007','What makes Night Light stand special?','Night Light stand is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (207,'0500007','Who is Night Light stand best for?','This pick works beautifully for shoppers browsing Room Decor & Comfort, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (208,'0500007','What should I know before ordering?','A soft-glow decorative night light that brings a warm and playful touch to kids\' rooms',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (209,'0500007','Is it available right now?','Night Light stand is currently available with 13 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (210,'0500007','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (211,'0500008','What makes Checkered Cushion special?','Checkered Cushion is selected for the Kids (General & Lifestyle) collection with the playful, practical Bits&Bobbins feel families expect.',1,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (212,'0500008','Who is Checkered Cushion best for?','This pick works beautifully for shoppers browsing Room Decor & Comfort, everyday surprises, birthdays, small rewards, and sweet gifting moments.',2,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (213,'0500008','What should I know before ordering?','A soft decorative cushion with a playful check pattern for beds, reading corners and playrooms.',3,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (214,'0500008','Is it available right now?','Checkered Cushion is currently available with 14 piece(s) ready for checkout.',4,'2026-09-21 15:46:28','2026-09-21 15:46:28');
INSERT INTO `product_detail` (`detail_id`, `product_id`, `title`, `body`, `display_order`, `created_at`, `updated_at`) VALUES (215,'0500008','Does this product include warranty support?','Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.',5,'2026-09-21 15:46:28','2026-09-21 15:46:28');

INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (1,1,1,'2026-09-21 17:55:37','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (2,2,2,'2026-09-21 18:12:54','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (3,3,3,'2026-09-21 18:22:33','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (4,4,4,'2026-09-21 18:27:30','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (5,4,4,'2026-09-22 05:46:14','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (6,5,5,'2026-09-22 12:19:47','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (7,6,6,'2026-09-22 12:23:05','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (8,5,7,'2026-09-30 05:19:57','delivered');
INSERT INTO `orders` (`order_id`, `customer_id`, `shipping_address_id`, `order_date`, `order_status`) VALUES (9,5,8,'2026-09-30 06:27:44','placed');

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (1,1,'0500007','1','00000001',1,6000.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (2,1,'0200008','1','00000002',1,4500.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (3,1,'0300008','1','00000003',1,4500.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (4,2,'0100004','2','00000001',2,13500.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (5,3,'0300003','1','00000001',1,2000.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (6,3,'0600003','1','00000002',1,14000.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (7,4,'0500006','1','00000001',1,5000.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (8,4,'0400006','1','00000002',1,1600.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (9,5,'0200010','1','00000001',1,4500.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (10,6,'0200009','1','00000001',1,1200.00,'replaced');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (11,7,'0400001','1','00000001',1,5500.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (12,8,'0300001','1','00000008',1,12000.00,'delivered');
INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `delivery_code`, `item_sequence`, `quantity`, `unit_price`, `item_status`) VALUES (13,9,'0500007','1','00000009',1,6000.00,'placed');

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (1,1,'vpp_cod',15000.00,'cleared','2026-09-30 05:40:46');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (2,2,'credit_card',27000.00,'cleared','2026-09-21 18:12:54');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (3,3,'dd',16000.00,'pending','2026-09-21 18:22:33');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (4,4,'vpp_cod',6600.00,'cleared','2026-09-30 05:39:31');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (5,5,'vpp_cod',4500.00,'cleared','2026-09-30 05:39:09');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (6,6,'vpp_cod',1200.00,'pending','2026-09-22 12:19:47');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (7,7,'credit_card',5500.00,'cleared','2026-09-22 12:23:05');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (8,8,'credit_card',12000.00,'cleared','2026-09-30 05:19:57');
INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `amount`, `payment_status`, `payment_date`) VALUES (9,9,'vpp_cod',6000.00,'pending','2026-09-30 06:27:44');

INSERT INTO `payment_credit_card` (`payment_id`, `card_last4`, `card_holder_name`, `gateway_txn_ref`, `auth_code`) VALUES (2,'0434','HadiAli','PENDING-2','CLEARED-2');
INSERT INTO `payment_credit_card` (`payment_id`, `card_last4`, `card_holder_name`, `gateway_txn_ref`, `auth_code`) VALUES (7,'8282','UMAR JANJUA','PENDING-7','CLEARED-7');
INSERT INTO `payment_credit_card` (`payment_id`, `card_last4`, `card_holder_name`, `gateway_txn_ref`, `auth_code`) VALUES (8,'3345','Abdul Mannan','PENDING-8','CLEARED-8');


INSERT INTO `payment_dd` (`payment_id`, `dd_number`, `bank_name`, `dd_date`, `clearance_date`) VALUES (3,'0984758804993002','Alfalah bank','2026-09-09',NULL);

INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (1,9,2,'2026-09-22 00:00:00','2026-09-25','2026-09-22','TRK-2026-004583');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (2,8,2,'2026-09-22 00:00:00','2026-09-25','2026-09-29','TRK-2026-004582');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (3,7,2,'2026-09-22 00:00:00','2026-09-25','2026-09-22','TRK-2026-004585');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (4,6,3,'2026-09-22 00:00:00','2026-09-25','2026-09-22','TRK-2026-004586');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (5,5,3,'2026-09-22 00:00:00','2026-09-25','2026-09-22','TRK-2026-004587');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (6,3,2,'2026-09-22 00:00:00','2026-09-25','2026-09-22','TRK-2026-004588');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (7,2,3,'2026-09-22 00:00:00','2026-09-25','2026-09-22','TRK-2026-004589');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (8,1,3,'2026-09-22 00:00:00','2026-09-25','2026-09-22','TRK-2026-004590');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (9,11,1,'2026-09-22 00:00:00','2026-09-25','2026-09-23','TRK-2026-004591');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (10,10,1,'2026-09-22 00:00:00','2026-09-25','2026-09-23','TRK-2026-004592');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (11,4,1,'2026-09-22 00:00:00','2026-09-25','2026-09-23','TRK-2026-004593');
INSERT INTO `dispatch` (`dispatch_id`, `order_item_id`, `dispatched_by`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `courier_tracking_number`) VALUES (12,12,2,'2026-09-30 00:00:00','2026-10-03','2026-09-30','TRK-2026-004100');

INSERT INTO `warranty_card` (`warranty_id`, `order_item_id`, `warranty_start_date`, `warranty_end_date`, `terms`) VALUES (1,9,'2026-09-22','2026-12-22','Warranty coverage is valid for 3 month(s) from delivery date.');

INSERT INTO `return_replace_request` (`request_id`, `order_item_id`, `request_type`, `request_date`, `reason`, `status`, `refund_amount`, `processed_by`, `resolved_date`) VALUES (1,10,'replace','2026-09-30 05:21:39','Customer requested replacement from order detail page.','completed',NULL,NULL,'2026-09-30 05:29:22');

INSERT INTO `feedback` (`feedback_id`, `customer_id`, `order_id`, `product_id`, `message`, `rating`, `submitted_at`, `reviewed_at`) VALUES (1,1,1,'0200008','Everything I needed was included, and the instructions were easy to follow. The yarn was soft, and the kit made crocheting feel really fun and relaxing. Perfect for beginners!',5,'2026-09-21 18:00:15','2026-09-29 16:58:49');
INSERT INTO `feedback` (`feedback_id`, `customer_id`, `order_id`, `product_id`, `message`, `rating`, `submitted_at`, `reviewed_at`) VALUES (2,3,3,'0600003','I absolutely love this bag! It looks so cute, feels well-made, and is the perfect size for carrying my everyday essentials. The quality is even better than I expected!',4,'2026-09-21 18:23:22','2026-09-29 16:58:44');
INSERT INTO `feedback` (`feedback_id`, `customer_id`, `order_id`, `product_id`, `message`, `rating`, `submitted_at`, `reviewed_at`) VALUES (3,2,2,'0100004','I got this doll for my child and she absolutely loves it! The doll is adorable, well-made, and has quickly become her favorite for imaginative play. Great quality and very cut',4,'2026-09-21 18:24:16','2026-09-29 16:58:40');
INSERT INTO `feedback` (`feedback_id`, `customer_id`, `order_id`, `product_id`, `message`, `rating`, `submitted_at`, `reviewed_at`) VALUES (4,4,4,'0500006','absolutely loves this teddy bear! It’s super soft, cuddly, and the perfect size for little hands. It has quickly become their favorite bedtime companion',3,'2026-09-21 18:28:14','2026-09-29 16:58:34');
INSERT INTO `feedback` (`feedback_id`, `customer_id`, `order_id`, `product_id`, `message`, `rating`, `submitted_at`, `reviewed_at`) VALUES (5,5,6,'0200009','I bought this kit for my daughters, and they absolutely loved making friendship bracelets together. Everything was easy to use, and it kept them happily busy while creating something special.',3,'2026-09-22 12:20:35','2026-09-29 16:58:31');
INSERT INTO `feedback` (`feedback_id`, `customer_id`, `order_id`, `product_id`, `message`, `rating`, `submitted_at`, `reviewed_at`) VALUES (6,6,7,'0400001','I bought this lip balm for my wife, and she really loved it. It’s a simple, thoughtful little gift and the quality was great',3,'2026-09-22 12:24:59','2026-09-29 16:58:27');

INSERT INTO `faq` (`faq_id`, `question`, `answer`, `created_by`, `display_order`) VALUES (1,'What age group are your \"Art&Craft\" kits intended for?','Our craft kits range from toddler-friendly finger paints to advanced DIY sets for teens. Each product page lists the manufacturer\'s recommended age range and notes any potential small-part choking hazards',1,1);
INSERT INTO `faq` (`faq_id`, `question`, `answer`, `created_by`, `display_order`) VALUES (2,'What materials are your \"Bags&Wallets\" made from?','We offer a variety of high-quality materials including vegan leather, genuine top-grain leather, water-resistant nylon, and durable cotton canvas.',1,2);
INSERT INTO `faq` (`faq_id`, `question`, `answer`, `created_by`, `display_order`) VALUES (3,'Can you send a package directly to a gift recipient without showing the price?','Yes! At checkout, simply check the \"This is a gift\" box. We will exclude the physical pricing invoice from the box and replace it with a sleek gift packing slip. You can also type a custom gift message that we will print onto a card for them.',1,3);
INSERT INTO `faq` (`faq_id`, `question`, `answer`, `created_by`, `display_order`) VALUES (4,'Can I change or cancel my order after it has been placed?','We process orders quickly to ensure fast delivery. If you need to make changes or cancel an order, please email our support team within 1 hour of placement. Once an order has shifted to the \"shipped\" status in our warehouse, we can no longer modify it.',1,4);
INSERT INTO `faq` (`faq_id`, `question`, `answer`, `created_by`, `display_order`) VALUES (5,'Do you ship internationally?','We process orders quickly to ensure fast delivery. If you need to make changes or cancel an order, please email our support team within 1 hour of placement. Once an order has shifted to the \"shipped\" status in our warehouse, we can no longer modify it.',1,5);
INSERT INTO `faq` (`faq_id`, `question`, `answer`, `created_by`, `display_order`) VALUES (8,'Why isn’t my discount code working?','Discount codes usually fail for three reasons: they are expired, your cart includes \"Final Sale\" items, or you haven\'t met the minimum spend requirement. Note that codes cannot be stacked (only one per order)',1,8);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
