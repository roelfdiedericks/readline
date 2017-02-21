<?php

namespace Ridzhi\Readline\Dropdown;


/**
 * Helper for stylish dropdown
 * All styles sets in CSI format
 *
 * Class DropdownStyle
 * @package Ridzhi\Readline
 */
class Theme implements ThemeInterface
{

    /**
     * @var array
     */
    protected $text;

    /**
     * @var array when elem active
     */
    protected $textActive;

    /**
     * @var array
     */
    protected $scrollbar;

    /**
     * @var array
     */
    protected $slider;

    /**
     * @param array $text
     * @return $this
     */
    public function setText(array $text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param array $textActive
     * @return $this
     */
    public function setTextActive(array $textActive)
    {
        $this->textActive = $textActive;

        return $this;
    }

    /**
     * @param array $scrollbar
     * @return $this
     */
    public function setScrollbar(array $scrollbar)
    {
        $this->scrollbar = $scrollbar;

        return $this;
    }

    /**
     * @param array $slider
     * @return $this
     */
    public function setSlider(array $slider)
    {
        $this->slider = $slider;

        return $this;
    }

    /**
     * @return array CSI format
     */
    public function getText(): array
    {
       return $this->text;
    }

    /**
     * @return array CSI format When elem is active
     */
    public function getTextActive(): array
    {
        return $this->textActive;
    }

    /**
     * @return array CSI format
     */
    public function getScrollbar(): array
    {
        return $this->scrollbar;
    }

    /**
     * @return array CSI format
     */
    public function getSlider(): array
    {
        return $this->slider;
    }
}