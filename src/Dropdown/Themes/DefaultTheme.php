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
        return [30, 48, 5, 14];
    }

    /**
     * @return array CSI format
     */
    public function getScrollbar(): array
    {
        return [47];
    }

    /**
     * @return array CSI format
     */
    public function getSlider(): array
    {
        return [48, 5, 241];
    }

}