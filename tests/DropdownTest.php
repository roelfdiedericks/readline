<?php

namespace Ridzhi\Readline\Tests;


use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\Dropdown\Dropdown;


class DropdownTest extends TestCase
{

    protected $obj;


    public function setUp()
    {
        $class = new \ReflectionClass(Dropdown::class);
        $this->obj = $class->newInstanceWithoutConstructor();
    }

}
