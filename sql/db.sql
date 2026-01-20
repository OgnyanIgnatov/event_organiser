CREATE DATABASE IF NOT EXISTS event_organiser CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_organiser;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('client','organiser','admin') NOT NULL DEFAULT 'client',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    organiser_id INT NOT NULL,
    event_type ENUM('private_party','corporate party','team_building','birthday','other') NOT NULL,
    requested_date DATE NOT NULL,
    participants INT NOT NULL,
    status ENUM('pending','accepted_by_organiser','rejected_by_organiser','accepted_by_client','declined_by_client','needs_correction','completed') NOT NULL DEFAULT 'pending',
    correction_note TEXT DEFAULT NULL,
    gallery_public TINYINT(1) NOT NULL DEFAULT 0,
    proposal_details TEXT DEFAULT NULL,
    price_per_participant DECIMAL(10,2) DEFAULT NULL,
    catering_details TEXT DEFAULT NULL,
    hotel_details TEXT DEFAULT NULL,
    venue_address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organiser_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT  NOT NULL,
    rating TINYINT  NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_request FOREIGN KEY (request_id) REFERENCES event_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS galleries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT  NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_public TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gallery_request FOREIGN KEY (request_id) REFERENCES event_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blacklists (
    id INT  AUTO_INCREMENT PRIMARY KEY,
    user_id INT  NOT NULL,
    blocked_user_id INT  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_blacklist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_blacklist_blocked FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
