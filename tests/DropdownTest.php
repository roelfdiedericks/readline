<?php
/**
 * Created by PhpStorm.
 * User: ridzhi
 * Date: 15.02.17
 * Time: 2:05
 */

namespace Ridzhi\Readline\Tests;


use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\Dropdown;


class DropdownTest extends TestCase
{

    protected $obj;


    public function setUp()
    {
        $class = new \ReflectionClass(Dropdown::class);
        $this->obj = $class->newInstanceWithoutConstructor();
    }

}
