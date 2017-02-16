<?php

namespace Ridzhi\Readline;


use Hoa\Console\Cursor;
use Hoa\Console\Input;
use Hoa\Console\Output;
use Ridzhi\Readline\Dropdown\Dropdown;
use Ridzhi\Readline\Dropdown\DropdownInterface;
use Ridzhi\Readline\Dropdown\Style;


class Readline
{

    /**
     * @var CompleteInterface
     */
    protected $completer;

    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var Output
     */
    protected $output;

    /**
     * @var Dropdown
     */
    protected $dropdown;

    /**
     * @var array
     */
    protected $keyHandlers;

    /**
     * @var bool
     */
    protected $pressEnter = false;


    public function __construct(DropdownInterface $dropdown)
    {

        $this->buffer = new Buffer();
        $this->input = new Input();
        $this->output = new Output();
        $this->dropdown = $dropdown;

        $this->initKeyHandlers();
    }

    public function read(string $prompt): string
    {
        $this->updateDropdown();
        $this->buffer->setPrompt($prompt);

        do {

            $this->printBuffer();
            $this->showDropdown();
            $input = $this->input->read($maxUsageLength = 5);
            $this->hideDropdown();

//            $segments = str_split($input);
//
//            foreach ($segments as $segment) {
//                echo "CHar code:" . ord($segment) . PHP_EOL;
//            }
//
//            echo "-----------------------";
//            continue;
//
            if ($this->tryResolveAsServiceCommand($input)) {
                continue;
            }

            // omit unresolved
            if ($this->isUnresolved($input)) {
                continue;
            }

            //char processing
            $this->buffer->insert($input);
            $this->updateDropdown();

        } while (!$this->pressEnter);

        $this->pressEnter = false;

        $line = $this->buffer->getInput();
        $this->buffer->reset();

        return $line;
    }

    /**
     * @param CompleteInterface $completer
     */
    public function setCompleter(CompleteInterface $completer)
    {
        $this->completer = $completer;
    }

    /**
     * @param $value
     * @param callable $handler
     */
    public function registerKeyHandler($value, Callable $handler)
    {
        $this->keyHandlers[$value] = $handler;
    }

    /**
     * @param int|string $value ASCII code| String value
     * @param string $handler Function name
     */
    protected function registerCoreKeyHandler($value, string $handler)
    {
        $this->registerKeyHandler($value, [$this, $handler]);
    }

    protected function initKeyHandlers()
    {
        /** @uses \Ridzhi\Readline\Readline::handlerTab() */
        $this->registerCoreKeyHandler(9, 'handlerTab');

        /** @uses \Ridzhi\Readline\Readline::handlerEnter() */
        $this->registerCoreKeyHandler(10, 'handlerEnter');

        /** @uses \Ridzhi\Readline\Readline::handlerBackspace() */
        $this->registerCoreKeyHandler(127, 'handlerBackspace');

        /** @uses \Ridzhi\Readline\Readline::handlerEscape() */
        $this->registerCoreKeyHandler("\033", 'handlerEscape');

        /** @uses \Ridzhi\Readline\Readline::handlerDelete() */
        $this->registerCoreKeyHandler("\033[3~", 'handlerDelete');

        /** @uses \Ridzhi\Readline\Readline::handlerHome() */
        $this->registerCoreKeyHandler("\033[H", 'handlerHome');

        /** @uses \Ridzhi\Readline\Readline::handlerEnd() */
        $this->registerCoreKeyHandler("\033[F", 'handlerEnd');

        /** @uses \Ridzhi\Readline\Readline::bindArrowUp() */
        $this->registerCoreKeyHandler("\033[A", 'handlerArrowUp');

        /** @uses \Ridzhi\Readline\Readline::handlerArrowRight() */
        $this->registerCoreKeyHandler("\033[C", 'handlerArrowRight');

        /** @uses \Ridzhi\Readline\Readline::handlerArrowDown() */
        $this->registerCoreKeyHandler("\033[B", 'handlerArrowDown');

        /** @uses \Ridzhi\Readline\Readline::handlerArrowLeft() */
        $this->registerCoreKeyHandler("\033[D", 'handlerArrowLeft');

        /** @uses \Ridzhi\Readline\Readline::handlerQuotes() */
        $this->registerCoreKeyHandler("\"", "handlerQuotes");

    }

