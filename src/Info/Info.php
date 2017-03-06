<?php

namespace Ridzhi\Readline\Info;

/**
 * Class Info
 * @package Ridzhi\Readline\Info
 */
class Info implements InfoInterface
{

    /**
     * @var int
     */
    protected $type = self::TYPE_ARG;

    /**
     * @var string
     */
    protected $current = '';

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @var string
     */
    protected $optionName = '';

    /**
     * @param int $type
     */
    public function setType(int $type)
    {
        $this->type = $type;
    }

    /**
     * @param string $current
     */
    public function setCurrent(string $current)
    {
        $this->current = $current;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
    }

    /**
     * @param string $optionName
     */
    public function setOptionName(string $optionName)
    {
        $this->optionName = $optionName;
    }

    /**
     * @return int self:TYPE_*
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string Current token
     */
    public function getCurrent(): string
    {
        return $this->current;
    }

    /**
     * @return array List args, else []
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return string If type of completion is <self:TYPE_OPTION_VALUE>, its return option name else ''
     */
    public function getOptionName(): string
    {
        return $this->optionName;
    }

}