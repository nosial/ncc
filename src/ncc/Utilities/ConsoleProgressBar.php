<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
     *
     *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
     *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
     *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
     *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
     *  conditions:
     *
     *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
     *  of the Software.
     *
     *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
     *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
     *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
     *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
     *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
     *  DEALINGS IN THE SOFTWARE.
     *
     */

    namespace ncc\Utilities;

    use ncc\CLI\Main;
    use ncc\Enums\LogLevel;

    class ConsoleProgressBar
    {
        /**
         * @var int
         */
        private $value;

        /**
         * @var int
         */
        private $max_value;

        /**
         * @var int
         */
        private $terminal_width;

        /**
         * @var int
         */
        private $progress_width;

        /**
         * @var bool
         */
        private $ended;

        /**
         * @var string
         */
        private $title;

        /**
         * @var string|null
         */
        private $misc_text;

        /**
         * @var bool
         */
        private $auto_end;

        /**
         * Constructor for the object.
         *
         * This constructor initializes the object with the given parameters. By default,
         * the $max_value parameter is set to 100, and the $progress_width parameter is set to 20.
         * The object's title is set to the given $title value, and other properties are initialized
         * accordingly.
         *
         * @param string $title The title to be set for the object.
         * @param int $max_value Optional. The maximum value for the object's progress. Defaults to 100.
         * @param bool $auto_end Optional. If True, when the progress bar reaches the $max_value, a new line is created.
         * @param int $progress_width Optional. The width of the progress bar in characters. Defaults to 20.
         * @return void
         */
        public function __construct(string $title, int $max_value=100, bool $auto_end=false, int $progress_width=20)
        {
            $this->title = $title;
            $this->progress_width = $progress_width;
            $this->value = 0;
            $this->max_value = $max_value;
            $this->ended = false;
            $this->auto_end = $auto_end;
            $this->terminal_width = $this->getTerminalWidth();
        }

        /**
         * Get the title of the object.
         *
         * @return string The title of the object.
         */
        public function getTitle(): string
        {
            return $this->title;
        }

        /**
         * Sets the title of the object.
         *
         * This method sets the title of the object to the given value. Optionally,
         * it can also trigger an update if the $update parameter is set to true.
         *
         * @param string $title The new title to be set.
         * @param bool $update Optional. Whether to trigger an update after setting the title.
         *                     Defaults to false.
         * @return void
         */
        public function setTitle(string $title, bool $update=false): void
        {
            $this->title = $title;

            if($update)
            {
                $this->update();
            }
        }

        /**
         * Retrieves the miscellaneous text.
         *
         * This method retrieves the miscellaneous text associated with the object.
         *
         * @return string|null The miscellaneous text, or null if it is not set.
         */
        public function getMiscText(): ?string
        {
            return $this->misc_text;
        }

        /**
         * Sets the miscellaneous text of the object.
         *
         * This method sets the miscellaneous text of the object to the given value. Optionally,
         * it can also trigger an update if the $update parameter is set to true.
         *
         * @param string|null $misc_text The new miscellaneous text to be set. If null, the miscellaneous text will be cleared.
         * @param bool $update Optional. Whether to trigger an update after setting the miscellaneous text.
         *                     Defaults to false.
         * @return void
         */
        public function setMiscText(?string $misc_text, bool $update=false): void
        {
            $this->misc_text = $misc_text;

            if($update)
            {
                $this->update();
            }
        }

        /**
         * Gets the value of the object.
         *
         * This method retrieves the value of the object.
         *
         * @return int The value of the object.
         */
        public function getValue(): int
        {
            return $this->value;
        }

        /**
         * Sets the value of the object.
         *
         * This method sets the value of the object to the given value. Optionally,
         * it can also trigger an update if the $update parameter is set to true.
         * If the given value is greater than the maximum value, it will be set to
         * the maximum value. If the given value is less than 0, it will be set to 0.
         *
         * @param int $value The new value to be set.
         * @param bool $update Optional. Whether to trigger an update after setting the value.
         *                     Defaults to false.
         * @return void
         */
        public function setValue(int $value, bool $update=false): void
        {
            if($value > $this->max_value)
            {
                $value = $this->max_value;
            }
            elseif($value < 0)
            {
                $value = 0;
            }

            $this->value = $value;

            if($update)
            {
                $this->update();
            }
        }

        /**
         * Increases the value of the object by the given amount.
         *
         * This method increases the current value of the object by the specified amount.
         * Optionally, it can also trigger an update if the $update parameter is set to true.
         *
         * @param int $value The amount by which to increase the value.
         * @param bool $update Optional. Whether to trigger an update after increasing the value.
         *                     Defaults to false.
         * @return void
         */
        public function increaseValue(int $value=1, bool $update=false): void
        {
            $this->setValue($this->value + $value, $update);
        }

        /**
         * Decreases the value of the object by the given amount.
         *
         * This method decreases the value of the object by the specified amount.
         * Optionally, it can also trigger an update if the $update parameter is set to true.
         *
         * @param int $value The amount to decrease the value by.
         * @param bool $update Optional. Whether to trigger an update after decreasing the value.
         *                     Defaults to false.
         * @return void
         */
        public function decreaseValue(int $value, bool $update=false): void
        {
            $this->setValue($this->value - $value, $update);
        }

        /**
         * Retrieves the maximum value.
         *
         * This method returns the current maximum value stored in the object.
         *
         * @return int The maximum value.
         */
        public function getMaxValue(): int
        {
            return $this->max_value;
        }

        /**
         * Sets the maximum value of the object.
         *
         * This method sets the maximum value of the object to the given value. Optionally,
         * it can also trigger an update if the $update parameter is set to true.
         * If the given $max_value is negative, it is set to 0.
         *
         * @param int $max_value The new maximum value to be set.
         * @param bool $update Optional. Whether to trigger an update after setting the maximum value.
         *                     Defaults to false.
         * @return void
         */
        public function setMaxValue(int $max_value, bool $update=false): void
        {
            if($max_value < 0)
            {
                $max_value = 0;
            }

            $this->max_value = $max_value;

            if($update)
            {
                $this->update();
            }
        }

        /**
         * Updates the object's state.
         *
         * This method updates the state of the object based on its current value and max value.
         * If the current value is greater than or equal to the max value, it prints the information
         * and progress bar using the renderInformation() and renderProgressBar() methods respectively,
         * and sets the 'ended' flag to true.
         * If the current value is less than the max value, it prints the information and progress bar
         * using the renderInformation() and renderProgressBar() methods respectively, but without
         * printing a new line.
         *
         * @return void
         */
        public function update(): void
        {
            if(LogLevel::VERBOSE->checkLogLevel(Main::getLogLevel()))
            {
                return;
            }

            if($this->auto_end && $this->value >= $this->max_value)
            {
                print($this->renderInformation() . $this->renderProgressBar() . "\n");
                $this->ended = true;
            }
            else
            {
                print($this->renderInformation() . $this->renderProgressBar() . "\r");
            }
        }

        /**
         * Retrieves the width of the terminal.
         *
         * This method retrieves the width of the terminal by executing the 'tput cols' command.
         * If the command execution fails or the output is empty, a default width of 70 is returned.
         *
         * @return int The width of the terminal.
         */
        private function getTerminalWidth(): int
        {
            exec('tput cols', $output, $result);

            if(empty($output[0]) || $result !== 0)
            {
                return 70;
            }

            return (int)$output[0];
        }

        /**
         * Renders the information to be displayed.
         *
         * This method calculates and returns a string containing the rendered information
         * based on the current state of the object. The information includes the title,
         * optional miscellaneous text, and any required spacing and formatting for display.
         *
         * @return string The rendered information string.
         */
        private function renderInformation(): string
        {
            // Resize title and misc if the terminal width is too small
            $max_text_length = $this->terminal_width - $this->progress_width - 10;

            if(strlen($this->title . ' ' . ($this->misc_text ?? '')) > $max_text_length)
            {
                // Calculate the maximum length of title and misc and assign them new truncated values
                $new_title_length = floor($max_text_length * strlen($this->title) / (strlen($this->title) + strlen($this->misc_text)));

                $title = substr($this->title, 0, $new_title_length);
                $misc  = substr($this->misc_text, 0, ($max_text_length - $new_title_length));
            }
            else
            {
                $title = $this->title;
                $misc  = $this->misc_text;
            }

            $spaces = $this->terminal_width - strlen($title) - strlen($misc) - $this->progress_width - 10;
            $line = $title . str_repeat(' ', $spaces);

            if (!empty($misc))
            {
                $line .= ' ' . $misc;
            }

            return $line;
        }

        /**
         * Renders the progress bar as a string.
         *
         * This method calculates the number of hashes and dashes based on the current
         * progress and width of the progress bar. It also calculates the percentage
         * completed and formats it with two decimal places at the end. It then constructs
         * and returns the progress bar string.
         *
         * @return string The progress bar string.
         */
        private function renderProgressBar(): string
        {
            $hashes_count = 0;
            $percent_done = 0;

            if($this->max_value !== 0)
            {
                $hashes_count = round($this->progress_width * $this->value / $this->max_value);
                $percent_done = round($this->value * 100 / $this->max_value);
            }

            $dashes_count = $this->progress_width - $hashes_count;
            return ' [' . str_repeat('#', $hashes_count) . str_repeat('-', $dashes_count) . ']' . sprintf('%4s', $percent_done) . '%';
        }

        /**
         * Destructor for the object.
         *
         * This method is automatically called when the object is destroyed. It checks
         * if the object has already ended and if not, it prints a new line character.
         *
         * @return void
         */
        public function __destruct()
        {
            if(!$this->ended)
            {
                print(PHP_EOL);
            }
        }
    }