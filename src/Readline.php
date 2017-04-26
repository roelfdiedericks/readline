<?php

namespace Ridzhi\Readline;

use CLI\Cursor;
use CLI\Erase;
use Hoa\Console\Cursor as HoaCursor;
use Hoa\Console\Input;
use Hoa\Console\Output;
use Hoa\Console\Window;
use Hoa\Stream\IStream\In;
use Hoa\Stream\IStream\Out;
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
     * @var string
     */
    protected $prompt = 'readline: ';

    /**
     * @var ThemeInterface
     */
    protected $theme;

    /**
     * @var int Height of Dropdown
     */
    protected $height;

    /**
     * @var In
     */
    protected $reader;

    /**
     * @var Out
     */
    protected $writer;

    /**
     * @var CompleteInterface
     */
    protected $completer;

    /**
     * @var Line
     */
    protected $line;

    /**
     * @var DropdownInterface
     */
    protected $dropdown;

    /**
     * @var array map of core handlers
     */
    protected $coreHandlers;

    /**
     * @var array map of custom handlers
     */
    protected $customHandlers;

    /**
     * @var History
     */
    protected $history;

    /**
     * @var bool
     */
    protected $pushedEnter = false;

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
    protected $ddScrolling = false;

    /**
     * @var Callable
     */
    protected $completeFilter;

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

        $this->reader = new Input();
        $this->writer = new Output();

        $this->dropdown = $this->factoryDropdown();
        $this->line = new Line();
        $this->history = new History();

        $this->initKeyHandlers();
