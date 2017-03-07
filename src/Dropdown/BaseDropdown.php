<?php

namespace Ridzhi\Readline\Dropdown;

/**
 * Class BaseDropdown
 * @package Ridzhi\Readline\Dropdown
 */
abstract class BaseDropdown implements DropdownInterface
{

    /**
     * @var ThemeInterface
     */
    protected $theme;

    /**
     * @var int height of dropdown
     */
    protected $maxHeight;

    /**
     * BaseDropdown constructor.
     * @param ThemeInterface $theme
     * @param int $height
     */
    function __construct(ThemeInterface $theme, int $height)
    {
        $this->theme = $theme;
        $this->maxHeight = $height;
    }

}