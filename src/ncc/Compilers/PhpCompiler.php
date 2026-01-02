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

    namespace ncc\Compilers;

    use ncc\Classes\IO;
    use ncc\Classes\Logger;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;

    /**
     * PhpCompiler extends PackageCompiler to create self-executable PHP packages.
     * 
     * This compiler wraps the compiled ncc package with a PHP header that allows
     * the package to be executed directly as a PHP script. The header handles:
     * - Loading the ncc runtime
     * - Importing the package into the runtime
     * - Detecting CLI vs Web execution environment
     * - Executing the appropriate entry point (entryPoint for CLI, webEntryPoint for Web)
     */
    class PhpCompiler extends PackageCompiler
    {
        /**
         * @inheritDoc
         */
        public function compile(?callable $progressCallback=null, bool $overwrite=true): string
        {
            Logger::getLogger()->verbose('PhpCompiler: Starting PHP executable package compilation');
            
            // First, compile using the parent PackageCompiler to create the base package
            $packagePath = parent::compile($progressCallback, $overwrite);
            
            Logger::getLogger()->verbose(sprintf('PhpCompiler: Base package compiled at: %s', $packagePath));
            Logger::getLogger()->verbose('PhpCompiler: Adding PHP executable header');
            
            // Now modify the file to add the PHP executable header
            $this->addPhpHeader($packagePath);
            
            Logger::getLogger()->verbose('PhpCompiler: PHP executable package compilation completed');
            return $packagePath;
        }

        /**
         * Adds the PHP executable header to the compiled package file.
         * 
         * This method reads the existing package data, prepends a PHP header with
         * shebang and runtime initialization code, and writes the combined content
         * back to the file. The file is then made executable.
         *
         * @param string $packagePath The path to the compiled package file
         */
        private function addPhpHeader(string $packagePath): void
        {
            try
            {
                Logger::getLogger()->debug(sprintf('Reading original package data from: %s', $packagePath));
                
                // Read the original package data
                $packageData = IO::readFile($packagePath);
                
                Logger::getLogger()->debug('Generating PHP executable header');
                
                // Create the PHP header
                $header = $this->generatePhpHeader();
                
                // Combine header and package data
                $newContent = $header . $packageData;
                
                Logger::getLogger()->debug('Writing combined content back to file');
                
                // Write back to file
                IO::writeFile($packagePath, $newContent);
                
                // Make the file executable (Unix/Linux only)
                if(DIRECTORY_SEPARATOR === '/')
                {
                    Logger::getLogger()->debug('Setting executable permissions');
                    chmod($packagePath, 0755);
                }
                
                Logger::getLogger()->verbose(sprintf('PHP header added successfully. Final size: %d bytes', strlen($newContent)));
            }
            catch(IOException $e)
            {
                throw new OperationException(sprintf('Failed to add PHP header to package: %s', $e->getMessage()), $e->getCode(), $e);
            }
        }

        /**
         * Generates the PHP executable header code.
         * 
         * The header includes:
         * - Shebang line for direct execution
         * - ncc runtime loading logic
         * - Package import and initialization
         * - Environment detection (CLI vs Web)
         * - Appropriate entry point execution
         * - __halt_compiler() directive to separate code from package data
         *
         * @return string The complete PHP header as a string
         */
        private function generatePhpHeader(): string
        {
            return <<<'PHP'
#!/usr/bin/env php
<?php
/**
 * Auto-generated PHP executable wrapper for ncc package
 * This file is self-contained and can be executed directly.
 */

// Ensure ncc runtime is loaded
require 'ncc';

try
{
    // Import this package into the runtime
    import(realpath(__FILE__));
    
    // Create a package reader to access the header information
    $packageReader = new \ncc\Classes\PackageReader(realpath(__FILE__), true);
    $header = $packageReader->getHeader();
    
    // Determine the execution environment
    $isCli = (php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg');
    $isWeb = !$isCli;
    
    // Get the appropriate entry point based on environment
    $entryPoint = null;
    if($isCli)
    {
        $entryPoint = $header->getEntryPoint();
        if($entryPoint === null)
        {
            fwrite(STDERR, "Error: No CLI entry point defined in package.\n");
            exit(1);
        }
    }
    else
    {
        $entryPoint = $header->getWebEntryPoint();
        if($entryPoint === null)
        {
            // Fall back to CLI entry point if web entry point is not defined
            $entryPoint = $header->getEntryPoint();
            if($entryPoint === null)
            {
                http_response_code(500);
                echo "Error: No entry point defined in package.";
                exit(1);
            }
        }
    }
    
    // Execute the appropriate entry point directly from the package reader
    if($isCli)
    {
        // CLI execution - pass command line arguments (excluding script name)
        $arguments = array_slice($argv ?? [], 1);
        $result = $packageReader->execute($entryPoint, $arguments);
        
        // If the execution returns an integer, use it as exit code
        if(is_int($result))
        {
            exit($result);
        }
    }
    else
    {
        // Web execution - no arguments passed
        $packageReader->execute($entryPoint, []);
    }
}
catch(\ncc\Exceptions\OperationException $e)
{
    if(php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg')
    {
        fwrite(STDERR, "Import Error: " . $e->getMessage() . "\n");
        exit(1);
    }
    else
    {
        http_response_code(500);
        echo "Import Error: " . htmlspecialchars($e->getMessage());
        exit(1);
    }
}
catch(\Exception $e)
{
    if(php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg')
    {
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        if(getenv('NCC_DEBUG'))
        {
            fwrite(STDERR, $e->getTraceAsString() . "\n");
        }
        exit(1);
    }
    else
    {
        http_response_code(500);
        echo "Internal Server Error: " . htmlspecialchars($e->getMessage());
        if(getenv('NCC_DEBUG'))
        {
            echo "\n<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        exit(1);
    }
}

__halt_compiler();
?>
PHP;
        }
    }
