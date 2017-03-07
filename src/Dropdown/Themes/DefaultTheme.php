<?php

namespace Ridzhi\Readline\Dropdown\Themes;

use Ridzhi\Readline\Dropdown\ThemeInterface;

/**
 * Class DefaultTheme
 * @package Ridzhi\Readline\Dropdown\Themes
 */
class DefaultTheme implements ThemeInterface
{

    /**
     * @return array CSI format
     */
    public function getText(): array
    {
        return [38, 2, 255, 255, 255, 46];
    }

    /**
     * @return array CSI format When elem is active
     */
    public function getTextActive(): array
    {
        return [38, 2, 0, 0, 0, 48, 2, 0, 180, 180];
    }

    /**
     * @return array CSI format
     */
    public function getScrollbar(): array
    {
        return [48, 2, 50, 50, 50];
    }

    /**
     * @return array CSI format
     */
    public function getSlider(): array
    {
        return [48, 2, 90, 90, 90];
    }

}