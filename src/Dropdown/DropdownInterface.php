<?php

namespace Ridzhi\Readline\Dropdown;


interface DropdownInterface
{

    public function hide();

    public function show();

    public function scrollDown();

    public function scrollUp();

    public function resetScrolling();

    public function isActive(): bool;

    public function getSelect(): string;

    public function getView():string;

}