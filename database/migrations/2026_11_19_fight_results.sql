ALTER TABLE event_fights
    MODIFY result ENUM('Win', 'Loss', 'Draw', 'Pending', 'No contest') DEFAULT 'Pending',
    ADD COLUMN method VARCHAR(50) NULL,
    ADD COLUMN finish_round TINYINT UNSIGNED NULL,
    ADD COLUMN finish_seconds SMALLINT UNSIGNED NULL;

ALTER TABLE fights
    ADD COLUMN finish_round TINYINT UNSIGNED NULL,
    ADD COLUMN finish_seconds SMALLINT UNSIGNED NULL;
