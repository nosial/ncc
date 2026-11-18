<?php
/*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

namespace ncc\Classes;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

class ConsoleProgressTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetConsole();
        Console::disableAnsiColors();
        Console::disableSimplifiedMode();
    }

    protected function tearDown(): void
    {
        $this->resetConsole();
        Console::enableAnsiColors();
    }

    /**
     * Resets the progress state of the Console and forces the animated progress bar (non-verbose) mode
     */
    private function resetConsole(): void
    {
        $values = [
            'progressScopes' => [],
            'lastFraction' => 0.0,
            'lastProgressOutput' => null,
            'lastProgressLength' => 0,
            'currentProgressMessage' => null,
            'currentSimplifiedOperation' => null,
            'isVerboseOrDebugMode' => false,
            'smoothProgressSupported' => true,
        ];

        foreach($values as $name => $value)
        {
            $property = new ReflectionProperty(Console::class, $name);
            $property->setAccessible(true);
            $property->setValue(null, $value);
        }
    }

    /**
     * Captures everything printed by the given callable
     */
    private function capture(callable $callable): string
    {
        ob_start();
        try
        {
            $callable();
        }
        finally
        {
            $output = ob_get_clean();
        }

        return $output;
    }

    /**
     * Returns the percentages of every progress bar rendered in the output
     *
     * @return int[]
     */
    private function percentages(string $output): array
    {
        preg_match_all('/\] +(\d+)% - /', $output, $matches);
        return array_map('intval', $matches[1]);
    }

    public function testProgressWithoutScopeIsUnchanged(): void
    {
        $output = $this->capture(static function()
        {
            Console::inlineProgress(1, 4, 'Working');
        });

        $this->assertSame("\r\033[K[" . str_repeat('█', 7) . '▌' . str_repeat(' ', 22) . ']  25% - Working', $output);
        $this->assertSame(0, Console::getProgressScopeDepth());
    }

    public function testProgressWithoutScopeCanGoBackwards(): void
    {
        $output = $this->capture(static function()
        {
            Console::inlineProgress(80, 100, 'First');
            Console::completeProgress();
            Console::inlineProgress(10, 100, 'Second');
        });

        $this->assertSame([80, 10], $this->percentages($output));
    }

    public function testNestedScopesMapProgress(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.5, 1.0, static function()
            {
                Console::inlineProgress(50, 100, 'Half of the second half');

                Console::withProgressScope(0.5, 1.0, static function()
                {
                    Console::inlineProgress(0, 100, 'Nested start');
                    Console::inlineProgress(100, 100, 'Nested end');
                });
            });
        });

        // The finished nested scope redraws the bar at the end of its slice
        $this->assertSame([75, 75, 100, 100], $this->percentages($output));
    }

    public function testScopeReturnsOperationResult(): void
    {
        $result = null;
        $this->capture(static function() use (&$result)
        {
            $result = Console::withProgressScope(0.0, 1.0, static fn() => 'result');
        });

        $this->assertSame('result', $result);
    }

    public function testProgressNeverDecreasesWithinScope(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.0, 1.0, static function()
            {
                Console::withProgressScope(0.0, 0.5, static function()
                {
                    Console::inlineProgress(100, 100, 'First operation done');
                });

                // Each operation reports its own 0..100, which must not move the shared bar backwards
                Console::withProgressScope(0.0, 0.5, static function()
                {
                    Console::inlineProgress(0, 100, 'Second operation start');
                });

                Console::inlineProgress(10, 100, 'Lower progress');
            });
        });

        $percentages = $this->percentages($output);
        $this->assertNotEmpty($percentages);

        $sorted = $percentages;
        sort($sorted);
        $this->assertSame($sorted, $percentages, 'Progress must never decrease within a scope');
    }

    public function testCompleteProgressInsideScopeKeepsBar(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.0, 1.0, static function()
            {
                Console::inlineProgress(40, 100, 'Working');
                Console::completeProgress('Step finished');
                Console::inlineProgress(60, 100, 'Still working');
            });
        });

        // The final message is printed above the bar and the bar is drawn again below it
        $this->assertStringContainsString("\r\033[KStep finished" . PHP_EOL . '[', $output);
        $this->assertSame([40, 40, 60], $this->percentages($output));
    }

    public function testOuterScopeClearsBar(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.0, 1.0, static function()
            {
                Console::inlineProgress(100, 100, 'Done');
            });
        });

        $this->assertStringEndsWith("\r\033[K", $output);
        $this->assertSame(0, Console::getProgressScopeDepth());

        // Once the outermost scope ended the progress starts over
        $output = $this->capture(static function()
        {
            Console::inlineProgress(10, 100, 'New operation');
        });
        $this->assertSame([10], $this->percentages($output));
    }

    public function testScopeIsPoppedAfterException(): void
    {
        $this->capture(function()
        {
            try
            {
                Console::withProgressScope(0.0, 1.0, static function()
                {
                    Console::withProgressScope(0.2, 0.8, static function()
                    {
                        Console::inlineProgress(50, 100, 'Failing');
                        throw new RuntimeException('Failure');
                    });
                });
                $this->fail('The exception should be rethrown');
            }
            catch(RuntimeException $e)
            {
                $this->assertSame('Failure', $e->getMessage());
            }
        });

        $this->assertSame(0, Console::getProgressScopeDepth());
    }

    public function testOutWhileBarIsDrawnKeepsBarAtBottom(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.0, 1.0, static function()
            {
                Console::inlineProgress(50, 100, 'Working');
                Console::out('A message');
            });
        });

        $bar = '[' . str_repeat('█', 15) . str_repeat(' ', 15) . ']  50% - Working';
        $this->assertStringContainsString($bar . "\r\033[KA message" . PHP_EOL . $bar, $output);
    }

    public function testOutWithoutBarIsUnchanged(): void
    {
        $output = $this->capture(static function()
        {
            Console::out('A message');
        });

        $this->assertSame('A message' . PHP_EOL, $output);
    }

    public function testProgressMessageInsideScopeKeepsProgress(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.0, 1.0, static function()
            {
                Console::inlineProgress(40, 100, 'Working');
                Console::progressMessage('Resolving package');
            });
        });

        // Only the inline text changes, no line is printed above the bar
        $this->assertSame([40, 40], $this->percentages($output));
        $this->assertStringContainsString(']  40% - Resolving package', $output);
        $this->assertStringNotContainsString(PHP_EOL, $output);
    }

    public function testProgressMessageStartsAtScopeStart(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.5, 1.0, static function()
            {
                Console::progressMessage('Installing dependency');
            });
        });

        $this->assertSame([50], $this->percentages($output));
    }

    public function testProgressMessageWithoutScopePrintsLine(): void
    {
        $output = $this->capture(static function()
        {
            Console::progressMessage('A message');
        });

        $this->assertSame('A message' . PHP_EOL, $output);
    }

    public function testSimplifiedProgressMessagePrintsOnce(): void
    {
        Console::enableSimplifiedMode();

        try
        {
            $output = $this->capture(static function()
            {
                Console::withProgressScope(0.0, 1.0, static function()
                {
                    Console::progressMessage('Installing package');
                    Console::progressMessage('Installing package');
                });
            });
        }
        finally
        {
            Console::disableSimplifiedMode();
        }

        $this->assertSame('Installing package' . PHP_EOL, $output);
    }

    public function testSuspendAndResumeRedrawBar(): void
    {
        $output = $this->capture(static function()
        {
            Console::withProgressScope(0.0, 1.0, static function()
            {
                Console::inlineProgress(50, 100, 'Working');
                Console::suspendProgress();
                print('log event' . PHP_EOL);
                Console::resumeProgress();
            });
        });

        $bar = '[' . str_repeat('█', 15) . str_repeat(' ', 15) . ']  50% - Working';
        $this->assertStringContainsString($bar . "\r\033[Klog event" . PHP_EOL . "\r\033[K" . $bar, $output);
    }

    public function testSuspendAndResumeWithoutBarPrintNothing(): void
    {
        $output = $this->capture(static function()
        {
            Console::suspendProgress();
            Console::resumeProgress();
        });

        $this->assertSame('', $output);
    }

    public function testSmoothProgressUsesEighthBlocks(): void
    {
        $output = $this->capture(static function()
        {
            Console::inlineProgress(43, 100, 'Working');
        });

        $this->assertSame("\r\033[K[" . str_repeat('█', 12) . '▉' . str_repeat(' ', 17) . ']  43% - Working', $output);
    }

    public function testLineProgressUsesConnectedLine(): void
    {
        $this->setSmoothProgress(false);

        $output = $this->capture(static function()
        {
            Console::inlineProgress(1, 4, 'Working');
            Console::completeProgress();
            Console::inlineProgress(43, 100, 'Working');
        });

        $this->assertStringContainsString('[' . str_repeat('━', 7) . '╸' . str_repeat('─', 22) . ']  25% - Working', $output);
        $this->assertStringContainsString('[' . str_repeat('━', 13) . str_repeat('─', 17) . ']  43% - Working', $output);
    }

    public function testProgressBarKeepsWidthAtBounds(): void
    {
        foreach([true, false] as $smooth)
        {
            $this->setSmoothProgress($smooth);

            foreach([0, 1, 99, 100] as $percent)
            {
                $output = $this->capture(static function() use ($percent)
                {
                    Console::inlineProgress($percent, 100, 'Working');
                    Console::completeProgress();
                });

                $this->assertSame(1, preg_match('/\[(.*?)\]/u', $output, $matches));
                $this->assertSame(30, mb_strlen($matches[1]), 'Width at ' . $percent . '% (smooth: ' . var_export($smooth, true) . ')');
            }
        }

        $this->setSmoothProgress(true);
        $full = $this->capture(static function()
        {
            Console::inlineProgress(100, 100, 'Working');
        });
        $this->assertStringContainsString('[' . str_repeat('█', 30) . ']', $full);
    }

    public function testColoredProgressDimsEmptyPart(): void
    {
        Console::enableAnsiColors();
        $this->setSmoothProgress(false);

        try
        {
            $output = $this->capture(static function()
            {
                Console::inlineProgress(50, 100, 'Working');
            });
        }
        finally
        {
            Console::disableAnsiColors();
        }

        $this->assertStringContainsString("\e[93m" . str_repeat('━', 15) . "\e[0m\e[2m" . str_repeat('─', 15) . "\e[0m", $output);
    }

    #[DataProvider('smoothProgressEnvironmentProvider')]
    public function testDetectSmoothProgress(string $osFamily, array $environment, bool $expected): void
    {
        $method = new ReflectionMethod(Console::class, 'detectSmoothProgress');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke(null, $osFamily, $environment));
    }

    public static function smoothProgressEnvironmentProvider(): array
    {
        return [
            'utf-8 terminal' => ['Linux', ['TERM' => 'xterm-256color', 'LANG' => 'en_US.UTF-8'], true],
            'utf8 spelling' => ['Linux', ['TERM' => 'xterm', 'LANG' => 'en_US.utf8'], true],
            'unset locale' => ['Linux', ['TERM' => 'xterm-256color'], true],
            'linux console' => ['Linux', ['TERM' => 'linux', 'LANG' => 'en_US.UTF-8'], false],
            'non utf-8 locale' => ['Linux', ['TERM' => 'xterm', 'LANG' => 'en_US.ISO-8859-1'], false],
            'c locale' => ['Linux', ['TERM' => 'xterm', 'LC_ALL' => 'C'], false],
            'lc_all wins over lang' => ['Linux', ['LC_ALL' => 'C', 'LANG' => 'en_US.UTF-8'], false],
            'lc_ctype wins over lang' => ['Linux', ['LC_CTYPE' => 'en_US.UTF-8', 'LANG' => 'C'], true],
            'empty lc_all is skipped' => ['Linux', ['LC_ALL' => '', 'LANG' => 'en_US.UTF-8'], true],
            'macos' => ['Darwin', ['TERM' => 'xterm-256color', 'LANG' => 'en_US.UTF-8'], true],
            'legacy windows console' => ['Windows', [], false],
            'windows terminal' => ['Windows', ['WT_SESSION' => 'abc'], true],
            'vs code on windows' => ['Windows', ['TERM_PROGRAM' => 'vscode'], true],
            'conemu' => ['Windows', ['ConEmuANSI' => 'ON'], true],
        ];
    }

    /**
     * Forces the progress bar style instead of detecting it from the environment
     */
    private function setSmoothProgress(bool $smooth): void
    {
        $property = new ReflectionProperty(Console::class, 'smoothProgressSupported');
        $property->setAccessible(true);
        $property->setValue(null, $smooth);
    }
}
