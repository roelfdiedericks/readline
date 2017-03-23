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
    protected $buffer = '';

    public function reset()
    {
        $this->buffer = '';
        $this->cursorToBegin();
    }

    /**
     * @return int
     */
    public function getPos(): int
    {
        return $this->pos;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return mb_strlen($this->buffer);
    }

    /**
     * @return string Line buffer
     */
    public function getFull(): string
    {
        return $this->buffer;
    }

    /**
     * @return string
     */
    public function getCurrent(): string
    {
        return $this->slice(0, $this->pos);
    }

    /**
     * @param string $value
     */
    public function insert(string $value)
    {
        $this->insertString($value);
        $this->cursorNext(mb_strlen($value));
    }

    public function cursorToEnd()
    {
        $this->pos = $this->getLength();
    }

    public function cursorToBegin()
    {
        $this->pos = 0;
    }

    /**
     * @param int $step
     */
    public function cursorPrev(int $step = 1)
    {
        $this->pos -= $step;

        if ($this->pos < 0) {
            $this->cursorToBegin();
        }
    }

    /**
     * @param int $step
     * @param bool $extend
     */
    public function cursorNext(int $step = 1, $extend = false)
    {
        $max = $this->getLength();
        $this->pos += $step;

        if ($this->pos <= $max) {
            return;
        }

        if ($extend) {
            $offset = $this->pos - $max;
            $this->insert(str_repeat(" ", $offset));

            return;
        }

        $this->pos = $max;
    }

    /**
     * Remove char before cursor
     */
    public function backspace()
    {
        if ($this->buffer !== '' && $this->pos > 0) {
            $this->buffer = $this->slice(0, $this->pos - 1) . $this->slice($this->pos);
            $this->cursorPrev();
        }
    }

    /**
     * Remove char after cursor
     */
    public function delete()
    {
        if ($this->pos !== $this->getLength()) {
            $this->cursorNext();
            $this->backspace();
        }
    }

    /**
     * @param int $start
     * @param int|null $length
     * @return string
     */
    protected function slice(int $start, $length = null): string
    {
        return mb_substr($this->buffer, $start, $length);
    }

    /**
     * @param string $value
     */
    protected function insertString(string $value)
    {
        $this->buffer = $this->getCurrent() . $value . $this->slice($this->pos);
    }

}