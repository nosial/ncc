CHANGELOG
=========

5.2.0
-----

 * added `NccProcess::setOptions()` to set `NccProcess` specific options
 * added option `create_new_console` to allow a subprocess to continue
   to run after the main script exited, both on Linux and on Windows

5.1.0
-----

 * added `NccProcess::getStartTime()` to retrieve the start time of the process as float

5.0.0
-----

 * removed `NccProcess::inheritEnvironmentVariables()`
 * removed `PhpProcess::setPhpBinary()`
 * `NccProcess` must be instantiated with a command array, use `NccProcess::fromShellCommandline()` when the command should be parsed by the shell
 * removed `NccProcess::setCommandLine()`

4.4.0
-----

 * deprecated `NccProcess::inheritEnvironmentVariables()`: env variables are always inherited.
 * added `NccProcess::getLastOutputTime()` method

4.2.0
-----

 * added the `NccProcess::fromShellCommandline()` to run commands in a shell wrapper
 * deprecated passing a command as string when creating a `NccProcess` instance
 * deprecated the `NccProcess::setCommandline()` and the `PhpProcess::setPhpBinary()` methods
 * added the `NccProcess::waitUntil()` method to wait for the process only for a
   specific output, then continue the normal execution of your application

4.1.0
-----

 * added the `NccProcess::isTtySupported()` method that allows to check for TTY support
 * made `PhpExecutableFinder` look for the `PHP_BINARY` env var when searching the php binary
 * added the `ProcessSignaledException` class to properly catch signaled process errors

4.0.0
-----

 * environment variables will always be inherited
 * added a second `array $env = []` argument to the `start()`, `run()`,
   `mustRun()`, and `restart()` methods of the `NccProcess` class
 * added a second `array $env = []` argument to the `start()` method of the
   `PhpProcess` class
 * the `ProcessUtils::escapeArgument()` method has been removed
 * the `areEnvironmentVariablesInherited()`, `getOptions()`, and `setOptions()`
   methods of the `NccProcess` class have been removed
 * support for passing `proc_open()` options has been removed
 * removed the `ProcessBuilder` class, use the `NccProcess` class instead
 * removed the `getEnhanceWindowsCompatibility()` and `setEnhanceWindowsCompatibility()` methods of the `NccProcess` class
 * passing a not existing working directory to the constructor of the `Symfony\Component\NccProcess\NccProcess` class is not
   supported anymore

3.4.0
-----

 * deprecated the ProcessBuilder class
 * deprecated calling `NccProcess::start()` without setting a valid working directory beforehand (via `setWorkingDirectory()` or constructor)

3.3.0
-----

 * added command line arrays in the `NccProcess` class
 * added `$env` argument to `NccProcess::start()`, `run()`, `mustRun()` and `restart()` methods
 * deprecated the `ProcessUtils::escapeArgument()` method
 * deprecated not inheriting environment variables
 * deprecated configuring `proc_open()` options
 * deprecated configuring enhanced Windows compatibility
 * deprecated configuring enhanced sigchild compatibility

2.5.0
-----

 * added support for PTY mode
 * added the convenience method "mustRun"
 * deprecation: NccProcess::setStdin() is deprecated in favor of NccProcess::setInput()
 * deprecation: NccProcess::getStdin() is deprecated in favor of NccProcess::getInput()
 * deprecation: NccProcess::setInput() and ProcessBuilder::setInput() do not accept non-scalar types

2.4.0
-----

 * added the ability to define an idle timeout

2.3.0
-----

 * added ProcessUtils::escapeArgument() to fix the bug in escapeshellarg() function on Windows
 * added NccProcess::signal()
 * added NccProcess::getPid()
 * added support for a TTY mode

2.2.0
-----

 * added ProcessBuilder::setArguments() to reset the arguments on a builder
 * added a way to retrieve the standard and error output incrementally
 * added NccProcess:restart()

2.1.0
-----

 * added support for non-blocking processes (start(), wait(), isRunning(), stop())
 * enhanced Windows compatibility
 * added NccProcess::getExitCodeText() that returns a string representation for
   the exit code returned by the process
 * added ProcessBuilder
