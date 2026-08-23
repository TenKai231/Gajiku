<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/includes/auth.php';

requireAuth();

$user = currentUser();

http_response_code(200);
?>
<?php require dirname(__DIR__) . '/app/pages/dashboard.php'; ?>
