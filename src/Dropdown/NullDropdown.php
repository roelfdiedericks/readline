<?php

namespace Ridzhi\Readline\Dropdown;

/**
 * Class NullDropdown
 * @package Ridzhi\Readline\Dropdown
 */
class NullDropdown implements DropdownInterface
{

    /**
     * @return string Current chosen
     */
    public function getSelect(): string
    {
        return '';
    }

    /**
     * @param int $width Full width of current representation
     * @return string Inline representation of dd
     */
    public function getView(& $width = 0): string
    {
        return '';
    }

    /**
     * @return int Height of viewport
     */
    public function getHeight(): int
    {
        return 0;
    }

    /**
     * @return bool If starts navigation by dd
     */
    public function hasFocus(): bool
    {
        return false;
    }

    /**
     * Looped, 1 -> n
     */
    public function scrollUp()
    {

    }

    /**
     * Looped, n -> 1
     */
    public function scrollDown()
    {

    }

    /**
     * Remove focus, back to default state
     */
    public function reset()
    {

    }
}