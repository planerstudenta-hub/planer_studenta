<?php

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

try {
    $user = require_user();
    $service = new FriendService(App::db());
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        json_response(['ok' => true, 'data' => $service->summary((int) $user['id'])]);
    }

    $input = json_input();

    if ($method === 'POST') {
        $service->sendRequest((int) $user['id'], (string) ($input['email'] ?? ''));
        json_response(['ok' => true]);
    }

    if ($method === 'PUT') {
        $service->respond((int) $user['id'], (int) ($input['friendship_id'] ?? 0), (string) ($input['action'] ?? ''));
        json_response(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $service->remove((int) $user['id'], (int) ($_GET['id'] ?? 0));
        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => 'Metoda nieobsługiwana.'], 405);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
}
