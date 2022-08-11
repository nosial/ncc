CHANGELOG
=========

5.2.0
-----

 * added `process::setOptions()` to set `process` specific options
 * added option `create_new_console` to allow a subprocess to continue
   to run after the main script exited, both on Linux and on Windows

5.1.0
-----

 * added `process::getStartTime()` to retrieve the start time of the process as float

5.0.0
-----

 * removed `process::inheritEnvironmentVariables()`
 * removed `Phpprocess::setPhpBinary()`
 * `process` must be instantiated with a command array, use `process::fromShellCommandline()` when the command should be parsed by the shell
 * removed `process::setCommandLine()`

4.4.0
-----

 * deprecated `process::inheritEnvironmentVariables()`: env variables are always inherited.
 * added `process::getLastOutputTime()` method

4.2.0
-----

 * added the `process::fromShellCommandline()` to run commands in a shell wrapper
 * deprecated passing a command as string when creating a `process` instance
 * deprecated the `process::setCommandline()` and the `Phpprocess::setPhpBinary()` methods
 * added the `process::waitUntil()` method to wait for the process only for a
   specific output, then continue the normal execution of your application

4.1.0
-----

 * added the `process::isTtySupported()` method that allows to check for TTY support
 * made `PhpExecutableFinder` look for the `PHP_BINARY` env var when searching the php binary
 * added the `processSignaledException` class to properly catch signaled process errors

4.0.0
-----

 * environment variables will always be inherited
 * added a second `array $env = []` argument to the `start()`, `run()`,
   `mustRun()`, and `restart()` methods of the `process` class
 * added a second `array $env = []` argument to the `start()` method of the
   `Phpprocess` class
 * the `processUtils::escapeArgument()` method has been removed
 * the `areEnvironmentVariablesInherited()`, `getOptions()`, and `setOptions()`
   methods of the `process` class have been removed
 * support for passing `proc_open()` options has been removed
 * removed the `processBuilder` class, use the `process` class instead
 * removed the `getEnhanceWindowsCompatibility()` and `setEnhanceWindowsCompatibility()` methods of the `process` class
 * passing a not existing working directory to the constructor of the `Symfony\Component\process\process` class is not
   supported anymore

3.4.0
-----

 * deprecated the processBuilder class
 * deprecated calling `process::start()` without setting a valid working directory beforehand (via `setWorkingDirectory()` or constructor)

3.3.0
-----

 * added command line arrays in the `process` class
 * added `$env` argument to `process::start()`, `run()`, `mustRun()` and `restart()` methods
 * deprecated the `processUtils::escapeArgument()` method
 * deprecated not inheriting environment variables
 * deprecated configuring `proc_open()` options
 * deprecated configuring enhanced Windows compatibility
 * deprecated configuring enhanced sigchild compatibility

2.5.0
-----

 * added support for PTY mode
 * added the convenience method "mustRun"
 * deprecation: process::setStdin() is deprecated in favor of process::setInput()
 * deprecation: process::getStdin() is deprecated in favor of process::getInput()
 * deprecation: process::setInput() and processBuilder::setInput() do not accept non-scalar types

2.4.0
-----

 * added the ability to define an idle timeout

2.3.0
-----

 * added processUtils::escapeArgument() to fix the bug in escapeshellarg() function on Windows
 * added process::signal()
 * added process::getPid()
 * added support for a TTY mode

2.2.0
-----

 * added processBuilder::setArguments() to reset the arguments on a builder
 * added a way to retrieve the standard and error output incrementally
 * added process:restart()

2.1.0
-----

 * added support for non-blocking processes (start(), wait(), isRunning(), stop())
 * enhanced Windows compatibility
 * added process::getExitCodeText() that returns a string representation for
   the exit code returned by the process
 * added processBuilder