    protected function handlerEscape(Readline $self)
    {
        if ($self->dropdown->isActive()) {
            $self->dropdown->resetScrolling();
        }
    }

    protected function handlerQuotes(Readline $self)
    {
        $self->buffer->insert("\"\"");
        $self->buffer->cursorPrev();
        $self->updateDropdown();
    }

    protected function handlerBackspace(Readline $self)
    {
        $self->buffer->removeChar();
        $self->updateDropdown();
    }

    protected function handlerDelete(Readline $self)
    {
        $self->buffer->removeChar(false);
    }

    protected function handlerHome(Readline $self)
    {
        $self->buffer->cursorToBegin();
    }

    protected function handlerEnd(Readline $self)
    {
        $self->buffer->cursorToEnd();
    }

    protected function handlerTab(Readline $self)
    {
        $input = $self->buffer->getInputCurrent();
        $info = Info::create($input);

        if ($info['current'] !== '') {
            $data = $self->completer->complete($input);
            $suffix = $self->getSuffix($info['current'], $data);

            if ($suffix !== '') {
                $self->buffer->insert($suffix);
            }
        }
    }

    protected function handlerEnter(Readline $self)
    {
        if ($self->dropdown->isActive()) {
            $self->processComplete();
        } else {
            $self->pressEnter = true;
        }
    }

    protected function handlerArrowUp(Readline $self)
    {
        $self->dropdown->scrollUp();
    }

    protected function handlerArrowRight(Readline $self)
    {
        $self->buffer->cursorNext();
        $self->updateDropdown();
    }

    //TODO для скрола добавить чеки что окно что то покащывпет
    protected function handlerArrowDown(Readline $self)
    {
        $self->dropdown->scrollDown();
    }

    protected function handlerArrowLeft(Readline $self)
    {
        $self->buffer->cursorPrev();
        $self->updateDropdown();
    }

    protected function updateDropdown()
    {
        $this->dropdown->setContent($this->getDict());
    }

    protected function showDropdown()
    {
        Cursor::save();
        Cursor::move('down');

        $this->output->writeString($this->dropdown->getView());

        Cursor::restore();
    }

    protected function hideDropdown()
    {
        Cursor::clear("down");
    }


    /**
     * @param string $char
     * @return bool
     */
    protected function tryResolveAsServiceCommand(string $char): bool
    {
        // printable
        if (!$this->callHandler($char)) {
            // try non printable
            return $this->callHandler(ord($char));
        }

        return true;
    }

    protected function processComplete()
    {
        $value = $this->dropdown->getSelect();
        $info = Info::create($this->buffer->getInputCurrent());
        $completion = substr($value, strlen($info['current']));
        $this->buffer->insert($completion);

        if ($info['quoted']) {
            $this->buffer->cursorNext(2);
        } else {
            $this->buffer->cursorNext();
        }

        $this->updateDropdown();
    }

    protected function printBuffer()
    {
        Cursor::clear('line');
        //after this cursor at the end of line
        $this->output->writeString($this->buffer->getPrompt() . $this->buffer->getInput());
        Cursor::move("left", $this->buffer->getPos(true));
    }

    /**
     * @return array
     */
    protected function getDict(): array
    {
        return $this->completer->complete($this->buffer->getInputCurrent());
    }

    /**
     * TODO temp decision, make prefix tree in future
     *
     * @param string $pattern
     * @param array $data
     * @return string
     */
    protected function getSuffix(string $pattern, array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $data = array_filter($data, function ($item) use ($pattern) {
            return strpos($item, $pattern) === 0;
        });

        $max = min(array_map('mb_strlen', $data));
        $word = array_pop($data);
        $result = "";

        for ($i = strlen($pattern); $i < $max; $i++) {

            $char = $word[$i];

            foreach ($data as $item) {
                if ($item[$i] !== $char) {
                    break 2;
                }
            }

            $result .= $char;
        }

        return $result;
    }

    /**
     * TODO just stub, make in future
     * @param string $str
     * @return bool
     */
    protected function isUnresolved(string $str)
    {
        return mb_strlen($str) > 1;
    }

    /**
     * @param $key
     * @return bool
     */
    protected function callHandler($key): bool
    {
        if (!isset($this->keyHandlers[$key])) {
            return false;
        }

        call_user_func($this->keyHandlers[$key], $this);

        return true;
    }

}