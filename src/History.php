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
     * @var bool
     */
    protected $firstIteration = true;

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
        if ($command !== end($this->history)) {
            $this->history[] = $command;
            next($this->history);
        }

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

        if ($this->firstIteration) {
            $this->firstIteration = false;

            return current($this->history);
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

        if ($this->firstIteration) {
            $this->firstIteration = false;
        }

        if (next($this->history) === false) {
            reset($this->history);
        }

        return current($this->history);
    }

}