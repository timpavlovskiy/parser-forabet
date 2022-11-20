<?php
$startTime = microtime(true);
printf("[%s] Info: Проверка результатов \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

$startDate = new \DateTime('2020-01-01');
$endDate = new \DateTime('2020-04-10');

$command = 'php %s %s &';
$scriptName = dirname(__FILE__) . '/all.php';

while( $startDate <= $endDate ) {
    printf("[%s] Info: Результаты за дату: %s %s", date('d/M/Y H:i:s'), $startDate->format('Y-m-d'), PHP_EOL);

    exec(sprintf($command, $scriptName, $startDate->format('Y-m-d')));

    $startDate->add(new \DateInterval('P1D'));
}

printf("[%s] Info: Проверка результатов \t(%f) %s", date('d/M/Y H:i:s'), microtime(true) - $startTime, PHP_EOL);
echo PHP_EOL;