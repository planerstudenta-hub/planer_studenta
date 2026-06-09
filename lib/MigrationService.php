<?php

declare(strict_types=1);

final class MigrationService
{
    public function __construct(private Database $db)
    {
    }

    public function migrate(): void
    {
        $pdo = $this->db->pdo();
        $pdo->exec(file_get_contents(__DIR__ . '/../database.sql'));

        if (!$this->db->columnExists('events', 'user_id')) {
            $pdo->exec('ALTER TABLE events ADD user_id INT UNSIGNED NULL AFTER id');
        }
        if (!$this->db->columnExists('events', 'visibility')) {
            $pdo->exec('ALTER TABLE events ADD visibility ENUM("private", "friends") NOT NULL DEFAULT "private" AFTER status');
        }
        if (!$this->db->columnExists('events', 'shared_note')) {
            $pdo->exec('ALTER TABLE events ADD shared_note VARCHAR(255) NULL AFTER visibility');
        }

        $this->addIndexIfMissing('events', 'idx_events_user_id', 'CREATE INDEX idx_events_user_id ON events (user_id)');
        $this->addIndexIfMissing('events', 'idx_events_visibility', 'CREATE INDEX idx_events_visibility ON events (visibility)');
    }

    private function addIndexIfMissing(string $table, string $index, string $sql): void
    {
        $row = $this->db->fetch(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index LIMIT 1',
            ['table' => $table, 'index' => $index]
        );

        if ($row === null) {
            $this->db->pdo()->exec($sql);
        }
    }
}
