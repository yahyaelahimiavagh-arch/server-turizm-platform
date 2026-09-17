<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

require_login();

if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

verify_csrf_or_fail();
logout_user();
redirect('login.php');
