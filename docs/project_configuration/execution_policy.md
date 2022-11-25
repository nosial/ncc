# Execution Policies

**Updated on Sunday, November 20, 2022**

An execution policy is a policy defined in the Project
configuration file (`project.json`) that can be used
to execute a script or program in any stage of the package

For instance, you can have a script that is executed before
the build process starts, or in different installation stages
when the user is installing your package you can have a unit
run before or after the installation/uninstallation process
starts.#

Use cases such as this allows you to properly implement
and control your program's files & assets that are not
handled by NCC's compiler extensions.

## Table of Contents

<!-- TOC -->
* [Execution Policies](#execution-policies)
  * [Table of Contents](#table-of-contents)
  * [JSON Example](#json-example)
  * [ExecutionPolicy Object](#executionpolicy-object)
    * [Object Properties](#object-properties)
    * [JSON Example](#json-example)
  * [ExecutionConfiguration Object](#executionconfiguration-object)
    * [Object Properties](#object-properties)
    * [JSON Example](#json-example)
  * [ExitHandler Object](#exithandler-object)
    * [Object Properties](#object-properties)
    * [JSON Example](#json-example)
<!-- TOC -->


## JSON Example

```json
{
  "execution_policies": {
    "main": {
      "runner": "php",
      "message": "Running main %ASSEMBLY.PACKAGE%",
      "exec": {
        "target": "scripts/main.php",
        "working_directory": "%INSTALL_PATH.SRC%",
        "silent": false
      }
    },
    "hello_world": {
      "runner": "shell",
      "message": "Running HTOP",
      "options": {
        "htop": null
      },
      "exec": {
        "tty": true
      }
    }
  }
}
```

------------------------------------------------------------

## ExecutionPolicy Object

Execution Policies for your project **must** have unique
names, because they way you tell NCC to execute these
policies is by referencing their name in the configuration.

Invalid names/undefined policies will raise errors when
building the project

### Object Properties

| Property Name   | Value Type                      | Example Value        | Description                                                                                |
|-----------------|---------------------------------|----------------------|--------------------------------------------------------------------------------------------|
| `runner`        | string                          | bash                 | The name of a supported runner instance, see runners in this document                      |
| `message`       | string, null                    | Starting foo_bar ... | *Optional* the message to display before running the execution policy                      |
| `exec`          | ExecutionConfiguration          | N/A                  | The configuration object that tells how the runner should execute the process              |
| `exit_handlers` | ExitHandlersConfiguration, null | N/A                  | *Optional* Exit Handler Configurations that tells NCC how to handle exits from the process |

### JSON Example

```json
{
  "name": "foo_bar",
  "runner": "bash",
  "message": "Running foo_bar ...",
  "exec": null,
  "exit_handlers": null
}
```

------------------------------------------------------------

## ExecutionConfiguration Object

### Object Properties

| Property Name       | Value Type        | Example Value                   | Description                                                            |
|---------------------|-------------------|---------------------------------|------------------------------------------------------------------------|
| `target`            | `string`          | scripts/foo_bar.bash            | The target file to execute                                             |
| `working_directory` | `string`, `null`  | %INSTALL_PATH.SRC%              | *optional* The working directory to execute the process in             |
| `options`           | `array`, `null`   | {"run": null, "log": "verbose"} | Commandline Parameters to pass on to the target or process             |
| `silent`            | `boolean`, `null` | False                           | Indicates if the target should run silently, by default this is false. |
| `tty`               | `boolean`, `null` | False                           | Indicates if the target should run in TTY mode                         |
| `timeout`           | `integer`, `null` | 60                              | The amount of seconds to wait before the process is killed             |

### JSON Example

```json
{
  "target": "scripts/foo_bar.bash",
  "working_directory": "%INSTALL_PATH.SRC%",
  "options": {"run": null, "log": "verbose"},
  "silent": false,
  "tty": false,
  "timeout": 10
}
```


------------------------------------------------------------

## ExitHandler Object

An exit handler is executed once the specified exit code is
returned or the process exits with an error or normally, if
an exit handler is specified it will be executed.

### Object Properties

| Property Name | Value Type         | Example Value | Description                                                                  |
|---------------|--------------------|---------------|------------------------------------------------------------------------------|
| `message`     | `string`           | Hello World!  | The message to display when the exit handler is triggered                    |
| `end_process` | `boolean`, `null`  | False         | *optional* Kills the process after this exit handler is triggered            |
| `run`         | `string`, `null`   | `null`        | *optional* A execution policy to execute once this exit handler is triggered |
| `exit_code`   | `int`, `null`      | 1             | The exit code that triggers this exit handler                                |
### JSON Example

```json
{
  "message": "Hello World",
  "end_process": false,
  "run": null,
  "exit_code": 1
}
```