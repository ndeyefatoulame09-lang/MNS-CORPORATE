ALTER TABLE documents
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN archived_at DATETIME NULL AFTER is_archived,
    ADD COLUMN archived_by INT UNSIGNED NULL AFTER archived_at,
    ADD COLUMN archive_reason TEXT NULL AFTER archived_by;

CREATE INDEX idx_documents_archived ON documents(is_archived);

ALTER TABLE documents
    ADD CONSTRAINT fk_documents_archived_by
        FOREIGN KEY (archived_by) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
