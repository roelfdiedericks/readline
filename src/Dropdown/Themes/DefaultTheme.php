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
        return [37, 46, 1];
    }

    /**
     * @return array CSI format When elem is active
     */
    public function getTextActive(): array
    {
        return [30, 46, 1];
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