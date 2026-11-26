<?php
// Temporary diagnostic wrapper for notifications.php.
// Keep syntax compatible with older PHP versions.
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('html_errors', '0');
echo "CHECK FILE STARTED\n";
try {
    require __DIR__ . '/notifications.php';
} catch (ParseError $e) {
    if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
    http_response_code(200);
    echo "\nCAUGHT PARSE ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . basename($e->getFile()) . "\n";
    echo "LINE: " . $e->getLine() . "\n";
} catch (Throwable $e) {
    if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
    http_response_code(200);
    echo "\nCAUGHT PHP ERROR: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "FILE: " . basename($e->getFile()) . "\n";
    echo "LINE: " . $e->getLine() . "\n";
}
