<?php

class File
{
    /**
     * Загружаем файл и проверяем ttl при необходимости
     *
     * @param $filepath
     * @param null $ttl
     *
     * @return array|false|mixed|string
     * @throws \Exception
     */
    public function load($filepath, $ttl = null)
    {
        if( file_exists($filepath) === false ) {
            throw new \Exception('Not found file: ' . $filepath);
        }

        $content = file_get_contents($filepath);
        $content = json_decode($content, true);
        $content = $content ?? [];

        $data = $content['data'] ?? [];
        $ts = $content['ts'] ?? 0;

        if( !is_null($ttl) ) {
            $currentTS = time();

            if( ($currentTS - $ts) > $ttl ) {
                return [];
            }
        }

        return $data;
    }

    /**
     * Сохроняем данные в файл с ts меткой и текущей датой
     *
     * @param $filepath
     * @param $data
     */
    public function save($filepath, $data)
    {
        $content = [
            'date' => date('d/M/Y H:i:s'),
            'ts'   => time(),
            'data' => $data
        ];

        file_put_contents($filepath, json_encode($content, JSON_UNESCAPED_UNICODE));
    }
}