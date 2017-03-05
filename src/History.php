<?php

namespace Ridzhi\Readline;

/**
 * Class History
 * @package Ridzhi\Readline
 */
class History
{

    /**
     * @var array
     */
    protected $history = [];

    /**
     * @param string $command
     */
    public function add(string $command)
    {
        $command = trim($command);

        if (empty($command)) {
            return;
        }

        //don't adding repeated
        end($this->history);

        if ($command !== current($this->history)) {
            $this->history[] = $command;
        }

        reset($this->history);
    }

    /**
     * if history is empty return ''
     * @return string
     */
    public function prev(): string
    {
        if (empty($this->history)) {
            return '';
        }

        if (prev($this->history) === false) {
            end($this->history);
        }

        return current($this->history);
    }

    /**
     * if history is empty return ''
     * @return string
     */
    public function next(): string
    {
        if (empty($this->history)) {
            return '';
        }

        if (next($this->history) === false) {
            reset($this->history);
        }

        return current($this->history);
    }

}