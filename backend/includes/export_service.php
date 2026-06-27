<?php
declare(strict_types=1);

function streamCsv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        return;
    }

    fputcsv($output, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($output, $row, ';');
    }
    fclose($output);
}
