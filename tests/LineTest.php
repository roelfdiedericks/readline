<?php

namespace Ridzhi\Readline\Tests;


use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\Line;


class LineTest extends TestCase
{

    /**
     * @var Line
     */
    protected $obj;

    public function setUp()
    {
        $class = new \ReflectionClass(Line::class);
        $this->obj = $class->newInstanceWithoutConstructor();
    }

    /**
     * @cover Line::clear()
     */
    public function testClear()
    {
        $method = new \ReflectionMethod(Line::class, 'clear');
        $this->obj->insert('command');
        $method->invoke($this->obj);

        Assert::assertSame('', $this->obj->getFull());
        Assert::assertSame(0, $this->obj->getCursorPos());
    }

    /**
     * @cover Line::getCursorPos()
     */
    public function testGetCursorPos()
    {
        $this->obj->insert('command');

        Assert::assertSame(7, $this->obj->getCursorPos());
    }

    /**
     * @cover Line::getFull()
     */
    public function testGetFull()
    {
        $this->obj->insert('command');
        $this->obj->cursorNext(1, true);
        $this->obj->insert('action');

        Assert::assertSame('command action', $this->obj->getFull());
    }

    /**
     * @cover Line::getCurrent()
     */
    public function testGetCurrent()
    {
        $this->obj->insert('command');
        $this->obj->cursorPrev(2);

        Assert::assertSame('comma', $this->obj->getCurrent());
    }

    /**
     * @cover Line::getLength()
     */
    public function testGetLength()
    {
        $this->obj->insert('command');

        Assert::assertSame(7, $this->obj->getLength());
    }

    /**
     * @cover Line::cursorToBegin()
     */
    public function cursorToBegin()
    {
        $this->obj->insert('command');
        $this->obj->cursorToBegin();

        Assert::assertSame(0, $this->obj->getCursorPos());
    }

    /**
     * @cover Line::cursorToEnd()
     */
    public function testCursorToEnd()
    {
        $this->obj->insert('command');
        $this->obj->cursorPrev(3);
        $this->obj->cursorToEnd();

        Assert::assertSame(7, $this->obj->getCursorPos());
    }

    /**
     * @param string $insert
     * @param int $steps
     * @param int $expected
     *
     * @cover Line::cursorPrev()
     * @dataProvider cursorPrevProvider
     */
    public function testCursorPrev(string $insert, int $steps, int $expected)
    {
        $this->obj->insert($insert);
        $this->obj->cursorPrev($steps);

        Assert::assertSame($expected, $this->obj->getCursorPos());
    }


    /**
     * @param string $insert
     * @param int $start
     * @param int $steps
     * @param bool $extend
     * @param int $expected
     *
     * @cover Line::cursorNext()
     * @dataProvider cursorNextProvider
     */
    public function testCursorNext(string $insert, int $start, int $steps, bool $extend, int $expected)
    {
        $this->obj->insert($insert);
        $pos = new \ReflectionProperty(Line::class, 'pos');
        $pos->setAccessible(true);
        $pos->setValue($this->obj, $start);

        $this->obj->cursorNext($steps, $extend);

        Assert::assertSame($expected, $this->obj->getCursorPos());
    }

    /**
     * @param string $insert
     * @param int $start
     * @param int $steps
     * @param int $expectedPos
     * @param string $expectedBuffer
     * @internal param int $expected
     *
     * @cover Line::cursorNext()
     * @dataProvider cursorNextExtendProvider
     */
    public function testCursorNextExtend(string $insert, int $start, int $steps, int $expectedPos, string $expectedBuffer)
    {
        $this->obj->insert($insert);
        $pos = new \ReflectionProperty(Line::class, 'pos');
        $pos->setAccessible(true);
        $pos->setValue($this->obj, $start);

        $this->obj->cursorNext($steps, true);

        Assert::assertSame($expectedPos, $this->obj->getCursorPos());
        Assert::assertSame($expectedBuffer, $this->obj->getFull());
    }

