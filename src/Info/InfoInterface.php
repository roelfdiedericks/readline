<?php

namespace Ridzhi\Readline\Info;

/**
 * All getters at least must return empty value of appropriate type
 *
 * Interface InfoInterface
 * @package Ridzhi\Rush\Info
 */
interface InfoInterface
{

    const TYPE_ARG = 1;
    const TYPE_OPTION_SHORT = 2;
    const TYPE_OPTION_LONG = 3;
    const TYPE_OPTION_VALUE = 4;

    /**
     * @return int self:TYPE_*
     */
    public function getType(): int;

    /**
     * @return string Current token
     */
    public function getCurrent(): string;

    /**
     * @return array List args, else []
     */
    public function getArgs(): array;

    /**
     * @return string If type of completion is <self:TYPE_OPTION_VALUE>, its return option name else ''
     */
    public function getOptionName(): string;

}