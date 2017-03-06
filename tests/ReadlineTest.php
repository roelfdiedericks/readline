<?php

namespace Ridzhi\Readline\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\Readline;

class ReadlineTest extends TestCase
{

    protected $obj;

    public function setUp()
    {
        $class = new \ReflectionClass(Readline::class);
        $this->obj = $class->newInstanceWithoutConstructor();
    }

    public function testInit()
    {
        Assert::assertEquals(1,1);
    }

    /**
     * @param string $pattern
     * @param array $dict
     * @param string $expected
     *
     * @cover Readline::getSuffix()
     * @dataProvider getSuffixProvider
     */
    public function testGetSuffix(string $pattern, array $dict, string $expected)
    {
        $method = new \ReflectionMethod(Readline::class, 'getSuffix');
        $method->setAccessible(true);

        Assert::assertSame($expected, $method->invoke($this->obj, $pattern, $dict));
    }

    public function getSuffixProvider()
    {
        $dict = [
            'as',
            'asset',
            'assert',
            'assembly',
            'assertion'
        ];

        return [
            'without' => ['as', $dict, ''],
            'one' => ['ass', $dict, 'e'],
            'some' => ['asser', $dict, 't'],
        ];
    }

}
