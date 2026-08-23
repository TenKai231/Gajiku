<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/includes/auth.php';

requireAuth();

// Panggil file UI/Logic utama dari app/pages/
require dirname(__DIR__) . '/app/pages/karyawan/index.php';
