<?php
require_once __DIR__ . '/config/bootstrap.php';

auth_guard();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function readVisitCounter(string $filePath): int
{
    if (!file_exists($filePath)) {
        return 0;
    }

    $fp = fopen($filePath, 'r');
    if (!$fp) {
        return 0;
    }

    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return (int) trim((string) $content);
}

$count = readVisitCounter(__DIR__ . '/storage/catalog_visits.txt');

echo json_encode([
    'success' => true,
    'count'   => $count,
], JSON_UNESCAPED_UNICODE);