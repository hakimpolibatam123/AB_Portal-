-- M1: dashboard sections (categories) and cards, plus uploaded files.
-- Same rules as schema.sql: one statement terminator per line end, no semicolons in strings.
-- Soft delete: rows get deleted_at instead of being removed.

CREATE TABLE IF NOT EXISTS dashboard_sections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL,
    title VARCHAR(32) NOT NULL,
    icon VARCHAR(40) NOT NULL,
    kind ENUM('forms', 'links') NOT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    UNIQUE KEY uq_dashboard_sections_slug (slug),
    KEY idx_dashboard_sections_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files live outside the web root under storage/app/private/<yyyy>/<mm>/ and are served by /files/{id}.
CREATE TABLE IF NOT EXISTS attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_type VARCHAR(40) NOT NULL,
    owner_id BIGINT UNSIGNED NULL DEFAULT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    UNIQUE KEY uq_attachments_stored_path (stored_path),
    KEY idx_attachments_owner (owner_type, owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- kind builtin = one of the existing module pages (url and rekap_url are its fixed routes),
-- link = an external website, form = a dynamic form (form_id, added in M2).
CREATE TABLE IF NOT EXISTS dashboard_cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    kind ENUM('builtin', 'form', 'link') NOT NULL,
    builtin_key VARCHAR(50) NULL DEFAULT NULL,
    form_id BIGINT UNSIGNED NULL DEFAULT NULL,
    title VARCHAR(60) NOT NULL,
    description VARCHAR(60) NOT NULL DEFAULT '',
    icon VARCHAR(40) NOT NULL,
    color VARCHAR(20) NOT NULL DEFAULT 'teal',
    icon_attachment_id BIGINT UNSIGNED NULL DEFAULT NULL,
    url VARCHAR(500) NULL DEFAULT NULL,
    rekap_url VARCHAR(255) NULL DEFAULT NULL,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    UNIQUE KEY uq_dashboard_cards_builtin_key (builtin_key),
    KEY idx_dashboard_cards_section_sort (section_id, sort_order),
    CONSTRAINT fk_dashboard_cards_section FOREIGN KEY (section_id) REFERENCES dashboard_sections (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_dashboard_cards_icon FOREIGN KEY (icon_attachment_id) REFERENCES attachments (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
