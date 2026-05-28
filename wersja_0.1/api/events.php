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
        json_response(['ok' => true, 'events' => $service->listForUser((int) $user['id'], $from, $to)]);
    }

    if ($method === 'POST') {
        $id = $service->create((int) $user['id'], json_input());
        json_response(['ok' => true, 'id' => $id], 201);
    }

    if ($method === 'PUT') {
        $service->update((int) $user['id'], (int) ($_GET['id'] ?? 0), json_input());
        json_response(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $service->delete((int) $user['id'], (int) ($_GET['id'] ?? 0));
        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => 'Metoda nieobslugiwana.'], 405);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
}

