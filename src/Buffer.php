<?php

namespace Ridzhi\Readline;

use Hoa\Console\Window;


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
        $this->buffer = '';
        $this->pos = 0;
    }

    /**
     * @return int
     */
    public function getPos(): int
    {
        return $this->pos;
    }

    /**
     * @param bool $prompt
     * @return string
     */
    public function get(bool $prompt = false): string
    {
        if ($prompt) {
            return $this->prompt . $this->buffer;
        }

        return $this->buffer;
    }

    /**
     * @return string
     */
    public function getInputTail(): string
    {
        return mb_substr($this->buffer, $this->pos);
    }

    /**
     * @return string
     */
    public function getInputCurrent(): string
    {
        return mb_substr($this->buffer, 0, $this->pos);
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
     * @return bool
     */
    public function cursorPrev(int $step = 1): bool
    {
        $this->pos -= $step;

        if ($this->pos < 0) {
            $this->pos = 0;

            return false;
        }

        return true;
    }

    /**
     * @param int $step
     * @param bool $extend
     * @return bool
     */
    public function cursorNext(int $step = 1, $extend = false): bool
    {
        $max = $this->getLength();
        $this->pos += $step;

        $isOutOfBounds = $this->pos > $max;

        if (!$isOutOfBounds) {
            return true;
        }

        if ($extend) {
            $offset = $this->pos - $max;
            $this->pos = $max;
            $this->insert(str_repeat(" ", $offset));

            return true;
        }

        $this->pos = $max;

        return false;
    }

    /**
     * Remove char before cursor
     *
     * @return bool
     */
    public function backspace(): bool
    {
        if ($this->isEmpty() || $this->pos === 0) {
            return false;
        }

        $this->buffer = mb_substr($this->buffer, 0, $this->pos - 1) . $this->getInputTail();

        return $this->cursorPrev();
    }

    /**
     * Remove char after cursor
     *
     * @return bool
     */
    public function delete(): bool
    {
        if ($this->isEnd()) {
            return false;
        }

        $this->cursorNext();

        return $this->backspace();
    }

    /**
     * @return bool
     */
    public function isEnd(): bool
    {
        return $this->pos === $this->getLength();
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return mb_strlen($this->buffer);
    }

    /**
     * @param int $x
     * @param int $y
     * @return array
     */
    protected function getTerminalPos(int $x, int $y): array
    {
        $width = Window::getSize()['x'];
        $pos = $this->getPos();

        $offsetY = floor($pos / $width);
        $offsetX = $pos - ($offsetY * $width);

        return [$x + $offsetX, $y + $offsetY];
    }

    /**
     * @param string $value
     */
    protected function insertString(string $value)
    {
        $this->buffer = implode("", [$this->getInputCurrent(), $value, $this->getInputTail()]);
    }

    /**
     * @return bool
     */
    protected function isEmpty(): bool
    {
        return $this->buffer === '';
    }

}