    /**
     * @param string $insert
     * @param int $offset
     * @param int $expectedPos
     * @param string $expectedBuffer
     *
     * @cover Line::backspace()
     * @dataProvider backspaceProvider()
     */
    public function testBackspace(string $insert, int $offset, int $expectedPos, string $expectedBuffer)
    {
        $this->obj->insert($insert);
        $this->obj->cursorPrev($offset);
        $this->obj->backspace();

        Assert::assertSame($expectedPos, $this->obj->getCursorPos());
        Assert::assertSame($expectedBuffer, $this->obj->getFull());
    }

    /**
     * @param string $insert
     * @param int $offset
     * @param int $expectedPos
     * @param string $expectedBuffer
     *
     * @cover Line::delete()
     * @dataProvider deleteProvider
     */
    public function testDelete(string $insert, int $offset, int $expectedPos, string $expectedBuffer)
    {
        $this->obj->insert($insert);
        $this->obj->cursorPrev($offset);
        $this->obj->delete();

        Assert::assertSame($expectedPos, $this->obj->getCursorPos());
        Assert::assertSame($expectedBuffer, $this->obj->getFull());
    }

    /**
     * @cover Line::slice()
     */
    public function testSlice()
    {
        $method = new \ReflectionMethod(Line::class, 'slice');
        $method->setAccessible(true);

        $this->obj->insert('command');

        Assert::assertSame('mm', $method->invoke($this->obj, 2, 2));
    }

    /**
     * @param string $value
     * @param string $insert
     * @param int $offset
     * @param string $expected
     *
     * @cover Line::insertString()
     * @dataProvider insertStringProvider
     */
    public function testInsertString(string $value, string $insert, int $offset, string $expected)
    {
        $method = new \ReflectionMethod(Line::class, 'insertString');
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
            //string $value, string $insert, int $offset, string $expected
            'begin' => ['456789', '123', 6, $expected],
            'middle' => ['123789', '456', 3, $expected],
            'end' => ['123456', '789', 0, $expected]
        ];
    }

    public function cursorPrevProvider()
    {
        $insert = 'command';

        return [
            //string $insert, int $steps, int $expected
            'one step' => [$insert, 1, 6],
            'some steps' => [$insert, 3, 4],
            'out of bounds' => [$insert, 30, 0]
        ];
    }

    public function cursorNextProvider()
    {
        $insert = 'command';

        return [
            //string $insert, int $start, int $steps, bool $extend, int $expected
            'one step' => [$insert, 3, 1, false, 4],
            'some steps' => [$insert, 3, 3, false, 6],
            'out of bounds' => [$insert, 3, 30, false, 7],
            'out of bounds with extend' => [$insert, 3, 10, true, 13]
        ];
    }

    public function cursorNextExtendProvider()
    {
        $insert = 'command';

        return [
            //string $insert, int $start, int $steps, int $expectedPos, string $expectedBuffer
            'one step' => [$insert, 3, 1, 4, 'command'],
            'some steps' => [$insert, 3, 3, 6, 'command'],
            'out of bounds' => [$insert, 3, 10, 13, 'command      '],
        ];
    }

    public function backspaceProvider()
    {
        $insert = 'command';
        return [
            //string $insert, int $offset, int $expectedPos, string $expectedBuffer
            'from end' => [$insert, 0, 6, 'comman'],
            'from middle' => [$insert, 3, 3, 'comand'],
            'from begin' => [$insert, 7, 0, 'command'],
        ];
    }

    public function deleteProvider()
    {
        $insert = 'command';
        return [
            //string $insert, int $offset, int $expectedPos, string $expectedBuffer
            'from end' => [$insert, 0, 7, 'command'],
            'from middle' => [$insert, 3, 4, 'commnd'],
            'from begin' => [$insert, 7, 0, 'ommand'],
        ];
    }
}
