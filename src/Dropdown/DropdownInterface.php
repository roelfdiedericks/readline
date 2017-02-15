<?php

namespace Ridzhi\Readline\Dropdown;


interface DropdownInterface
{

    public function scrollDown();
    public function scrollUp();

    public function isActive(): bool;

    public function getSelect(): string;

    public function getView():string;

}