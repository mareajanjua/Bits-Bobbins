-- ============================================================
-- Retail Dealer E-Commerce Application — Database Schema
-- Engine: MySQL 8.x (InnoDB). Adjust types if using Postgres/SQL Server.
-- Cart is intentionally NOT modeled here — it lives in session
-- storage and only becomes persistent rows at checkout.
-- ============================================================

-- ---------- ADMIN ----------
CREATE TABLE admin (
    admin_id        INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- EMPLOYEE (login by email, admin-created, self password change only) ----------
CREATE TABLE employee (
    employee_id     INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20),
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

-- Never store a full card number — PCI-DSS. Keep only a gateway token/ref + last 4.
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
