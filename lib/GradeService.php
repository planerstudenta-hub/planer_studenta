<?php

declare(strict_types=1);

final class GradeService
{
    private const VALID_GRADES = [2.0, 3.0, 3.5, 4.0, 4.5, 5.0, 5.5];
    private const VALID_TYPES = ['egzamin', 'kolokwium', 'projekt', 'aktywnosc', 'inne'];

    public function __construct(private readonly Database $db) {}

    public function stats(int $userId): array
    {
        $subjectRow = $this->db->fetch(
            'SELECT COUNT(*) AS subject_count, COALESCE(SUM(ects), 0) AS total_ects FROM grade_subjects WHERE user_id = :uid',
            ['uid' => $userId]
        );
        $entryRow = $this->db->fetch(
            'SELECT COUNT(*) AS entry_count, ROUND(AVG(grade), 2) AS avg_grade FROM grade_entries WHERE user_id = :uid',
            ['uid' => $userId]
        );

        return [
            'subject_count' => (int) ($subjectRow['subject_count'] ?? 0),
            'total_ects'    => (int) ($subjectRow['total_ects'] ?? 0),
            'entry_count'   => (int) ($entryRow['entry_count'] ?? 0),
            'avg_grade'     => isset($entryRow['avg_grade']) && $entryRow['avg_grade'] !== null
                ? number_format((float) $entryRow['avg_grade'], 2, '.', '')
                : null,
        ];
    }

    public function listSubjects(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT gs.*,
                    COUNT(ge.id) AS entry_count,
                    ROUND(AVG(ge.grade), 2) AS avg_grade
             FROM grade_subjects gs
             LEFT JOIN grade_entries ge ON ge.subject_id = gs.id
             WHERE gs.user_id = :uid
             GROUP BY gs.id
             ORDER BY gs.semester DESC, gs.name ASC',
            ['uid' => $userId]
        );
    }

    public function createSubject(int $userId, array $data): int
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Nazwa przedmiotu jest wymagana.');
        }
        $this->db->execute(
            'INSERT INTO grade_subjects (user_id, name, code, semester, ects) VALUES (:uid, :name, :code, :semester, :ects)',
            [
                'uid'      => $userId,
                'name'     => $name,
                'code'     => trim($data['code'] ?? '') ?: null,
                'semester' => trim($data['semester'] ?? '') ?: null,
                'ects'     => max(0, (int) ($data['ects'] ?? 0)),
            ]
        );
        return $this->db->lastId();
    }

    public function updateSubject(int $userId, int $id, array $data): void
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Nazwa przedmiotu jest wymagana.');
        }
        $this->db->execute(
            'UPDATE grade_subjects SET name = :name, code = :code, semester = :semester, ects = :ects WHERE id = :id AND user_id = :uid',
            [
                'name'     => $name,
                'code'     => trim($data['code'] ?? '') ?: null,
                'semester' => trim($data['semester'] ?? '') ?: null,
                'ects'     => max(0, (int) ($data['ects'] ?? 0)),
                'id'       => $id,
                'uid'      => $userId,
            ]
        );
    }

    public function deleteSubject(int $userId, int $id): void
    {
        $this->db->execute(
            'DELETE FROM grade_subjects WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $userId]
        );
    }

    public function listEntries(int $userId, int $subjectId): array
    {
        return $this->db->fetchAll(
            'SELECT ge.* FROM grade_entries ge
             WHERE ge.subject_id = :sid AND ge.user_id = :uid
             ORDER BY ge.graded_at DESC, ge.id DESC',
            ['sid' => $subjectId, 'uid' => $userId]
        );
    }

    public function createEntry(int $userId, array $data): int
    {
        $subjectId = (int) ($data['subject_id'] ?? 0);
        if ($subjectId <= 0) {
            throw new RuntimeException('Brak ID przedmiotu.');
        }
        $grade = (float) ($data['grade'] ?? 0);
        if (!in_array($grade, self::VALID_GRADES, true)) {
            throw new RuntimeException('Nieprawidłowa ocena. Dozwolone: 2.0, 3.0, 3.5, 4.0, 4.5, 5.0, 5.5');
        }
        $subject = $this->db->fetch(
            'SELECT id FROM grade_subjects WHERE id = :id AND user_id = :uid',
            ['id' => $subjectId, 'uid' => $userId]
        );
        if ($subject === null) {
            throw new RuntimeException('Nie znaleziono przedmiotu.');
        }
        $type = in_array($data['type'] ?? '', self::VALID_TYPES, true) ? $data['type'] : 'inne';
        $this->db->execute(
            'INSERT INTO grade_entries (subject_id, user_id, grade, type, notes, graded_at) VALUES (:sid, :uid, :grade, :type, :notes, :graded_at)',
            [
                'sid'       => $subjectId,
                'uid'       => $userId,
                'grade'     => $grade,
                'type'      => $type,
                'notes'     => trim($data['notes'] ?? '') ?: null,
                'graded_at' => $data['graded_at'] ?? date('Y-m-d'),
            ]
        );
        return $this->db->lastId();
    }

    public function updateEntry(int $userId, int $id, array $data): void
    {
        $grade = (float) ($data['grade'] ?? 0);
        if (!in_array($grade, self::VALID_GRADES, true)) {
            throw new RuntimeException('Nieprawidłowa ocena.');
        }
        $type = in_array($data['type'] ?? '', self::VALID_TYPES, true) ? $data['type'] : 'inne';
        $this->db->execute(
            'UPDATE grade_entries SET grade = :grade, type = :type, notes = :notes, graded_at = :graded_at WHERE id = :id AND user_id = :uid',
            [
                'grade'     => $grade,
                'type'      => $type,
                'notes'     => trim($data['notes'] ?? '') ?: null,
                'graded_at' => $data['graded_at'] ?? date('Y-m-d'),
                'id'        => $id,
                'uid'       => $userId,
            ]
        );
    }

    public function deleteEntry(int $userId, int $id): void
    {
        $this->db->execute(
            'DELETE FROM grade_entries WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $userId]
        );
    }
}
