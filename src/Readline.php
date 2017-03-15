<?php

namespace Ridzhi\Readline;

use CLI\Cursor;
use CLI\Erase;
use Hoa\Console\Console;
use Hoa\Console\Cursor as HoaCursor;
use Hoa\Console\Input;
use Hoa\Console\Output;
use Hoa\Console\Window;
use Ridzhi\Readline\Dropdown\Dropdown;
use Ridzhi\Readline\Dropdown\DropdownInterface;
use Ridzhi\Readline\Dropdown\NullDropdown;
use Ridzhi\Readline\Dropdown\ThemeInterface;
use Ridzhi\Readline\Dropdown\Themes\DefaultTheme;
use Ridzhi\Readline\Info\Parser;


/**
 * Class Readline
 * @package Ridzhi\Readline
 */
class Readline
{

    /**
     * @var ThemeInterface
     */
    protected $theme;

    /**
     * @var int Height of Dropdown
     */
    protected $height;

    /**
     * @var Output
     */
    protected $output;

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
     * @var DropdownInterface
     */
    protected $dropdown;

    /**
     * @var array map of handlers
     */
    protected $handlers;

    /**
     * @var History
     */
    protected $history;

    /**
     * @var bool
     */
    protected $hasEnter = false;

    /**
     * @var int Usage for eol tracking
     */
    protected $eolTracker = 0;

    /**
     * @var int Usage for cursor positioning before each iteration rendering
     */
    protected $lastConsolePos = 0;

    /**
     * @var bool
     */
    protected $updateDd = true;

    /**
     * Readline constructor.
     * @param ThemeInterface|null $theme
     * @param int $height
     */
    public function __construct(ThemeInterface $theme = null, int $height = 7)
    {
        if ($theme === null) {
            $theme = new DefaultTheme();
        }

        $this->theme = $theme;
        $this->height = $height;

        $this->output = new Output();
        $this->dropdown = $this->factoryDropdown();
        $this->buffer = new Buffer();
        $this->history = new History();
        $this->input = new Input();

        $this->initKeyHandlers();
    }

