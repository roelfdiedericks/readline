<?php

namespace Ridzhi\Readline\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\History;

class HistoryTest extends TestCase
{

    /**
     * @var History
     */
    protected $obj;

    public function setUp()
    {
        $class = new \ReflectionClass(History::class);
        $this->obj = $class->newInstanceWithoutConstructor();
    }

    /**
     * @param array $values
     * @param array $expectedHistory
     * @param string $expectedLast
     *
     * @cover History::add()
     * @dataProvider addProvider
     */
    public function testAdd(array $values, array $expectedHistory, string $expectedLast)
    {
        foreach ($values as $value) {
            $this->obj->add($value);
        }

        $history = Assert::readAttribute($this->obj, 'history');

        Assert::assertSame($expectedHistory, $history);
        Assert::assertSame($expectedLast, current($history));
    }

    /**
     * @param array $values
     * @param array $before action before like ['next', 'next', 'prev', ...]
     * @param string $expectedValue
     *
     * @cover History::prev()
     * @dataProvider prevProvider
     */
    public function testPrev(array $values, array $before, string $expectedValue)
    {
        foreach ($values as $value) {
            $this->obj->add($value);
        }

        foreach ($before as $action) {

            if (!in_array($action, ['prev', 'next'])) {
                self::fail('Unresolved actions');
            }

            $this->obj->$action();
        }

        Assert::assertSame($expectedValue, $this->obj->prev());
    }

    /**
     * @param array $values
     * @param array $before action before like ['next', 'next', 'prev', ...]
     * @param string $expectedValue
     *
     * @cover History::next()
     * @dataProvider nextProvider
     */
    public function testNext(array $values, array $before, string $expectedValue)
    {
        foreach ($values as $value) {
            $this->obj->add($value);
        }

        foreach ($before as $action) {

            if (!in_array($action, ['prev', 'next'])) {
                self::fail('Unresolved actions');
            }

            $this->obj->$action();
        }

        Assert::assertSame($expectedValue, $this->obj->next());
    }


    public function addProvider()
    {
        return [
            //array $values, array $expectedHistory, string $expectedLast
            'simple' => [['one', 'two', 'three'], ['one', 'two', 'three'], 'three'],
            'empty' => [['one', '', 'three'], ['one', 'three'], 'three'],
            'doubles' => [['one', 'two', 'two', 'three'], ['one', 'two', 'three'], 'three']
        ];
    }

    public function prevProvider()
    {
        return [
            //array $values, array $before, string $expectedValue
            'empty' => [[], [], ''],
            'get last' => [['one', 'two', 'three'], [], 'three'],
            'with before' => [['one', 'two', 'three'], ['prev'], 'two'],
            'with next' => [['one', 'two', 'three'], ['next'], 'three'],
            'with across begin/end' => [['one', 'two', 'three'], ['next', 'next', 'prev'], 'three'],
        ];
    }

    public function nextProvider()
    {
        return [
            //array $values, array $before, string $expectedValue
            'empty' => [[], [], ''],
            'get last' => [['one', 'two', 'three'], [], 'one'],
            'with before' => [['one', 'two', 'three'], ['next'], 'two'],
            'with prev' => [['one', 'two', 'three'], ['prev'], 'one'],
            'with across begin/end' => [['one', 'two', 'three'], ['next', 'next', 'prev'], 'two'],
        ];
    }

}
