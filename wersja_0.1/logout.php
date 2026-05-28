<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

auth()->logout();
redirect('login.php');