    /**
     * @param string $prompt
     * @return string
     */
    public function read(string $prompt): string
    {
        Console::advancedInteraction();

        $maxUsageLength = 4;
        $this->lastConsolePos = 0;

        $this->buffer->setPrompt($prompt);
        $this->write($prompt);

        do {
            $this->clear();
            $this->showBuffer();
            $this->showDropdown();

            $input = $this->input->read($maxUsageLength);

            $isSpecial = $this->tryResolveAsServiceCommand($input) || $this->isUnresolved($input);

            if ($isSpecial) {
                continue;
            }

            $this->buffer->insert($input);

        } while (!$this->hasEnter);

        $this->hasEnter = false;
        $line = $this->buffer->getFull();
        $this->history->add($line);
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

    protected function initKeyHandlers()
    {
        /** @uses \Ridzhi\Readline\Readline::handlerTab() */
        $this->registerCoreKeyHandler(9, 'handlerTab');

        /** @uses \Ridzhi\Readline\Readline::handlerEnter() */
        $this->registerCoreKeyHandler(10, 'handlerEnter');

        /** @uses \Ridzhi\Readline\Readline::handlerBackspace() */
        $this->registerCoreKeyHandler(127, 'handlerBackspace');

        /** @uses \Ridzhi\Readline\Readline::handlerPageUp() */
        $this->registerCoreKeyHandler("\033[5~", 'handlerPageUp');

        /** @uses \Ridzhi\Readline\Readline::handlerPageDown() */
        $this->registerCoreKeyHandler("\033[6~", 'handlerPageDown');

        /** @uses \Ridzhi\Readline\Readline::handlerEscape() */
        $this->registerCoreKeyHandler("\033", 'handlerEscape');

        /** @uses \Ridzhi\Readline\Readline::handlerDelete() */
        $this->registerCoreKeyHandler("\033[3~", 'handlerDelete');

        /** @uses \Ridzhi\Readline\Readline::handlerHome() */
        $this->registerCoreKeyHandler("\033[H", 'handlerHome');

        /** @uses \Ridzhi\Readline\Readline::handlerEnd() */
        $this->registerCoreKeyHandler("\033[F", 'handlerEnd');

        /** @uses \Ridzhi\Readline\Readline::handlerArrowUp() */
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

    /**
     * @param Readline $self
     */
    protected function handlerEscape(Readline $self)
    {
        if ($self->dropdown->hasFocus()) {
            $self->dropdown->reset();
        }
    }

    /**
     * @param Readline $self
     */
    protected function handlerQuotes(Readline $self)
    {
        $self->buffer->insert("\"\"");
        $self->buffer->cursorPrev();
    }

    /**
     * @param Readline $self
     */
    protected function handlerPageUp(Readline $self)
    {
        $self->showHistory($self->history->prev());
    }

    /**
     * @param Readline $self
     */
    protected function handlerPageDown(Readline $self)
    {
        $self->showHistory($self->history->next());
    }

    /**
     * @param Readline $self
     */
    protected function handlerBackspace(Readline $self)
    {
        $self->buffer->backspace();
    }

    /**
     * @param Readline $self
     */
    protected function handlerDelete(Readline $self)
    {
        $self->buffer->delete();
    }

    /**
     * @param Readline $self
     */
    protected function handlerHome(Readline $self)
    {
        $self->buffer->cursorToBegin();
    }

    /**
     * @param Readline $self
     */
    protected function handlerEnd(Readline $self)
    {
        $self->buffer->cursorToEnd();
    }

    /**
     * @param Readline $self
     */
    protected function handlerTab(Readline $self)
    {
        $input = $self->buffer->getCurrent();
        $current = Parser::parse($input)->getCurrent();

        if ($current !== '') {
            $data = $self->completer->complete($input);
            $suffix = $self->getSuffix($current, $data);

            if ($suffix !== '') {
                $self->buffer->insert($suffix);
            }
        }
    }

    /**
     * @param Readline $self
     */
    protected function handlerEnter(Readline $self)
    {
        if ($self->dropdown->hasFocus()) {
            $value = $self->dropdown->getSelect();
            $current = Parser::parse($self->buffer->getCurrent())->getCurrent();
            $completion = mb_substr($value, mb_strlen($current));
            $self->buffer->insert($completion);
        } else {
            $self->hasEnter = true;
        }
    }

    /**
     * @param Readline $self
     */
    protected function handlerArrowUp(Readline $self)
    {
        $self->dropdown->scrollUp();
        //by default after each iteration dd will be update and reset, this stop it
        $self->updateDd = false;
    }

    /**
     * TODO: для скрола добавить чеки что окно что то покащывпет
     * @param Readline $self
     */
    protected function handlerArrowDown(Readline $self)
    {
        $self->dropdown->scrollDown();
        //by default after each iteration dd will be update and reset, this stop it
        $self->updateDd = false;
    }

    /**
     * @param Readline $self
     */
    protected function handlerArrowLeft(Readline $self)
    {
        $self->buffer->cursorPrev();
    }

    /**
     * @param Readline $self
     */
    protected function handlerArrowRight(Readline $self)
    {
        $self->buffer->cursorNext();
    }

    /**
     * @param int $steps
     */
    protected function cursorLeftWithAutoWrap(int $steps = 1)
    {
        Cursor::hide();

        $x = $this->getX();

        if ($x > $steps) {

            if ($steps > 0) {
                Cursor::back($steps);
                $this->eolTracker = $x - $steps;
            }

        } else {
            $steps = $steps - $x;
            $width = Window::getSize()['x'];
            $offsetY = floor($steps / $width) + 1;
            $offsetX = $steps - (($offsetY - 1) * $width);

            Cursor::up($offsetY);
            Cursor::forward(9999);

            if ($offsetX > 0) {
                Cursor::back($offsetX);
            }

            $this->eolTracker = $offsetX;
        }

        Cursor::show();
    }

    /**
     * @param int $steps
     */
    protected function cursorRightWithAutoWrap(int $steps = 1)
    {
        if ($steps < 1) {
            return;
        }

        Cursor::hide();

        $width = Window::getSize()['x'];
        $x = $this->getX();

        if ($steps > 1) {

            $limit = $width - $x;

            if ($steps > $limit) {
                $steps = $steps - $limit;
                $offsetY = floor($steps / $width) + 1;
                $offsetX = $steps - (($offsetY - 1) * $width) - 1;

                Cursor::down($offsetY);
                Cursor::back(9999);

                if ($offsetX > 0) {
                    Cursor::forward($offsetX);
                }
            } else {
                Cursor::forward($steps);
                $this->eolTracker = $x + 1;
            }

        } else {
            if ($x < $width) {
                Cursor::forward();
                $this->eolTracker = $x + 1;
            } else {
                Cursor::down();
                Cursor::back(9999);
                $this->eolTracker = 1;
            }
        }

        Cursor::show();
    }

    /**
     * @param $value
     * @param callable $handler
     */
    protected function registerKeyHandler($value, Callable $handler)
    {
        $this->handlers[$value] = $handler;
    }

    /**
     * @param int|string $value ASCII code| String value
     * @param string $handler Function name
     */
    protected function registerCoreKeyHandler($value, string $handler)
    {
        $this->registerKeyHandler($value, [$this, $handler]);
    }

    /**
     * @param string $value
     */
    protected function insert(string $value)
    {
        $this->buffer->insert($value);
    }

    /**
     * @param string $command
     */
    protected function showHistory(string $command)
    {
        $this->buffer->reset();
        $this->buffer->insert($command);
    }

    protected function clear()
    {
        if ($this->lastConsolePos !== 0) {
            $this->cursorLeftWithAutoWrap($this->lastConsolePos);
        }

        Erase::down();
    }

    protected function showBuffer()
    {
        $buffer = $this->buffer->getFull();
        $this->lastConsolePos = $pos = $this->buffer->getPos();

        if (empty($buffer)) {
            return;
        }

        $this->write($buffer);
        $leftOffset = mb_strlen($buffer);

        $this->cursorLeftWithAutoWrap($leftOffset);
        $this->cursorRightWithAutoWrap($pos);
    }

    protected function showDropdown()
    {
        if ($this->updateDd) {
            $this->updateDropdown();
        } else {
            $this->updateDd = true;
        }

        $width = 0;
        $view = $this->dropdown->getView($width); //by reference

        if (empty($view)) {
            return;
        }

        Cursor::hide();
        Cursor::savepos();

        $size = Window::getSize();
        $pos = HoaCursor::getPosition();

        $padding = 1;
        $diffY = $pos['y'] + $this->dropdown->getHeight() - $size['y'] + $padding;

        if ($diffY > 0) {
            Window::scroll('up', $diffY);
            Cursor::up($diffY);
        }

        Cursor::down();

        $diffX = $pos['x'] + $width - $size['x'];

        if ($diffX > 0) {
            Cursor::back($diffX);
        }

        $this->output->writeAll($view);

        Cursor::restore();

        if ($diffY > 0) {
            Cursor::up($diffY);
        }

        Cursor::show();
    }

    protected function hideDropdown()
    {
        Cursor::save();
        Cursor::down();
        Cursor::back(9999);
        Erase::down();
        Cursor::restore();
    }

    protected function updateDropdown()
    {
        if ($this->completer instanceof CompleteInterface) {
            $items = $this->completer->complete($this->buffer->getCurrent());
            $this->dropdown = $this->factoryDropdown($items);
        }
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

    /**
     * @param string $value
     */
    protected function write(string $value)
    {
        Cursor::hide();

        $length = mb_strlen($value);

        if ($length > 1) {
            $this->writeWithAutoLF(mb_substr($value, 0, -1));
            $value = mb_substr($value, -1);
        }

        $this->writeWithAutoLF($value);

        Cursor::show();
    }

    /**
     * @param string $value
     */
    protected function writeWithAutoLF(string $value)
    {
        if (empty($value)) {
            return;
        }

        $this->output->writeAll($value);

        $x = $this->getX();

        if ($x === $this->eolTracker) {
            Cursor::down();
            Cursor::back(9999);
        } else {
            $this->eolTracker = $x;
        }

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
     * @return int
     */
    protected function getX(): int
    {
        //Very slow and unpredictable instruction
        //TODO Try to remove
        $x = HoaCursor::getPosition()['x'];

        // hack if getting zero
        if ($x === 0) {
            return $this->getX();
        }

        return $x;
    }

    /**
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
        if (!isset($this->handlers[$key])) {
            return false;
        }

        call_user_func($this->handlers[$key], $this);

        return true;
    }

    /**
     * @param array $items
     * @return DropdownInterface
     */
    protected function factoryDropdown(array $items = []): DropdownInterface
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return empty($items) ? new NullDropdown() : new Dropdown($items, $this->height, $this->theme);
    }

}