<?php

class ProxyHelper
{
    /**@var \PDO */
    private $pdo;

    private $parserId;

    private $proxies = [];

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function useParserId(int $parserId): self
    {
        $this->parserId = $parserId;

        return $this;
    }

    public function initialize(): self
    {
        $proxies = [];
        if (empty($this->parserId)) {
            $this->proxies = $proxies;
            return $this;
        }

        $query = 'SELECT p.`id`, p.`user`, p.`password`, p.`ip`, p.`port`,  p.`protocol`, pp.`parser_id` FROM `proxy` as p
                  INNER JOIN `proxy_parser` as pp ON p.`id` = pp.`proxy_id`
                  WHERE pp.`enable` = %d   AND p.`paid_time` > NOW()  AND pp.`parser_id` = %d;';
        $query = sprintf($query, 1, $this->parserId);
        $this->pdo
            ->query($query)
            ->fetchAll(
                \PDO::FETCH_FUNC,
                function ($id, $user, $password, $ip, $port, $protocol, $parserId) use (&$proxies) {
                    $proxy = [
                        'id' => $id,
                        'user' => $user,
                        'password' => $password,
                        'ip' => $ip,
                        'port' => $port,
                        'protocol' => $protocol,
                        'parserId' => (int)$parserId
                    ];
                    $proxies[$this->proxyConvertToUrl($proxy)] = $proxy;
                }
            );
        $this->proxies = $proxies;
        return $this;
    }

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

    public function getProxy(): string
    {
        return array_rand($this->proxies);
    }

    public function failProxy(string $proxy)
    {
        if (isset($this->proxies[$proxy])) {
            $rawProxy = $this->proxies[$proxy];
            $sql = 'UPDATE `proxy_parser` SET `count_failed` = `count_failed` + 1 WHERE parser_id = %d AND proxy_id = %d';
            $sql = sprintf($sql, (int)$this->parserId, (int)$rawProxy['id']);
            $this->pdo->exec($sql);
        }
    }
}