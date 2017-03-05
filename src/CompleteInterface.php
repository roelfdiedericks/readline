<?php

namespace Ridzhi\Readline;

/**
 * Interface CompleteInterface
 * @package Ridzhi\Readline
 */
interface CompleteInterface
{

    /**
     * @param string $input User input to cursor position
     * @return array
     */
    public function complete(string $input): array;

}