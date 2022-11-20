<?php

namespace PaserFonbet\language;

class Logger
{

    private $startTime;

    private $showSeconds = false;

    /**
     * Logger constructor.
     */
    public function __construct()
    {
        $this->resetStartTime();
    }

    public function showSeconds(): self
    {
        $this->showSeconds = true;

        return $this;
    }

    public function resetStartTime(): self
    {
        $this->startTime = microtime(true);
        return $this;
    }

    public function error(string $message)
    {
        $this->print(
            "[%s] Error: %s",
            $message
        );
    }

    public function info(string $message)
    {
        $this->print(
            "[%s] Info: %s",
            $message
        );
    }

    private function print($template, string $message)
    {
        $date = date('d/M/Y H:i:s');
        $seconds = microtime(true) - $this->startTime;

        //\t(%f seconds) %s
        if ($this->showSeconds) {
            printf(
                $template . " \t(%f seconds) %s",
                $date,
                $message,
                $seconds,
                PHP_EOL
            );
            $this->showSeconds = false;
        } else {
            printf(
                $template . "%s",
                $date,
                $message,
                PHP_EOL
            );
        }
        //"[%s] Info: %s \t(%f seconds) %s" - пример шаблона

    }

}