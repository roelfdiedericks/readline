<?php

namespace Ridzhi\Readline;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class App
{

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Readline
     */
    protected $readline;

    /**
     * @var string
     */
    protected $ttyOrig;

    /**
     * Rush constructor.
     * @param Readline $readline
     * @param array $options
     */
    public function __construct(Readline $readline, array $options)
    {
        $this->readline = $readline;
        $this->options = $options;

        // save origin
        $this->ttyOrig = shell_exec('stty -g < /dev/tty');
    }

    /**
     * Run execution loop
     *
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function run()
    {
        while (true) {
            $command = $this->readline->read();

            if ('q' === $command) {
                break;
            }

            $this->disableByCharMode();
            $this->execute($command);
            $this->enableByCharMode();
        }

        $this->readline->getWriter()->writeAll("\nBye :-)\n");
    }


    /**
     * Execute command in separate process
     *
     * @param string $input
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    protected function execute(string $input)
    {
        try {

            $cmd = $this->buildCommand($input);
            $process = new Process($cmd);

            $this->writeln();
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

        } catch (\Throwable $e) {
            $this->writeln(Console::header('[FAIL] Something is wrong...', [41]));
            $this->writeln($e->getMessage());
            $this->writeln($e->getTraceAsString() . PHP_EOL);
        }

    }

    /**
     * If sub-processes has some interactive, we must handle it, off not buffered mode
     */
    public function disableByCharMode()
    {
        shell_exec('stty ' . $this->ttyOrig . ' < /dev/tty');
    }

    protected function enableByCharMode()
    {
        shell_exec('stty -echo -icanon min 1 time 0 < /dev/tty');
    }

    /**
     * @param string $command
     * @return string
     */
    protected function buildCommand(string $command): string
    {
        $cmd = sprintf('%s %s', $this->options['bin'], $command);
        $pid = posix_getpid();

        return sprintf('(%s) %s %s %s',
            $cmd,
            sprintf('0</proc/%s/fd/0', $pid),
            sprintf('1>/proc/%s/fd/1', $pid),
            sprintf('2>/proc/%s/fd/2', $pid)
        );
    }

    /**
     * @param string $value
     */
    protected function writeln(string $value = '')
    {
        $this->readline->getWriter()->writeAll($value . PHP_EOL);
    }

}