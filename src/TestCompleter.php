<?php

namespace Ridzhi\Readline;


class TestCompleter implements CompleteInterface
{

    /**
     * @param string $input User input to cursor position
     * @return array
     */
    public function complete(string $input): array
    {
        $info = Info::create($input);

        switch ($info['type']) {
            case Info::TYPE_ARG:
                return $this->filter($info['current'], $this->getCommands());
            case Info::TYPE_OPTION_SHORT:
                return $this->filter($info['current'], $this->getOptionsShort());
            case Info::TYPE_OPTION_LONG:
                return $this->filter($info['current'], $this->getOptionsLong());
            case Info::TYPE_OPTION_VALUE:
                return $this->filter($info['current'], $this->getOptionValue($info['optionName']));
            default:
                return [];
        }

    }

    public function getOptionValue(string $key)
    {
        $options = [
            '--name' => [
                'Danila Stivrinsh',
                'Vlad Kolesnokov',
                'Maks Ashimov',
                'Sergey Goppinkov'
            ],
            '--city' => [
                'Saint-Petersburg',
                'Moscow',
                'Novgorod',
                'Samara'
            ]
        ];

        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return [];
    }

    public function getCommands()
    {
        return [
            'asset/compress',
            'asset/template',
            'cache/flush',
            'cache/flush-all',
            'cache/flush-schema',
            'cache/index',
            'fixture/load',
            'fixture/unload',
            'help/index',
            'message/config',
            'message/config-template',
            'message/extract',
            'migrate/create',
            'migrate/down',
            'migrate/history',
            'migrate/mark',
            'migrate/new',
            'migrate/redo',
            'migrate/to',
            'migrate/up',
            'serve/index'
        ];
    }

    protected function getOptionsShort()
    {
        return [
            '-v',
            '-c',
            '-d',
            '-e'
        ];
    }

    protected function getOptionsLong()
    {
        return [
            '--name',
            '--email',
            '--age',
            '--city'
        ];
    }

    protected function filter(string $search, array $handle): array
    {
        if ($search === "") {
            return $handle;
        }

        return array_filter($handle, function ($item) use ($search) {
            return strpos($item, $search) === 0 && $item !== $search;
        });
    }

}