<?php

use \Ridzhi\Readline\Info\InfoInterface;

/**
 * This just example of readline completion
 *
 *
 * Class ExampleCompleter
 */
class ExampleCompleter implements \Ridzhi\Readline\CompleteInterface
{

    /**
     * @param string $input User input to cursor position
     * @return array
     */
    public function complete(string $input): array
    {
        $info = \Ridzhi\Readline\Info\Parser::parse($input);
        $current = $info->getCurrent();

        switch ($info->getType()) {
            case InfoInterface::TYPE_ARG:
                $args = $info->getArgs();

                if (count($args) <= 1) {
                    return $this->filter($current, $this->getCommands());
                }

                return [];
            case InfoInterface::TYPE_OPTION_SHORT:
            case InfoInterface::TYPE_OPTION_LONG:
                return $this->filter($current, $this->getOptions());
            case InfoInterface::TYPE_OPTION_VALUE:

                if ($info->getOptionName() === '--db') {
                    $values = [
                        'mysql',
                        'postgres',
                        'mongo'
                    ];

                    return $this->filter($current, $values);
                }

                return [];
            default:
                return [];
        }
    }

    protected function getCommands()
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

    protected function getOptions()
    {
        return [
            '--appconfig',
            '--color',
            '--db',
            '--fields',
            '--interactive',
            '--migrationNamespaces',
            '--migrationPath',
            '--migrationTable',
            '--templateFile',
            '--useTablePrefix'
        ];
    }

    /**
     * @param string $search
     * @param array $handle
     * @return array
     */
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