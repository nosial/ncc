<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'ncc\\abstracts\\exceptioncodes' => '/Abstracts/ExceptionCodes.php',
                'ncc\\abstracts\\regexpatterns' => '/Abstracts/RegexPatterns.php',
                'ncc\\exceptions\\invalidprojectconfigurationexception' => '/Exceptions/InvalidProjectConfigurationException.php',
                'ncc\\ncc' => '/ncc.php',
                'ncc\\objects\\projectconfiguration' => '/Objects/ProjectConfiguration.php',
                'ncc\\objects\\projectconfiguration\\assembly' => '/Objects/ProjectConfiguration/Assembly.php',
                'ncc\\objects\\projectconfiguration\\build' => '/Objects/ProjectConfiguration/Build.php',
                'ncc\\objects\\projectconfiguration\\buildconfiguration' => '/Objects/ProjectConfiguration/BuildConfiguration.php',
                'ncc\\objects\\projectconfiguration\\compiler' => '/Objects/ProjectConfiguration/Compiler.php',
                'ncc\\objects\\projectconfiguration\\dependency' => '/Objects/ProjectConfiguration/Dependency.php',
                'ncc\\objects\\projectconfiguration\\project' => '/Objects/ProjectConfiguration/Project.php',
                'ncc\\utilities\\functions' => '/Utilities/Functions.php',
                'ncc\\utilities\\validate' => '/Utilities/Validate.php',
                'symfony\\component\\nccprocess\\exception\\exceptioninterface' => '/ThirdParty/Symfony/Process/Exception/ExceptionInterface.php',
                'symfony\\component\\nccprocess\\exception\\invalidargumentexception' => '/ThirdParty/Symfony/Process/Exception/InvalidArgumentException.php',
                'symfony\\component\\nccprocess\\exception\\logicexception' => '/ThirdParty/Symfony/Process/Exception/LogicException.php',
                'symfony\\component\\nccprocess\\exception\\processfailedexception' => '/ThirdParty/Symfony/Process/Exception/ProcessFailedException.php',
                'symfony\\component\\nccprocess\\exception\\processsignaledexception' => '/ThirdParty/Symfony/Process/Exception/ProcessSignaledException.php',
                'symfony\\component\\nccprocess\\exception\\processtimedoutexception' => '/ThirdParty/Symfony/Process/Exception/ProcessTimedOutException.php',
                'symfony\\component\\nccprocess\\exception\\runtimeexception' => '/ThirdParty/Symfony/Process/Exception/RuntimeException.php',
                'symfony\\component\\nccprocess\\executablefinder' => '/ThirdParty/Symfony/Process/ExecutableFinder.php',
                'symfony\\component\\nccprocess\\inputstream' => '/ThirdParty/Symfony/Process/InputStream.php',
                'symfony\\component\\nccprocess\\phpexecutablefinder' => '/ThirdParty/Symfony/Process/PhpExecutableFinder.php',
                'symfony\\component\\nccprocess\\phpprocess' => '/ThirdParty/Symfony/Process/PhpProcess.php',
                'symfony\\component\\nccprocess\\pipes\\abstractpipes' => '/ThirdParty/Symfony/Process/Pipes/AbstractPipes.php',
                'symfony\\component\\nccprocess\\pipes\\pipesinterface' => '/ThirdParty/Symfony/Process/Pipes/PipesInterface.php',
                'symfony\\component\\nccprocess\\pipes\\unixpipes' => '/ThirdParty/Symfony/Process/Pipes/UnixPipes.php',
                'symfony\\component\\nccprocess\\pipes\\windowspipes' => '/ThirdParty/Symfony/Process/Pipes/WindowsPipes.php',
                'symfony\\component\\nccprocess\\process' => '/ThirdParty/Symfony/Process/Process.php',
                'symfony\\component\\nccprocess\\processutils' => '/ThirdParty/Symfony/Process/ProcessUtils.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    },
    true,
    false
);
// @codeCoverageIgnoreEnd
