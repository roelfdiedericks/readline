<?php

namespace Ridzhi\Readline\Tests\Dropdown;


use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\Dropdown\Dropdown;
use Ridzhi\Readline\Dropdown\Themes\DefaultTheme;


class DropdownTest extends TestCase
{

    /**
     * @var Dropdown
     */
    protected $obj;

    public function setUp()
    {
        $class = new \ReflectionClass(Dropdown::class);
        $this->obj = $class->newInstanceWithoutConstructor();
    }

    /**
     * @cover Dropdown::reset()
     */
    public function testReset()
    {
        // get defaults
        $propPos = new \ReflectionProperty(Dropdown::class, 'pos');
        $propPos->setAccessible(true);
        $defaultPos = $propPos->getValue($this->obj);

        $propOffset = new \ReflectionProperty(Dropdown::class, 'offset');
        $propOffset->setAccessible(true);
        $defaultOffset = $propOffset->getValue($this->obj);

        $propHasFocus = new \ReflectionProperty(Dropdown::class, 'hasFocus');
        $propHasFocus->setAccessible(true);
        $defaultHasFocus = $propHasFocus->getValue($this->obj);

        $propReverse = new \ReflectionProperty(Dropdown::class, 'reverse');
        $propReverse->setAccessible(true);
        $defaultReverse = $propReverse->getValue($this->obj);


        // set some values
        $propPos->setValue($this->obj, 10);
        $propOffset->setValue($this->obj, 10);
        $propHasFocus->setValue($this->obj, true);
        $propReverse->setValue($this->obj, true);


        // check test values not equal defaults
        if ($propPos->getValue($this->obj) === $defaultPos ||
            $propOffset->getValue($this->obj) === $defaultOffset ||
            $propHasFocus->getValue($this->obj) === $defaultHasFocus ||
            $propReverse->getValue($this->obj) === $defaultReverse
        ) {
            Assert::fail('Invalid test values for Dropdown::testReset()');
        }

        $this->obj->reset();

        Assert::assertAttributeEquals($defaultPos, 'pos', $this->obj);
        Assert::assertAttributeEquals($defaultOffset, 'offset', $this->obj);
        Assert::assertAttributeEquals($defaultHasFocus, 'hasFocus', $this->obj);
        Assert::assertAttributeEquals($defaultReverse, 'reverse', $this->obj);

    }

    /**
     * @cover Dropdown::testSetItems()
     * @dataProvider setItemsProvider
     * @param int $maxHeight
     * @param array $items
     * @param array $expectedItems
     * @param int $expectedCount
     * @param int $expectedHeight
     */
    public function testSetItems(int $maxHeight, array $items, array $expectedItems, int $expectedCount, int $expectedHeight)
    {
        $method = new \ReflectionMethod(Dropdown::class, 'setItems');
        $method->setAccessible(true);

        $propMaxHeight = new \ReflectionProperty(Dropdown::class, 'maxHeight');
        $propMaxHeight->setAccessible(true);
        $propMaxHeight->setValue($this->obj, $maxHeight);

        $method->invoke($this->obj, $items);

        Assert::assertAttributeEquals($expectedItems, 'items', $this->obj);
        Assert::assertAttributeEquals($expectedCount, 'count', $this->obj);
        Assert::assertAttributeEquals($expectedHeight, 'height', $this->obj);
    }


    /**
     * @cover Dropdown::getSelect()
     */
    public function testGetSelect()
    {
        $dropdown = new Dropdown($this->getFixtureItems(), 5, new DefaultTheme());

        // 6 times for scrolling viewport, shows 2-6 items
        $dropdown->scrollDown();
        $dropdown->scrollDown();
        $dropdown->scrollDown();
        $dropdown->scrollDown();
        $dropdown->scrollDown();
        $dropdown->scrollDown();

        Assert::assertSame('six', $dropdown->getSelect());
    }


    /**
     * @cover Dropdown::getHeight()
     */
    public function testGetHeight()
    {
        Assert::assertSame(0, $this->obj->getHeight());
    }

    /**
     * @cover Dropdown::hasFocus()
     */
    public function testHasFocus()
    {
        Assert::assertFalse($this->obj->hasFocus());
    }

    /**
     * @cover Dropdown::scrollUp()
     * @dataProvider scrollUpProvider
     *
     * @param int $count
     * @param int $height
     * @param int $pos
     * @param int $offset
     * @param bool $hasFocus
     * @param bool $reverse
     * @param int $expectedPos
     * @param int $expectedOffset
     * @param bool $expectedHasFocus
     * @param bool $expectedReverse
     */
    public function testScrollUp(
        int $count,
        int $height,
        int $pos,
        int $offset,
        bool $hasFocus,
        bool $reverse,
        int $expectedPos,
        int $expectedOffset,
        bool $expectedHasFocus,
        bool $expectedReverse
    )
    {
        $propCount = new \ReflectionProperty(Dropdown::class, 'count');
        $propCount->setAccessible(true);

        $propHeight = new \ReflectionProperty(Dropdown::class, 'height');
        $propHeight->setAccessible(true);

        $propPos = new \ReflectionProperty(Dropdown::class, 'pos');
        $propPos->setAccessible(true);

        $propOffset = new \ReflectionProperty(Dropdown::class, 'offset');
        $propOffset->setAccessible(true);

        $propHasFocus = new \ReflectionProperty(Dropdown::class, 'hasFocus');
        $propHasFocus->setAccessible(true);

        $propReverse = new \ReflectionProperty(Dropdown::class, 'reverse');
        $propReverse->setAccessible(true);

        //set start state
        $propCount->setValue($this->obj, $count);
        $propHeight->setValue($this->obj, $height);
        $propPos->setValue($this->obj, $pos);
        $propOffset->setValue($this->obj, $offset);
        $propHasFocus->setValue($this->obj, $hasFocus);
        $propReverse->setValue($this->obj, $reverse);

        $this->obj->scrollUp();

        Assert::assertAttributeEquals($expectedPos, 'pos', $this->obj);
        Assert::assertAttributeEquals($expectedOffset, 'offset', $this->obj);
        Assert::assertAttributeEquals($expectedHasFocus, 'hasFocus', $this->obj);
        Assert::assertAttributeEquals($expectedReverse, 'reverse', $this->obj);
    }

    public function setItemsProvider()
    {
        $items = $this->getFixtureItems();

        return [
            //int $maxHeight, array $items, array $expectedItems, int $expectedCount, int $expectedHeight
            'simple' => [5, $items, $items, 7, 5],
            'incorrect items' => [5,
                array_slice($items, 1, null, true),/* index starts from 1 now */
                ['two', 'three', 'four', 'five', 'six', 'seven'], 6, 5],
            'count items < maxHeight' => [5, array_slice($items, 0, 3), array_slice($items, 0, 3), 3, 3],
        ];
    }


    public function scrollUpProvider()
    {
        return [
            // int $count,
            // int $height,
            // int $pos,
            // int $offset,
            // bool $hasFocus,
            // bool $reverse,
            // int $expectedPos,
            // int $expectedOffset,
            // bool $expectedHasFocus,
            // bool $expectedReverse
            'first' => [10, 5, 0, 0, false, false, 9, 5, true, true],
            'with offset' => [10, 5, 4, 0, true, false, 3, 0, true, false],
            'with offset + border pos' => [10, 5, 5, 6, true, false, 4, 5, true, false],
        ];
    }

    protected function getFixtureItems()
    {
        return ['one', 'two', 'three', 'four', 'five', 'six', 'seven'];
    }

}
