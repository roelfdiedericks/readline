<?php

namespace Ridzhi\Readline\Dropdown;


use Ridzhi\Readline\Dropdown\Themes\DefaultTheme;

class Dropdown implements DropdownInterface
{

    const POS_START = 0;

    /**
     * @var StyleInterface
     */
    protected $style;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var array list of items
     */
    protected $content = [];

    /**
     * @var int
     */
    protected $count;

    /**
     * @var int current pos, starts at self::POS_START
     */
    protected $pos = self::POS_START;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var bool If scrolling first -> last
     */
    protected $isReverse = false;

    /**
     * @var bool
     */
    protected $isActive = false;

    /**
     * Dropdown constructor.
     * @param int $height
     * @param StyleInterface|null $style
     */
    function __construct(int $height = 7, StyleInterface $style = null)
    {
        if ($style === null) {
            $style = new DefaultTheme();
        }

        $this->height = $height;
        $this->style = $style;
    }

    public function scrollUp()
    {
        if (!$this->isActive()) {
            $this->isActive = true;
        }

        if ($this->pos === self::POS_START) {
            $this->isReverse = true;
            $this->pos = $this->count - 1;
            $this->offset = $this->count - $this->height;
        } else {
            $this->pos--;

            if ($this->pos < $this->offset) {
                $this->offset--;
            }
        }

    }

    public function scrollDown()
    {
        if (!$this->isActive()) {
            $this->isActive = true;
            return;
        }

        if ($this->pos === ($this->count - 1)) {
            $this->pos = self::POS_START;
            $this->isReverse = false;
            $this->offset = 0;
        } else {
            $this->pos++;

            if ($this->pos > ($this->offset + $this->height - 1)) {
                $this->offset++;
            }
        }
    }

    public function setContent(array $content)
    {
        $this->resetScrolling();

        if (empty($content)) {
            return;
        }

        $this->content = array_values($content);
        $this->count = count($content);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getSelect(): string
    {
        return $this->content[$this->getPosRelative()];
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        $output = '';

        if (empty($this->content)) {
            return $output;
        }

        $dict = $this->getSlice();
        $width = max(array_map('mb_strlen', $dict));

        $scrollbar = ' ';
        $lineWidth = strlen($this->getViewItem('', $width) . $scrollbar);
        $lf = $this->getLF($lineWidth);

        $posRelative = $this->getPosRelative();
        $scrollPos = $this->getScrollPos();

        foreach ($dict as $key => $word) {
            $textStyle = (!$this->isActive() || $key !== $posRelative) ? $this->style->getText() : $this->style->getTextActive();
            $line = $this->getViewItem($word, $width);
            $output .= self::ansiFormat($line, $textStyle);
            $scrollbarStyle = ($key !== $scrollPos) ? $this->style->getScrollbar() : $this->style->getSlider();
            $output .= self::ansiFormat($scrollbar, $scrollbarStyle) . $lf;
        }

        return $output;
    }

    public function resetScrolling()
    {
        $this->isActive = false;
        $this->isReverse = false;
        $this->pos = 0;
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
     * @return int pos relative viewport
     */
    protected function getPosRelative(): int
    {
        return $this->pos - $this->offset;
    }

    /**
     * @return array
     */
    protected function getSlice(): array
    {
        return array_slice($this->content, $this->offset, $this->height);
    }

    /**
     * @return int
     */
    protected function getScrollPos(): int
    {
        $ratePerItem = 100 / $this->count;
        $progress = $this->pos * $ratePerItem;

        return floor(($this->height * $progress) / 100);
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