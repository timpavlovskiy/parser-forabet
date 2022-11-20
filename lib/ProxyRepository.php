<?php

class ProxyRepository
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var array
     */
    private $proxies = [];


    public function __construct(PDO $pdo, array $parsersIds)
    {
        $this->pdo = $pdo;
        if (!empty($parsersIds)) {
            $this->findProxiesByParserIds($parsersIds);
        }
    }

    /**
     * Возвращает массив проксей для загрузчика по конкретному парсеру
     * @param int $parserId
     * @return array
     */
    public function findProxiesByParserId(int $parserId): array
    {
        $proxies = [];
        foreach ($this->proxies as $proxy) {
            if ($proxy['parserId'] === $parserId) {
                $proxies[] = $this->proxyConvertToUrl($proxy);
            }
        }
        return $proxies;
    }

    /**
     * Обновляет прокси через которые не удалось загрузить данные
     * @param int $parserId
     * @param array $proxies
     * @return void
     */
    public function updateFailedProxies(int $parserId, array $proxies)
    {
        $hashMap = [];
        foreach ($proxies as $proxy) {
            $matches = [];
            preg_match('#(\d+\.\d+\.\d+\.\d+):(\d+)#', $proxy, $matches);
            $ip = $matches[1];
            $port = $matches[2];
            $hash = md5("{$ip}@{$port}");
            $hashMap[$hash] = $this->pdo->quote($hash);
        }

        $sql = 'UPDATE `proxy_parser`
                SET `count_failed` = `count_failed` + 1
                WHERE `parser_id` = %d AND `proxy_id` IN (SELECT p.`id`
                    FROM  `proxy` as p
                WHERE MD5(CONCAT_WS(\'@\', p.`ip`, p.`port`)) in (%s));';

        if (!empty($hashMap)) {
            $sql = sprintf($sql, $parserId, implode(',', $hashMap));
            $this->pdo->exec($sql);
        }
    }

    /**
     * Конвертируем прокси в url
     * @param array $proxy
     * @return string
     */
    private function proxyConvertToUrl(array $proxy): string
    {
        $password = $proxy['password'] ?? '';
        $user = $proxy['user'] ?? '';
        $userAndPassword = '';
        if (!empty($password) && !empty($user)) {
            $userAndPassword = "{$user}:{$password}@";
        } elseif (!empty($user)) {
            $userAndPassword = "{$user}@";
        }

        return "{$proxy['protocol']}://{$userAndPassword}{$proxy['ip']}:{$proxy['port']}";
    }

    /**
     * @param array $parserIds
     * @return void
     */
    private function findProxiesByParserIds(array $parserIds)
    {
        $proxies = [];
        if (empty($parserIds)) {
            $this->proxies = $proxies;
            return;
        }

        $query = 'SELECT p.`id`, p.`user`, p.`password`, p.`ip`, p.`port`,  p.`protocol`, pp.`parser_id` FROM `proxy` as p
                  INNER JOIN `proxy_parser` as pp ON p.`id` = pp.`proxy_id`
                  WHERE pp.`enable` = %d   AND p.`paid_time` > NOW()  AND pp.`parser_id` IN (%s);';
        $query = sprintf($query, 1, implode(',', $parserIds));
        $this->pdo
            ->query($query)
            ->fetchAll(
                \PDO::FETCH_FUNC,
                function ($id, $user, $password, $ip, $port, $protocol, $parserId) use (&$proxies) {
                    $proxies[] = [
                        'id' => $id,
                        'user' => $user,
                        'password' => $password,
                        'ip' => $ip,
                        'port' => $port,
                        'protocol' => $protocol,
                        'parserId' => (int)$parserId
                    ];
                }
            );
        $this->proxies = $proxies;
    }

}