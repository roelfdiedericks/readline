<?php

namespace Ridzhi\Readline;


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
    public function getCurrent(): string
    {
        return substr($this->input, 0, $this->pos);
    }

    public function insert(string $value)
    {
        $this->input = implode("", [$this->getCurrent(), $value, substr($this->input, $this->pos)]);
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

    /**
     * TODO mb remain only reverse version
     * @param bool $reverse
     * @return int
     */
    public function getPos(bool $reverse = false): int
    {
        return ($reverse) ? $this->getLength() - $this->pos : $this->pos;
    }

    public function removeChar(bool $left = true)
    {
        if ($left) {
            if (!$this->isEmpty()) {
                $this->input =  substr($this->input, 0, $this->pos - 1) . substr($this->input, $this->pos);
                $this->cursorPrev();
            }
        } elseif(!$this->isEnd()) {
            $this->cursorNext();
            $this->removeChar();
        }

    }

    protected function getLength()
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

}