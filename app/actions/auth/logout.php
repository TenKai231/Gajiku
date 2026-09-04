<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';

logoutUser();
redirect('/login.php?logout=1');
