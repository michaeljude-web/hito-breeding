CREATE TABLE staff (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname  VARCHAR(100) NOT NULL,
    birthday  DATE         NOT NULL,
    address   VARCHAR(255) NOT NULL,
    contact   VARCHAR(20)  NOT NULL,
    username  VARCHAR(100) NOT NULL UNIQUE,
    password  VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feeding Schedules
CREATE TABLE feeding_schedules (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pond_name   VARCHAR(100) NOT NULL,
    feed_type   VARCHAR(100) NOT NULL,
    amount_kg   DECIMAL(8,2) NOT NULL,
    frequency   VARCHAR(100) NOT NULL,
    feed_time   TIME         NOT NULL,
    created_by  INT          NOT NULL,  -- staff id
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feed Consumption Log (per session)
CREATE TABLE feed_consumption (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id     INT            NOT NULL,
    consumed_kg     DECIMAL(8,2)   NOT NULL,
    session_date    DATE           NOT NULL,
    session_time    TIME           NOT NULL,
    notes           VARCHAR(255)   DEFAULT NULL,
    logged_by       INT            NOT NULL,  -- staff id
    logged_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES feeding_schedules(id) ON DELETE CASCADE
);

------
-- Hatchery Records (breeding sessions)
CREATE TABLE hatchery_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    record_date     DATE           NOT NULL,
    female_count    INT            NOT NULL,
    eggs_produced   INT            NOT NULL,
    eggs_hatched    INT            NOT NULL,
    survival_rate   DECIMAL(5,2)   GENERATED ALWAYS AS (
                        ROUND((eggs_hatched / eggs_produced) * 100, 2)
                    ) STORED,
    logged_by       INT            NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fingerling Transfers
CREATE TABLE fingerling_transfers (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    transfer_date       DATE        NOT NULL,
    hatchery_id         INT         NOT NULL,
    pond_destination    VARCHAR(100) NOT NULL,
    quantity            INT         NOT NULL,
    logged_by           INT         NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hatchery_id) REFERENCES hatchery_records(id) ON DELETE CASCADE
);