//
    }

    /**
     * @param string $prompt
     * @return string
     */
    public function read(string $prompt = ''): string
    {
        if ($prompt !== '') {
            $this->setPrompt($prompt);
        }

        $maxUsageLength = 4;
        $this->lastConsolePos = 0;

        $this->write($this->prompt);

        do {
            if ($this->ddScrolling) {
                $this->clearDropdown();
                $this->ddScrolling = false;
            } else {
                $this->updateDropdown();
                $this->clearAll();
                $this->renderLine();
            }

            $this->renderDropdown();

            $input = $this->reader->read($maxUsageLength);

            $this->resolveInput($input);

        } while (!$this->pushedEnter);

        $this->pushedEnter = false;
        $line = $this->line->getFull();
        $this->history->add($line);
        $this->line->clear();

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
     * @param Callable $completeFilter
     */
    public function setCompleteFilter(Callable $completeFilter)
    {
        $this->completeFilter = $completeFilter;
    }

    /**
     * @param string $prompt
     */
    public function setPrompt(string $prompt)
    {
        $this->prompt = $prompt;
    }

    /**
     * @param string $key
     * @param callable $handler
     */
    public function bind(string $key, Callable $handler)
    {
        $this->customHandlers[$key] = $handler;
    }

    /**
     * @return Line
     */
    public function getLine(): Line
    {
        return $this->line;
    }

    /**
     * @return Out
     */
    public function getWriter(): Out
    {
        return $this->writer;
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
        $this->registerCoreKeyHandler('"', 'handlerQuotes');

        /** @uses \Ridzhi\Readline\Readline::handlerClearLine() */
        $this->registerCoreKeyHandler("\033[3;", 'handlerClearLine');

    }

    /**
     * @param string $input
     */
    protected function handlerInput(string $input)
    {
        $this->line->insert($input);
    }

    /**
     * @param Readline $self
     */
    protected function handlerClearLine(Readline $self)
    {
        $self->line->clear();
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
        $self->line->insert('""');
        $self->line->cursorPrev();
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
        $self->line->backspace();
    }

    /**
     * @param Readline $self
     */
    protected function handlerDelete(Readline $self)
    {
        $self->line->delete();
    }

    /**
     * @param Readline $self
     */
    protected function handlerHome(Readline $self)
    {
        $self->line->cursorToBegin();
    }

    /**
     * @param Readline $self
     */
    protected function handlerEnd(Readline $self)
    {
        $self->line->cursorToEnd();
    }

    /**
     * @param Readline $self
     */
    protected function handlerTab(Readline $self)
    {
        $input = $self->line->getCurrent();
        $current = Parser::parse($input)->getCurrent();

        if ($current !== '') {
            $data = $self->completer->complete($input);
            $suffix = $self->getSuffix($current, $data);

            if ($suffix !== '') {
                $self->line->insert($suffix);
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
            $current = Parser::parse($self->line->getCurrent())->getCurrent();
            $completion = mb_substr($value, mb_strlen($current));
            $self->line->insert($completion);
        } else {
            $self->pushedEnter = true;
        }
    }

    /**
     * @param Readline $self
     */
    protected function handlerArrowUp(Readline $self)
    {
        $self->dropdown->scrollUp();
        $self->ddScrolling = true;
    }

    /**
     * TODO: для скрола добавить чеки что окно что то покащывпет
     * @param Readline $self
     */
    protected function handlerArrowDown(Readline $self)
    {
        $self->dropdown->scrollDown();
        $self->ddScrolling = true;
    }

    /**
     * @param Readline $self
     */
    protected function handlerArrowLeft(Readline $self)
    {
        $self->line->cursorPrev();
    }

    /**
     * @param Readline $self
     */
    protected function handlerArrowRight(Readline $self)
    {
        $self->line->cursorNext();
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
            $steps -= $x;
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
                $steps -= $limit;
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
     * @param $key
     * @param callable $handler
     */
    protected function bindCore($key, Callable $handler)
    {
        $this->coreHandlers[$key] = $handler;
    }

    /**
     * @param int|string $value ASCII code| String value
     * @param string $handler Function name
     */
    protected function registerCoreKeyHandler($value, string $handler)
    {
        $this->bindCore($value, [$this, $handler]);
    }

    /**
     * @param string $value
     */
    protected function insert(string $value)
    {
        $this->line->insert($value);
    }

    /**
     * @param string $command
     */
    protected function showHistory(string $command)
    {
        $this->line->clear();
        $this->line->insert($command);
    }

    protected function clearAll()
    {
        if ($this->lastConsolePos !== 0) {
            $this->cursorLeftWithAutoWrap($this->lastConsolePos);
        }

        Erase::down();
    }

    protected function renderLine()
    {
        $line = $this->line->getFull();
        $this->lastConsolePos = $this->line->getCursorPos();

        if (empty($line)) {
            return;
        }

        $this->write($line);

        $this->cursorLeftWithAutoWrap(mb_strlen($line));
        $this->cursorRightWithAutoWrap($this->lastConsolePos);
    }

    protected function renderDropdown()
    {
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

        $this->writer->writeAll($view);

        Cursor::restore();

        if ($diffY > 0) {
            Cursor::up($diffY);
        }

        Cursor::show();
    }

    protected function clearDropdown()
    {
        Cursor::save();
        Cursor::down();
        Erase::down();
        Cursor::restore();
    }

    protected function updateDropdown()
    {
        if ($this->completer instanceof CompleteInterface) {
            $items = $this->completer->complete($this->line->getCurrent());

            if (is_callable($this->completeFilter)) {
                $items = array_filter($items, $this->completeFilter);
            }

            $this->dropdown = $this->factoryDropdown($items);
        }
    }

    /**
     * @param string $input
     */
    protected function resolveInput(string $input)
    {
        $isService = $this->callHandler($input) || // core printable
            $this->callHandler(ord($input)) || // core not printable
            $this->callHandler($input, false); //user handlers

        if (!$isService && ($isChar = (mb_strlen($input) === 1))) {
            $this->handlerInput($input);
        }
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

        $this->writer->writeAll($value);

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
     * @param $key
     * @param bool $core
     * @return bool
     */
    protected function callHandler($key, $core = true): bool
    {
        if ($core) {
            $handlers = $this->coreHandlers;
            $context = $this;
        } else {
            $handlers = $this->customHandlers;
            $context = $this->getLine();
        }

        if (!isset($handlers[$key])) {
            return false;
        }

        call_user_func($handlers[$key], $context);

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