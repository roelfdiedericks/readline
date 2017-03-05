<?php

namespace Ridzhi\Readline\Dropdown;

/**
 * Interface ThemeInterface
 * @package Ridzhi\Readline\Dropdown
 */
interface ThemeInterface
{

    /**
     * @return array CSI format
     */
    public function getText(): array;

    /**
     * @return array CSI format When elem is active
     */
    public function getTextActive(): array;

    /**
     * @return array CSI format
     */
    public function getScrollbar(): array;

    /**
     * @return array CSI format
     */
    public function getSlider(): array;

}