<?php

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

try {
    $user = require_user();
    $service = new EventService(App::db());
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $from = new DateTimeImmutable($_GET['from'] ?? 'first day of this month 00:00:00');
        $to = new DateTimeImmutable($_GET['to'] ?? 'last day of this month 23:59:59');
        $filters = [
            'type' => $_GET['type'] ?? 'all',
            'status' => $_GET['status'] ?? 'all',
            'search' => $_GET['search'] ?? '',
        ];

        json_response([
            'ok' => true,
            'events' => $service->listForUser((int) $user['id'], $filters, $from, $to),
        ]);
    }

    if ($method === 'POST') {
        $id = $service->create((int) $user['id'], json_input());
        json_response(['ok' => true, 'id' => $id], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'Brak ID wydarzenia.'], 400);
        }
        $service->update((int) $user['id'], $id, json_input());
        json_response(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'Brak ID wydarzenia.'], 400);
        }
        $service->delete((int) $user['id'], $id);
        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => 'Metoda nieobsługiwana.'], 405);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
}
