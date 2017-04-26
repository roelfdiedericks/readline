<?php

namespace Ridzhi\Readline;


use Hoa\Console\Window;

class Console
{

    /**
     * @param string $string
     * @param array $ansiFormat
     * @return string
     */
    public static function format(string $string, array $ansiFormat = []): string
    {
        $code = implode(';', $ansiFormat);

        return "\033[0m" . ($code !== '' ? "\033[" . $code . 'm' : '') . $string . "\033[0m";
    }

    /**
     * @param string $header
     * @param array $ansiFormat
     * @return string
     */
    public static function header(string $header, array $ansiFormat = []): string
    {
        $width = Window::getSize()['x'];
        $header = sprintf('%2$s%s%2$s', str_pad($header, $width, " ", STR_PAD_BOTH), str_repeat(" ", $width));

        return self::format($header, $ansiFormat);
    }

}