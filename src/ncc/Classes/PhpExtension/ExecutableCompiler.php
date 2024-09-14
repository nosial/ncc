<?php
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

    namespace ncc\Classes\PhpExtension;

    use ncc\Classes\NccExtension\ConstantCompiler;
    use ncc\CLI\Main;
    use ncc\Enums\LogLevel;
    use ncc\Enums\Options\BuildConfigurationOptions;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Exceptions\BuildException;
    use ncc\ThirdParty\Symfony\Process\ExecutableFinder;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\Utilities\Console;
    use ncc\Utilities\ConsoleProgressBar;
    use ncc\Utilities\Functions;
    use ncc\Utilities\PathFinder;

    class ExecutableCompiler extends NccCompiler
    {
        /**
         * @inheritDoc
         * @throws BuildException
         */
        public function build(string $build_configuration = BuildConfigurationValues::DEFAULT->value, array $options=[]): string
        {
            $configuration = $this->getProjectManager()->getProjectConfiguration()->getBuild()->getBuildConfiguration($build_configuration);

            if(count($options) > 0)
            {
                $configuration->setOptions(array_merge($configuration->getOptions(), $options));
            }

            if(!isset($configuration->getOptions()[BuildConfigurationOptions::NCC_CONFIGURATION->value]))
            {
                throw new BuildException(sprintf("Unable to compile the binary, the build configuration '%s' does not have a ncc_configuration.", $build_configuration));
            }

            // Build the ncc package first
            Console::outVerbose('Building ncc package.');
            $ncc_package = parent::build($configuration->getOptions()[BuildConfigurationOptions::NCC_CONFIGURATION->value]);

            // Prepare the ncc package for compilation
            $hex_dump_file = PathFinder::getCachePath() . DIRECTORY_SEPARATOR . $this->getProjectManager()->getProjectConfiguration()->getAssembly()->getName() . '.c';
            if(is_file($hex_dump_file))
            {
                unlink($hex_dump_file);
            }

            Console::outVerbose(sprintf('Converting ncc package %s to hex dump', $ncc_package));
            $this->hexDump($ncc_package, $hex_dump_file, $this->getProjectManager()->getProjectConfiguration()->getAssembly()->getName());

            // Prepare the gcc command
            $gcc_path = (new ExecutableFinder())->find('gcc');

            if(isset($configuration->getOptions()[BuildConfigurationOptions::OUTPUT_FILE->value]))
            {
                $binary_path = ConstantCompiler::compileConstants(
                    $this->getProjectManager()->getProjectConfiguration(),
                    $configuration->getOptions()[BuildConfigurationOptions::OUTPUT_FILE->value]
                );
            }
            else
            {
                $binary_path = ConstantCompiler::compileConstants($this->getProjectManager()->getProjectConfiguration(), $configuration->getOutput());
            }

            if($gcc_path === null)
            {
                throw new BuildException("Unable to find gcc executable, please make sure it is installed and in your PATH environment variable.");
            }

            if(!is_file(__DIR__ . DIRECTORY_SEPARATOR . 'bootstrap_main.c'))
            {
                throw new BuildException("Unable to find bootstrap_main.c, please make sure ncc is installed correctly.");
            }

            $gcc_options = [
                __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap_main.c',
                realpath($hex_dump_file)
            ];

            foreach($configuration->getOptions() as $option => $value)
            {
                if(str_starts_with($option, 'gcc-'))
                {
                    $gcc_options[] = sprintf('-%s%s', substr($option, 4), $value === null ? '' : '=' . $value);
                }
            }

            $gcc_options[] = '-o';
            $gcc_options[] = $binary_path;

            switch(Main::getLogLevel())
            {
                case LogLevel::VERBOSE->value:
                    $gcc_options[] = '-v';
                    break;

                case LogLevel::DEBUG->value:
                    $gcc_options[] = '-v';
                    $gcc_options[] = '-v';
                    break;
            }

            $process = new Process([$gcc_path, ...$gcc_options]);
            $process->setTimeout(0);

            Console::outVerbose(sprintf('Compiling executable to %s: %s', $binary_path, implode(' ', $gcc_options)));
            $process->run(static function ($type, $buffer)
            {
                // If $buffer contains multiple lines, split it and output each line separately
                if(str_contains($buffer, "\n"))
                {
                    foreach(explode("\n", $buffer) as $line)
                    {
                        if($line === '')
                        {
                            continue;
                        }

                        Console::outVerbose(rtrim($line, "\n"));
                    }

                    return;
                }

                Console::outVerbose(rtrim($buffer, "\n"));
            });

            if(!$process->isSuccessful())
            {
                unlink($hex_dump_file);
                throw new BuildException(sprintf("Unable to compile the binary, gcc exited with code %d: %s", $process->getExitCode(), $process->getErrorOutput()));
            }

            // Finally, remove the hex dump file and return the executable path
            unlink($hex_dump_file);
            return $binary_path;
        }

        /**
         * Creates a hex dump of the binary data and writes it to the output file suitable for inclusion in a C source
         * file, this is a similar utility to xxd.
         *
         * @param string $input_path
         * @param string $output_path
         * @param string $variable_name
         * @return void
         */
        private function hexDump(string $input_path, string $output_path, string $variable_name): void
        {
            Console::outVerbose(sprintf('Processing %s to hex dump', $input_path));

            $input = fopen($input_path, 'rb');
            $output = fopen($output_path, 'wb');
            $byte_count = 0;
            $progress_bar = new ConsoleProgressBar(sprintf('HexDump %s', $input_path), filesize($input_path));

            fwrite($output, sprintf("unsigned char %s[] = {\n", Functions::toSnakeCase($variable_name)));
            // Convert the binary data to hex and write it to the output file using chunks
            while (!feof($input))
            {
                $bytes = fread($input, 5026);
                $len = strlen($bytes);

                for ($i = 0; $i < $len; $i++)
                {
                    fwrite($output, sprintf(" 0x%02x,", ord($bytes[$i])));
                    $byte_count++;

                    // Write 12 bytes per line or when reaching the end of the file
                    if ($byte_count === 12 || ($i == $len - 1 && feof($input)))
                    {
                        fwrite($output, "\n");
                        $byte_count = 0;
                    }
                }

                $progress_bar->increaseValue($len, true);
                $progress_bar->setMiscText(sprintf('Processed (%d/%d)', $progress_bar->getValue(), $progress_bar->getMaxValue()));
            }

            // Close the output file
            fseek($output, -2, SEEK_END);
            fwrite($output, "\n");
            fwrite($output, "};\n");

            // Finally, close the input and output files
            fclose($input);
            fclose($output);

            // Close the progress bar
            $progress_bar->setMiscText('done', true);
            unset($progress_bar);
        }
    }