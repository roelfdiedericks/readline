<?php

namespace Ridzhi\Readline\Tests;


use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\Buffer;


class BufferTest extends TestCase
{

    /**
     * @var Buffer
     */
    protected $obj;

    public function setUp()
    {
        $class = new \ReflectionClass(Buffer::class);
        $this->obj = $class->newInstanceWithoutConstructor();
    }

    /**
     * @cover Buffer::setPrompt()
     */
    public function testPrompt()
    {
        $method = new \ReflectionMethod(Buffer::class, 'setPrompt');
        $prompt = 'readline >';
        $method->invoke($this->obj, $prompt);
        $property = new \ReflectionProperty(Buffer::class, 'prompt');
        $property->setAccessible(true);

        Assert::assertSame($prompt, $property->getValue($this->obj));
    }

    /**
     * @cover Buffer::reset()
     */
    public function testReset()
    {
        $method = new \ReflectionMethod(Buffer::class, 'reset');
        $this->obj->insert('command');
        $method->invoke($this->obj);

        Assert::assertSame('', $this->obj->getFull());
        Assert::assertSame(0, $this->obj->getPos());
    }

    /**
     * @cover Buffer::getPos()
     */
    public function testGetPos()
    {
        $this->obj->insert('command');

        Assert::assertSame(7, $this->obj->getPos());
    }

    /**
     * @cover Buffer::getFull()
     */
    public function testGetFull()
    {
        $this->obj->insert('command');
        $this->obj->cursorNext(1, true);
        $this->obj->insert('action');

        Assert::assertSame('command action', $this->obj->getFull());
    }

    /**
     * @cover Buffer::getCurrent()
     */
    public function testGetCurrent()
    {
        $this->obj->insert('command');
        $this->obj->cursorPrev(2);

        Assert::assertSame('comma', $this->obj->getCurrent());
    }

    /**
     * @cover Buffer::getLength()
     */
    public function testGetLength()
    {
        $this->obj->insert('command');

        Assert::assertSame(7, $this->obj->getLength());
    }

    /**
     * @cover Buffer::cursorToBegin()
     */
    public function cursorToBegin()
    {
        $this->obj->insert('command');
        $this->obj->cursorToBegin();

        Assert::assertSame(0, $this->obj->getPos());
    }

    /**
     * @cover Buffer::cursorToEnd()
     */
    public function testCursorToEnd()
    {
        $this->obj->insert('command');
        $this->obj->cursorPrev(3);
        $this->obj->cursorToEnd();

        Assert::assertSame(7, $this->obj->getPos());
    }

    /**
     * @param string $insert
     * @param int $steps
     * @param int $expected
     *
     * @cover Buffer::cursorPrev()
     * @dataProvider cursorPrevProvider
     */
    public function testCursorPrev(string $insert, int $steps, int $expected)
    {
        $this->obj->insert($insert);
        $this->obj->cursorPrev($steps);

        Assert::assertSame($expected, $this->obj->getPos());
    }

    /**
     * @param string $value
     * @param string $insert
     * @param int $offset
     * @param string $expected
     *
     * @cover Buffer::insertString()
     * @dataProvider insertStringProvider
     */
    public function testInsertString(string $value, string $insert, int $offset, string $expected)
    {
        $method = new \ReflectionMethod(Buffer::class, 'insertString');
        $method->setAccessible(true);
        $this->obj->insert($value);
        $this->obj->cursorPrev($offset);
        $method->invoke($this->obj, $insert);

        Assert::assertSame($expected, $this->obj->getFull());
    }

    public function insertStringProvider()
    {
        $expected = '123456789';

        return [
            'begin' => ['456789', '123', 6, $expected],
            'middle' => ['123789', '456', 3, $expected],
            'end' => ['123456', '789', 0, $expected]
        ];
    }

    public function cursorPrevProvider()
    {
        $insert = 'command';

        return [
            'one step' => [$insert, 1, 6],
            'some steps' => [$insert, 3, 4],
            'out of bound' => [$insert, 30, 0]
        ];
    }

}
