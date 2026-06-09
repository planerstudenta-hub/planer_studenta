<?php

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

try {
    $user = require_user();
    $service = new EventService(App::db());

    json_response([
        'ok' => true,
        'reminders' => $service->remindersForUser((int) $user['id']),
    ]);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
}
