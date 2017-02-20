<?php

namespace Ridzhi\Readline;


/**
 * Logic representation of CLI input
 *
 * Class Buffer
 * @package Ridzhi\Readline
 */
class Buffer
{

    /**
     * @var int
     */
    protected $pos = 0;

    /**
     * @var string
     */
    protected $input = '';

    /**
     * @var string
     */
    protected $prompt = '';

    /**
     * @param string $prompt
     */
    public function setPrompt(string $prompt)
    {
        $this->prompt = $prompt;
    }

    public function reset()
    {
        $this->input = '';
        $this->pos = 0;
    }

    /**
     * @return int
     */
    public function getPos(): int
    {
        return strlen($this->prompt) + $this->pos;
    }

    /**
     * @return string
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    /**
     * @return string
     */
    public function getInput(): string
    {
        return $this->input;
    }

    /**
     * @return string
     */
    public function getInputCurrent(): string
    {
        return substr($this->input, 0, $this->pos);
    }

    public function insert(string $value)
    {
        $this->insertToInput($value);
        $this->cursorNext(strlen($value));
    }

    public function cursorToEnd()
    {
        $this->pos = $this->getLength();
    }

    public function cursorToBegin()
    {
        $this->pos = 0;
    }

    public function cursorPrev(int $step = 1)
    {
        $this->pos -= $step;

        if ($this->pos < 0) {
            $this->pos = 0;
        }
    }

    public function cursorNext(int $step = 1)
    {
        $max = $this->getLength();
        $this->pos += $step;

        if ($this->pos > $max) {
            $offset = $this->pos - $max;
            $this->pos = $max;
            $this->insert(str_repeat(" ", $offset));
        }
    }

    public function removeChar(bool $left = true)
    {
        if ($left) {
            if (!$this->isEmpty()) {
                $this->input =  substr($this->input, 0, $this->pos - 1) . $this->getInputNext();
                $this->cursorPrev();
            }
        } elseif(!$this->isEnd()) {
            $this->cursorNext();
            $this->removeChar();
        }

    }

    /**
     * @param string $value
     */
    protected function insertToInput(string $value)
    {
        $this->input = implode("", [$this->getInputCurrent(), $value, $this->getInputNext()]);
    }

    /**
     * @return int
     */
    protected function getLength(): int
    {
        return strlen($this->input);
    }

    /**
     * @return bool
     */
    protected function isEmpty(): bool
    {
        return $this->input === '';
    }

    /**
     * @return bool
     */
    protected function isEnd(): bool
    {
        return $this->pos === $this->getLength();
    }

    /**
     * @return string
     */
    protected function getInputNext(): string
    {
        return substr($this->input, $this->pos);
    }
    
}