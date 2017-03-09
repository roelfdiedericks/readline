<?php

namespace Ridzhi\Readline\Tests\Info;


use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ridzhi\Readline\Info\InfoInterface;
use Ridzhi\Readline\Info\Parser;


class ParserTest extends TestCase
{

    /**
     * @param string $input
     * @param int $type
     * @param string $current
     * @param array $args
     * @param string $optionName
     *
     * @cover Parser::parse()
     * @dataProvider parseProvider()
     */
    public function testParse(string $input, int $type, string $current = "", array $args = [], string $optionName = "")
    {
        $method = new \ReflectionMethod(Parser::class, 'parse');
        $method->setAccessible(true);

        /**
         * @var InfoInterface $info
         */
        $info = $method->invoke(null, $input);
        Assert::assertSame($type, $info->getType());
        Assert::assertSame($current, $info->getCurrent());
        Assert::assertSame($args, $info->getArgs());
        Assert::assertSame($optionName, $info->getOptionName());
    }

    /**
     * @cover Parser::hasOpenedQuotes()
     */
    public function testHasOpenedQuotes()
    {
        $method = new \ReflectionMethod(Parser::class, 'hasOpenedQuotes');
        $method->setAccessible(true);
        Assert::assertSame(true, $method->invoke(null, 'command "some multi word arg '));
        Assert::assertSame(false, $method->invoke(null, 'command "some multi word arg"'));
        Assert::assertSame(true, $method->invoke(null, 'command "other arg" "some multi word arg'));
    }

    /**
     * @cover Parser::hasNewEmptyInput()
     */
    public function testHasNewEmptyInput()
    {
        $method = new \ReflectionMethod(Parser::class, 'hasNewEmptyInput');
        $method->setAccessible(true);
        Assert::assertSame(true, $method->invoke(null, 'command '));
        Assert::assertSame(false, $method->invoke(null, 'command'));
    }

    /**
     * @param string $input
     * @param bool $hasExpected
     * @param array $infoExpected
     *
     * @dataProvider hasOptionPrefixProvider
     * @cover         Parser::hasOptionPrefix()
     */
    public function testHasOptionPrefix(string $input, bool $hasExpected, array $infoExpected)
    {
        $method = new \ReflectionMethod(Parser::class, 'hasOptionPrefix');
        $method->setAccessible(true);
        list($has, $info) = $method->invoke(null, $input);

        Assert::assertSame($hasExpected, $has);
        Assert::assertSame($infoExpected, $info);
    }

    public function parseProvider()
    {
        return [
            'empty' => ['', InfoInterface::TYPE_ARG, '', ['']],
            'arg' => ['com', InfoInterface::TYPE_ARG, 'com', ['com']],
            'arg and multi words arg' => ['com "', InfoInterface::TYPE_ARG, '', ['com', '']],
            'arg and new empty input' => ['command ', InfoInterface::TYPE_ARG, '', ['command', '']],
            'few args' => ['command arg ', InfoInterface::TYPE_ARG, '', ['command', 'arg', '']],
            'args and short' => ['command -v', InfoInterface::TYPE_OPTION_SHORT, '-v', ['command']],
            'args and long' => ['command --n', InfoInterface::TYPE_OPTION_LONG, '--n', ['command']],
            'args and starts short' => ['command -', InfoInterface::TYPE_OPTION_SHORT, '-', ['command']],
            'args + opt and starts short' => ['command -v -', InfoInterface::TYPE_OPTION_SHORT, '-', ['command']],
            'args and starts long' => ['command --', InfoInterface::TYPE_OPTION_LONG, '--', ['command']],
            'args and empty after --' => ['command -- ', InfoInterface::TYPE_ARG, '', ['command', '1', '']],
            'args and short + long' => ['command -v --nam', InfoInterface::TYPE_OPTION_LONG, '--nam', ['command']],
            'args and short + new empty input' => ['command -v ', InfoInterface::TYPE_OPTION_LONG, '', ['command']],
            'args and long + empty value complete' => ['command --name ""', InfoInterface::TYPE_OPTION_VALUE, '', ['command'], '--name'],
            'args and long + value complete' => ['command -v --name "da"', InfoInterface::TYPE_OPTION_VALUE, 'da', ['command'], '--name'],
            'args and arrayed opt' => ['command -v --name "da" --name ""', InfoInterface::TYPE_OPTION_VALUE, '', ['command'], '--name'],
            'args and short + value complete' => ['command -v -n "da"', InfoInterface::TYPE_OPTION_VALUE, 'da', ['command'], '-n'],
        ];
    }

    public function hasOptionPrefixProvider()
    {
        return [
            'short' => ['command -', true, ['prefix' => Parser::MARKER_OPTION_SHORT, 'type' => InfoInterface::TYPE_OPTION_SHORT]],
            'long' => ['command --', true, ['prefix' => Parser::MARKER_OPTION_LONG, 'type' => InfoInterface::TYPE_OPTION_LONG]],
            'none 1' => ['command - ', false, []],
            'none 2' => ['command --  ', false, []],
            'none 3' => ['command strange_arg--', false, []],
        ];
    }

}
