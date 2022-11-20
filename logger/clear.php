<?php
$logFiles = [
    'loader.log',
    'resultLive.log',
    'resultAll.log',
    'parser.log',
    'updater.log',
    'video.log',
];

$notices = [];
$errors = [];

$noticeFilename = __DIR__ . '/notice.json';
$errorFilename = __DIR__ . '/error.json';

if( file_exists($noticeFilename) ) {
    $data = file_get_contents($noticeFilename);
    $notices = json_decode($data, true) ?? [];
}

if( file_exists($errorFilename) ) {
    $data = file_get_contents($errorFilename);
    $errors = json_decode($data, true) ?? [];
}

foreach ( $logFiles as $file ) {
    $filePath = dirname(__DIR__) . '/' . $file;

    if( file_exists($filePath) === false ) {
        continue;
    }

    $handle = fopen($filePath, 'r+');

    if ($handle) {
        while (($row = fgets($handle)) !== false) {
            $row = preg_replace('#\[\d+/\w+/\d+\s+\d+:\d+:\d+\]\s+#', '', $row);

            $hash = substr(md5($row), 0 ,6);

            $hasNotice = preg_match('#Notice:#', $row);
            $hasError = preg_match('#Error:#', $row);

            if( !empty($hasNotice) ) {
                $notices[$hash] = trim($row);
            }

            if( !empty($hasError) ) {
                $errors[$hash] = trim($row);
            }
        }

        ftruncate($handle, 0);
        rewind($handle);
        fclose($handle);
    }
}

file_put_contents($noticeFilename, json_encode($notices, JSON_UNESCAPED_UNICODE));
file_put_contents($errorFilename, json_encode($errors, JSON_UNESCAPED_UNICODE));