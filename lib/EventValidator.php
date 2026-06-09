<?php

declare(strict_types=1);

final class EventValidator
{
    private const TYPES = ['egzamin', 'projekt', 'zajecia', 'praktyki', 'wyjazd', 'sluzba', 'inne'];
    private const RECURRENCE = ['none', 'daily', 'weekly', 'monthly'];
    private const STATUSES = ['planned', 'done'];
    private const VISIBILITY = ['private', 'friends'];

    public static function normalize(array $data, bool $partial = false): array
    {
        $payload = [];
        $fields = [
            'title',
            'type',
            'start_at',
            'end_at',
            'location',
            'notes',
            'reminder_minutes',
            'prep_note',
            'prep_reminder_minutes',
            'recurrence',
            'recurrence_until',
            'status',
            'visibility',
            'shared_note',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (!$partial || array_key_exists('title', $payload)) {
            $payload['title'] = self::text($payload['title'] ?? null);
            if ($payload['title'] === null) {
                json_response(['ok' => false, 'error' => 'Tytuł jest wymagany.'], 422);
            }
        }

        if (!$partial || array_key_exists('type', $payload)) {
            $payload['type'] = $payload['type'] ?? 'inne';
            self::ensureAllowed($payload['type'], self::TYPES, 'Nieznany typ wydarzenia.');
        }

        if (!$partial || array_key_exists('status', $payload)) {
            $payload['status'] = $payload['status'] ?? 'planned';
            self::ensureAllowed($payload['status'], self::STATUSES, 'Nieznany status.');
        }

        if (!$partial || array_key_exists('visibility', $payload)) {
            $payload['visibility'] = $payload['visibility'] ?? 'private';
            self::ensureAllowed($payload['visibility'], self::VISIBILITY, 'Nieznana widoczność.');
        }

        foreach (['start_at', 'end_at', 'recurrence_until'] as $dateField) {
            if (!$partial || array_key_exists($dateField, $payload)) {
                $payload[$dateField] = self::text($payload[$dateField] ?? null);
            }
        }

        if (!$partial || array_key_exists('start_at', $payload)) {
            if (empty($payload['start_at']) || strtotime((string) $payload['start_at']) === false) {
                json_response(['ok' => false, 'error' => 'Data rozpoczęcia jest wymagana.'], 422);
            }
            $payload['start_at'] = date('Y-m-d H:i:s', strtotime((string) $payload['start_at']));
        }

        if (array_key_exists('end_at', $payload) && $payload['end_at'] !== null) {
            if (strtotime((string) $payload['end_at']) === false) {
                json_response(['ok' => false, 'error' => 'Niepoprawna data zakończenia.'], 422);
            }
            $payload['end_at'] = date('Y-m-d H:i:s', strtotime((string) $payload['end_at']));
        }

        if (isset($payload['start_at'], $payload['end_at']) && $payload['end_at'] !== null && strtotime($payload['end_at']) < strtotime($payload['start_at'])) {
            json_response(['ok' => false, 'error' => 'Koniec nie może być przed startem.'], 422);
        }

        foreach (['location', 'notes', 'prep_note', 'shared_note'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = self::text($payload[$field]);
            }
        }

        foreach (['reminder_minutes', 'prep_reminder_minutes'] as $field) {
            if (!$partial || array_key_exists($field, $payload)) {
                $payload[$field] = max(0, (int) ($payload[$field] ?? 0));
            }
        }

        if (!$partial || array_key_exists('recurrence', $payload)) {
            $payload['recurrence'] = $payload['recurrence'] ?? 'none';
            self::ensureAllowed($payload['recurrence'], self::RECURRENCE, 'Nieznana cykliczność.');
        }

        if (array_key_exists('recurrence_until', $payload) && $payload['recurrence_until'] !== null) {
            if (strtotime((string) $payload['recurrence_until']) === false) {
                json_response(['ok' => false, 'error' => 'Niepoprawna data końca cykliczności.'], 422);
            }
            $payload['recurrence_until'] = date('Y-m-d', strtotime((string) $payload['recurrence_until']));
        }

        return $payload;
    }

    private static function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private static function ensureAllowed(mixed $value, array $allowed, string $message): void
    {
        if (!in_array($value, $allowed, true)) {
            json_response(['ok' => false, 'error' => $message], 422);
        }
    }
}
