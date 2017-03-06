<?php

namespace Ridzhi\Readline\Info;

/**
 * Class Parser
 * @package Ridzhi\Rush\Info
 */
class Parser
{

    const MARKER_OPTION_SHORT = '-';
    const MARKER_OPTION_LONG = '--';

    /**
     * @param string $input
     * @return InfoInterface $info
     */
    public static function parse(string $input): InfoInterface
    {
        $info = new Info();

        // mutually exclusive predicates
        $hasOpenedQuotes = self::hasOpenedQuotes($input);
        $hasOptionPrefix = false;
        $hasNewEmptyInput = false;

        //if input has unclosed quotes we need to close them for valid hoa parser and set corresponding flag
        //quoted input can be arg or option's value, we define it later
        if ($hasOpenedQuotes) {
            $input .= '"';
        } else {
            list($hasOptionPrefix, $prefixInfo) = self::hasOptionPrefix($input);

            //if current input looks like as "command -|--" it will be option complete case always
            if ($hasOptionPrefix) {
                $input = rtrim(substr($input, 0, -(strlen($prefixInfo['prefix']))));
                $info->setType($prefixInfo['type']);
                $info->setCurrent($prefixInfo['prefix']);
            } else {
                $hasNewEmptyInput = self::hasNewEmptyInput($input);
            }
        }

        $parser = new \Hoa\Console\Parser();
        $parser->parse($input);
        $args = $parser->getInputs();
        $options = $parser->getSwitches();

        if (empty($options)) {

            $hasNewEmptyQuotedArg = $hasOpenedQuotes && (substr($input, -2) === '""');

            if ($hasNewEmptyQuotedArg) {
                // hoa parser omit empty quoted arg and we correct it
                $args[] = "";
            }

            if (!$hasOptionPrefix) {
                if ($hasNewEmptyInput || empty($args)) {
                    array_push($args, '');
                }

                $info->setCurrent(end($args));
            }

            $info->setArgs($args);

            return $info;

        } else {
            $info->setArgs($args);

            if ($hasOptionPrefix) {
                return $info;
            }

            if ($hasNewEmptyInput) {
                $info->setType(InfoInterface::TYPE_OPTION_LONG);

                return $info;
            }

            end($options);
            list($optionName, $optionValue) = each($options);

            if (is_array($optionValue)) {
                $optionValue = end($optionValue);
            }

            $isLong = strlen($optionName) > 1 || (substr($input, -3, 2) === self::MARKER_OPTION_LONG);

            if ($optionValue === true) {
                $info->setType(($isLong) ? InfoInterface::TYPE_OPTION_LONG : InfoInterface::TYPE_OPTION_SHORT);
                $info->setCurrent((($isLong) ? self::MARKER_OPTION_LONG : self::MARKER_OPTION_SHORT) . $optionName);
            } else {
                $info->setType(InfoInterface::TYPE_OPTION_VALUE);

                if ($optionValue !== ($trimmed = trim($optionValue, '"'))) {
                    $optionValue = $trimmed;
                }

                $info->setCurrent($optionValue);
                $info->setOptionName((($isLong) ? self::MARKER_OPTION_LONG : self::MARKER_OPTION_SHORT) . $optionName);
            }

        }

        return $info;
    }

    /**
     * @param string $input
     * @return bool
     */
    protected static function hasOpenedQuotes(string $input): bool
    {
        return (substr_count($input, "\"") % 2) !== 0;
    }

    /**
     * @param string $input
     * @return bool
     */
    protected static function hasNewEmptyInput(string $input): bool
    {
        return substr($input, -1) === ' ';
    }

    /**
     * If new input start with -/-- (exp "command --") it's particular case,
     * because hoa parser return ["command", "1"]
     *
     * @param string $input
     * @return array [$has, $info = []]
     */
    protected static function hasOptionPrefix(string $input): array
    {
        $has = preg_match('/(?<=\s)(--|-)$/', $input, $matches) === 1;

        $info = [];

        if ($has) {
            $info['prefix'] = $matches[0];
            $info['type'] = ($matches[0] === self::MARKER_OPTION_SHORT) ? InfoInterface::TYPE_OPTION_SHORT : InfoInterface::TYPE_OPTION_LONG;
        }

        return [$has, $info];
    }

}