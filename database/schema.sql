-- AB Portal Hub schema.
-- Import into the existing database (phpMyAdmin: select the database, then Import),
-- or run `php bin/migrate.php`. Safe to run repeatedly: every table uses IF NOT EXISTS.
-- bin/migrate.php splits statements on a semicolon at the end of a line, so keep
-- one statement terminator per line end and avoid semicolons inside string literals.

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'it_staff', 'supervisor') NOT NULL DEFAULT 'it_staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    definition_json JSON NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_forms_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- shift is NOT NULL with a default because MySQL treats NULLs as distinct in a
-- unique index, which would allow duplicate submissions for the same day.
CREATE TABLE IF NOT EXISTS form_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    submission_date DATE NOT NULL,
    shift VARCHAR(20) NOT NULL DEFAULT 'all',
    status ENUM('draft', 'submitted') NOT NULL DEFAULT 'draft',
    data_json JSON NOT NULL,
    submitted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_form_submissions_form_user_date_shift (form_id, user_id, submission_date, shift),
    KEY idx_form_submissions_user_id (user_id),
    KEY idx_form_submissions_submission_date (submission_date),
    CONSTRAINT fk_form_submissions_form FOREIGN KEY (form_id) REFERENCES forms (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_form_submissions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert-only: the application never updates or deletes audit rows, so there is no
-- updated_at column. Enforce it in production by granting the app user INSERT and
-- SELECT only on this table.
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NULL DEFAULT NULL,
    ip VARCHAR(45) NULL DEFAULT NULL,
    user_agent VARCHAR(255) NULL DEFAULT NULL,
    meta_json JSON NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_logs_user_id (user_id),
    KEY idx_audit_logs_entity (entity_type, entity_id),
    KEY idx_audit_logs_created_at (created_at),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
