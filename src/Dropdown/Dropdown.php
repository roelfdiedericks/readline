<?php

namespace Ridzhi\Readline\Dropdown;

/**
 * Class Dropdown
 * @package Ridzhi\Readline\Dropdown
 */
class Dropdown extends BaseDropdown
{

    const POS_START = 0;

    /**
     * @var array list of items
     */
    protected $items = [];

    /**
     * @var int content size
     */
    protected $count;

    /**
     * @var int current pos
     */
    protected $pos = self::POS_START;

    /**
     * @var int dropdown's starting position
     */
    protected $offset = 0;

    /**
     * @var bool if scrolling first -> last
     */
    protected $reverse = false;

    /**
     * @var bool if select someone
     */
    protected $hasFocus = false;

    /**
     * @param array $items
     */
    public function setItems(array $items)
    {
        $this->reset();
        $this->items = array_values($items);
        $this->count = count($items);
    }

    /**
     * @return string Current chosen
     */
    public function getActiveItem(): string
    {
        return $this->items[$this->getPosActiveItem()];
    }

    /**
     * @param int $width Full width of current representation
     * @return string Inline representation of dd
     */
    public function getView(& $width = 0): string
    {
        $dict = $this->getCurrentDict();

        if (empty($dict)) {
            return '';
        }

        $output = '';
        $widthItem = max(array_map('mb_strlen', $dict));
        $scrollbar = ' ';
        $lineWidth = $width = strlen($this->getViewItem('', $widthItem) . $scrollbar);
        $lf = $this->getLF($lineWidth);

        $posActiveItem = $this->getPosActiveItem();
        $posScroll = $this->getPosScroll();

        foreach ($dict as $lineNumber => $lineValue) {
            $textStyle = (!$this->hasFocus() || $lineNumber !== $posActiveItem) ? $this->theme->getText() : $this->theme->getTextActive();
            $line = $this->getViewItem($lineValue, $widthItem);
            $output .= self::ansiFormat($line, $textStyle);
            $scrollbarStyle = ($lineNumber !== $posScroll) ? $this->theme->getScrollbar() : $this->theme->getSlider();
            $output .= self::ansiFormat($scrollbar, $scrollbarStyle) . $lf;
        }

        return $output;
    }

    /**
     * @return int Height of viewport
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return bool If starts navigation by dd
     */
    public function hasFocus(): bool
    {
        return $this->hasFocus;
    }

    /**
     * Looped, 1 -> n
     */
    public function scrollUp()
    {
        if (!$this->hasFocus()) {
            $this->hasFocus = true;
        }

        if ($this->pos === self::POS_START) {
            $this->reverse = true;
            $this->pos = $this->count - 1;
            $this->offset = $this->count - $this->height;
        } else {
            $this->pos--;

            if ($this->pos < $this->offset) {
                $this->offset--;
            }
        }
    }

    /**
     * Looped, n -> 1
     */
    public function scrollDown()
    {
        if (!$this->hasFocus()) {
            $this->hasFocus = true;

            return;
        }

        if ($this->pos === ($this->count - 1)) {
            $this->pos = self::POS_START;
            $this->reverse = false;
            $this->offset = 0;
        } else {
            $this->pos++;

            if ($this->pos > ($this->offset + $this->height - 1)) {
                $this->offset++;
            }
        }
    }

    /**
     * Remove focus, back to default state
     */
    public function reset()
    {
        $this->hasFocus = false;
        $this->reverse = false;
        $this->pos = self::POS_START;
        $this->offset = 0;
    }

    /**
     * Normalize by length + padding
     *
     * @param string $word
     * @param int $width
     * @return string
     */
    protected function getViewItem(string $word, int $width): string
    {
        return ' ' . str_pad($word, $width) . ' ';
    }

    /**
     * Linefeed in CSI format.
     * When we are drawn dropdown line, we just use this lf for right positioning cursor
     *
     * @param int $offset
     * @return string
     */
    protected function getLF(int $offset): string
    {
        // down 1 line and left $offset chars
        return "\033[1B" . "\033[{$offset}D";
    }

    /**
     * @return int relative pos in viewport, starts from 0
     */
    protected function getPosActiveItem(): int
    {
        return $this->pos - $this->offset;
    }

    /**
     * @return array
     */
    protected function getCurrentDict(): array
    {
        if (empty($this->items)) {
            return [];
        }

        return array_slice($this->items, $this->offset, $this->height);
    }

    /**
     * @return int
     */
    protected function getPosScroll(): int
    {
        if ($this->count <= $this->height) {
            //-1 as not exists lineNumber
            return -1;
        }

        if ($this->offset === 0) {
            return 0;
        }

        if ($this->offset === ($this->count - $this->height)) {
            return $this->height - 1;
        }

        $progress = $this->pos * (100 / $this->count);

        $pos = (int)floor($this->height * $progress / 100);

        if ($pos === 0) {
            return 1;
        }

        if ($pos === ($this->height - 1)) {
            return $pos - 1;
        }

        return $pos;
    }

    /**
     * @param string $string
     * @param array $format
     * @return string
     */
    protected static function ansiFormat(string $string, array $format = []): string
    {
        $code = implode(';', $format);

        return "\033[0m" . ($code !== '' ? "\033[" . $code . 'm' : '') . $string . "\033[0m";
    }

}