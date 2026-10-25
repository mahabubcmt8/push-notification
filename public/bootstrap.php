<?php
// Shared by the JSON endpoints: never leak HTML errors into a JSON response.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) {
    error_log($e);                       // full details go to the PHP error log / terminal
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['error' => $e->getMessage()]);
});

/**
 * Locate the folder that holds config.php, db.php and vendor/, no matter how
 * this project was deployed:
 *   - "private" subfolder next to this file  (recommended: everything sits
 *     in one folder on shared hosting, e.g. public_html/, with the sensitive
 *     files in public_html/private/ protected by private/.htaccess)
 *   - this file's own folder                  (config.php copied alongside)
 *   - one level up                            (the original public/ layout,
 *     e.g. running `php -S localhost:8000 -t public` for local development)
 */
function app_root(string $from): string
{
    foreach ([$from . '/private', $from, dirname($from)] as $candidate) {
        if (is_file($candidate . '/config.php')) {
            return $candidate;
        }
    }
    throw new RuntimeException('Could not locate config.php — check your folder layout.');
}