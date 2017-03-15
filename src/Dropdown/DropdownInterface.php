<?php

namespace Ridzhi\Readline\Dropdown;

/**
 * Interface DropdownInterface
 * @package Ridzhi\Readline\Dropdown
 */
interface DropdownInterface
{

    /**
     * @return string Current chosen
     */
    public function getSelect(): string;

    /**
     * @param int $width Full width of current representation
     * @return string Inline representation of dd
     */
    public function getView(& $width = 0): string;

    /**
     * @return int Height of viewport
     */
    public function getHeight(): int;

    /**
     * @return bool If starts navigation by dd
     */
    public function hasFocus(): bool;

    /**
     * Looped, 1 -> n
     */
    public function scrollUp();

    /**
     * Looped, n -> 1
     */
    public function scrollDown();

    /**
     * Remove focus, back to default state
     */
    public function reset();

}