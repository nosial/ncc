<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'ncc\\abstracts\\authenticationsource' => '/Abstracts/AuthenticationSource.php',
                'ncc\\abstracts\\exceptioncodes' => '/Abstracts/ExceptionCodes.php',
                'ncc\\abstracts\\nccbuildflags' => '/Abstracts/NccBuildFlags.php',
                'ncc\\abstracts\\regexpatterns' => '/Abstracts/RegexPatterns.php',
                'ncc\\abstracts\\scopes' => '/Abstracts/Scopes.php',
                'ncc\\abstracts\\stringpaddingmethod' => '/Abstracts/StringPaddingMethod.php',
                'ncc\\assert\\assert' => '/ThirdParty/beberlei/assert/Assert.php',
                'ncc\\assert\\assertion' => '/ThirdParty/beberlei/assert/Assertion.php',
                'ncc\\assert\\assertionchain' => '/ThirdParty/beberlei/assert/AssertionChain.php',
                'ncc\\assert\\assertionfailedexception' => '/ThirdParty/beberlei/assert/AssertionFailedException.php',
                'ncc\\assert\\invalidargumentexception' => '/ThirdParty/beberlei/assert/InvalidArgumentException.php',
                'ncc\\assert\\lazyassertion' => '/ThirdParty/beberlei/assert/LazyAssertion.php',
                'ncc\\assert\\lazyassertionexception' => '/ThirdParty/beberlei/assert/LazyAssertionException.php',
                'ncc\\cli\\functions' => '/CLI/Functions.php',
                'ncc\\cli\\helpmenu' => '/CLI/HelpMenu.php',
                'ncc\\cli\\main' => '/CLI/Main.php',
                'ncc\\exceptions\\accessdeniedexception' => '/Exceptions/AccessDeniedException.php',
                'ncc\\exceptions\\directorynotfoundexception' => '/Exceptions/DirectoryNotFoundException.php',
                'ncc\\exceptions\\filenotfoundexception' => '/Exceptions/FileNotFoundException.php',
                'ncc\\exceptions\\invalidprojectconfigurationexception' => '/Exceptions/InvalidProjectConfigurationException.php',
                'ncc\\exceptions\\invalidscopeexception' => '/Exceptions/InvalidScopeException.php',
                'ncc\\exceptions\\malformedjsonexception' => '/Exceptions/MalformedJsonException.php',
                'ncc\\exceptions\\runtimeexception' => '/Exceptions/RuntimeException.php',
                'ncc\\ncc' => '/ncc.php',
                'ncc\\ncc\\ziproto\\typetransformer\\binarytransformer' => '/Extensions/ZiProto/TypeTransformer/BinaryTransformer.php',
                'ncc\\ncc\\ziproto\\typetransformer\\extension' => '/Extensions/ZiProto/TypeTransformer/Extension.php',
                'ncc\\ncc\\ziproto\\typetransformer\\validator' => '/Extensions/ZiProto/TypeTransformer/Validator.php',
                'ncc\\objects\\clihelpsection' => '/Objects/CliHelpSection.php',
                'ncc\\objects\\nccupdateinformation' => '/Objects/NccUpdateInformation.php',
                'ncc\\objects\\nccversioninformation' => '/Objects/NccVersionInformation.php',
                'ncc\\objects\\projectconfiguration' => '/Objects/ProjectConfiguration.php',
                'ncc\\objects\\projectconfiguration\\assembly' => '/Objects/ProjectConfiguration/Assembly.php',
                'ncc\\objects\\projectconfiguration\\build' => '/Objects/ProjectConfiguration/Build.php',
                'ncc\\objects\\projectconfiguration\\buildconfiguration' => '/Objects/ProjectConfiguration/BuildConfiguration.php',
                'ncc\\objects\\projectconfiguration\\compiler' => '/Objects/ProjectConfiguration/Compiler.php',
                'ncc\\objects\\projectconfiguration\\dependency' => '/Objects/ProjectConfiguration/Dependency.php',
                'ncc\\objects\\projectconfiguration\\project' => '/Objects/ProjectConfiguration/Project.php',
                'ncc\\phpschool\\climenu\\action\\exitaction' => '/ThirdParty/php-school/cli-menu/Action/ExitAction.php',
                'ncc\\phpschool\\climenu\\action\\gobackaction' => '/ThirdParty/php-school/cli-menu/Action/GoBackAction.php',
                'ncc\\phpschool\\climenu\\builder\\climenubuilder' => '/ThirdParty/php-school/cli-menu/Builder/CliMenuBuilder.php',
                'ncc\\phpschool\\climenu\\builder\\splititembuilder' => '/ThirdParty/php-school/cli-menu/Builder/SplitItemBuilder.php',
                'ncc\\phpschool\\climenu\\climenu' => '/ThirdParty/php-school/cli-menu/CliMenu.php',
                'ncc\\phpschool\\climenu\\dialogue\\cancellableconfirm' => '/ThirdParty/php-school/cli-menu/Dialogue/CancellableConfirm.php',
                'ncc\\phpschool\\climenu\\dialogue\\confirm' => '/ThirdParty/php-school/cli-menu/Dialogue/Confirm.php',
                'ncc\\phpschool\\climenu\\dialogue\\dialogue' => '/ThirdParty/php-school/cli-menu/Dialogue/Dialogue.php',
                'ncc\\phpschool\\climenu\\dialogue\\flash' => '/ThirdParty/php-school/cli-menu/Dialogue/Flash.php',
                'ncc\\phpschool\\climenu\\exception\\cannotshrinkmenuexception' => '/ThirdParty/php-school/cli-menu/Exception/CannotShrinkMenuException.php',
                'ncc\\phpschool\\climenu\\exception\\invalidshortcutexception' => '/ThirdParty/php-school/cli-menu/Exception/InvalidShortcutException.php',
                'ncc\\phpschool\\climenu\\exception\\invalidterminalexception' => '/ThirdParty/php-school/cli-menu/Exception/InvalidTerminalException.php',
                'ncc\\phpschool\\climenu\\exception\\menunotopenexception' => '/ThirdParty/php-school/cli-menu/Exception/MenuNotOpenException.php',
                'ncc\\phpschool\\climenu\\frame' => '/ThirdParty/php-school/cli-menu/Frame.php',
                'ncc\\phpschool\\climenu\\input\\input' => '/ThirdParty/php-school/cli-menu/Input/Input.php',
                'ncc\\phpschool\\climenu\\input\\inputio' => '/ThirdParty/php-school/cli-menu/Input/InputIO.php',
                'ncc\\phpschool\\climenu\\input\\inputresult' => '/ThirdParty/php-school/cli-menu/Input/InputResult.php',
                'ncc\\phpschool\\climenu\\input\\number' => '/ThirdParty/php-school/cli-menu/Input/Number.php',
                'ncc\\phpschool\\climenu\\input\\password' => '/ThirdParty/php-school/cli-menu/Input/Password.php',
                'ncc\\phpschool\\climenu\\input\\text' => '/ThirdParty/php-school/cli-menu/Input/Text.php',
                'ncc\\phpschool\\climenu\\menuitem\\asciiartitem' => '/ThirdParty/php-school/cli-menu/MenuItem/AsciiArtItem.php',
                'ncc\\phpschool\\climenu\\menuitem\\checkboxitem' => '/ThirdParty/php-school/cli-menu/MenuItem/CheckboxItem.php',
                'ncc\\phpschool\\climenu\\menuitem\\linebreakitem' => '/ThirdParty/php-school/cli-menu/MenuItem/LineBreakItem.php',
                'ncc\\phpschool\\climenu\\menuitem\\menuiteminterface' => '/ThirdParty/php-school/cli-menu/MenuItem/MenuItemInterface.php',
                'ncc\\phpschool\\climenu\\menuitem\\menumenuitem' => '/ThirdParty/php-school/cli-menu/MenuItem/MenuMenuItem.php',
                'ncc\\phpschool\\climenu\\menuitem\\propagatesstyles' => '/ThirdParty/php-school/cli-menu/MenuItem/PropagatesStyles.php',
                'ncc\\phpschool\\climenu\\menuitem\\radioitem' => '/ThirdParty/php-school/cli-menu/MenuItem/RadioItem.php',
                'ncc\\phpschool\\climenu\\menuitem\\selectableitem' => '/ThirdParty/php-school/cli-menu/MenuItem/SelectableItem.php',
                'ncc\\phpschool\\climenu\\menuitem\\selectableitemrenderer' => '/ThirdParty/php-school/cli-menu/MenuItem/SelectableItemRenderer.php',
                'ncc\\phpschool\\climenu\\menuitem\\splititem' => '/ThirdParty/php-school/cli-menu/MenuItem/SplitItem.php',
                'ncc\\phpschool\\climenu\\menuitem\\staticitem' => '/ThirdParty/php-school/cli-menu/MenuItem/StaticItem.php',
                'ncc\\phpschool\\climenu\\menustyle' => '/ThirdParty/php-school/cli-menu/MenuStyle.php',
                'ncc\\phpschool\\climenu\\style\\checkboxstyle' => '/ThirdParty/php-school/cli-menu/Style/CheckboxStyle.php',
                'ncc\\phpschool\\climenu\\style\\defaultstyle' => '/ThirdParty/php-school/cli-menu/Style/DefaultStyle.php',
                'ncc\\phpschool\\climenu\\style\\exception\\invalidstyle' => '/ThirdParty/php-school/cli-menu/Style/Exception/InvalidStyle.php',
                'ncc\\phpschool\\climenu\\style\\itemstyle' => '/ThirdParty/php-school/cli-menu/Style/ItemStyle.php',
                'ncc\\phpschool\\climenu\\style\\locator' => '/ThirdParty/php-school/cli-menu/Style/Locator.php',
                'ncc\\phpschool\\climenu\\style\\radiostyle' => '/ThirdParty/php-school/cli-menu/Style/RadioStyle.php',
                'ncc\\phpschool\\climenu\\style\\selectablestyle' => '/ThirdParty/php-school/cli-menu/Style/SelectableStyle.php',
                'ncc\\phpschool\\climenu\\terminal\\terminalfactory' => '/ThirdParty/php-school/cli-menu/Terminal/TerminalFactory.php',
                'ncc\\phpschool\\climenu\\util\\collection' => '/ThirdParty/php-school/cli-menu/Util/Collection.php',
                'ncc\\phpschool\\climenu\\util\\colourutil' => '/ThirdParty/php-school/cli-menu/Util/ColourUtil.php',
                'ncc\\phpschool\\climenu\\util\\stringutil' => '/ThirdParty/php-school/cli-menu/Util/StringUtil.php',
                'ncc\\phpschool\\terminal\\exception\\notinteractiveterminal' => '/ThirdParty/php-school/terminal/Exception/NotInteractiveTerminal.php',
                'ncc\\phpschool\\terminal\\inputcharacter' => '/ThirdParty/php-school/terminal/InputCharacter.php',
                'ncc\\phpschool\\terminal\\io\\bufferedoutput' => '/ThirdParty/php-school/terminal/IO/BufferedOutput.php',
                'ncc\\phpschool\\terminal\\io\\inputstream' => '/ThirdParty/php-school/terminal/IO/InputStream.php',
                'ncc\\phpschool\\terminal\\io\\outputstream' => '/ThirdParty/php-school/terminal/IO/OutputStream.php',
                'ncc\\phpschool\\terminal\\io\\resourceinputstream' => '/ThirdParty/php-school/terminal/IO/ResourceInputStream.php',
                'ncc\\phpschool\\terminal\\io\\resourceoutputstream' => '/ThirdParty/php-school/terminal/IO/ResourceOutputStream.php',
                'ncc\\phpschool\\terminal\\noncanonicalreader' => '/ThirdParty/php-school/terminal/NonCanonicalReader.php',
                'ncc\\phpschool\\terminal\\terminal' => '/ThirdParty/php-school/terminal/Terminal.php',
                'ncc\\phpschool\\terminal\\unixterminal' => '/ThirdParty/php-school/terminal/UnixTerminal.php',
                'ncc\\symfony\\component\\process\\exception\\exceptioninterface' => '/ThirdParty/Symfony/Process/Exception/ExceptionInterface.php',
                'ncc\\symfony\\component\\process\\exception\\invalidargumentexception' => '/ThirdParty/Symfony/Process/Exception/InvalidArgumentException.php',
                'ncc\\symfony\\component\\process\\exception\\logicexception' => '/ThirdParty/Symfony/Process/Exception/LogicException.php',
                'ncc\\symfony\\component\\process\\exception\\processfailedexception' => '/ThirdParty/Symfony/Process/Exception/ProcessFailedException.php',
                'ncc\\symfony\\component\\process\\exception\\processsignaledexception' => '/ThirdParty/Symfony/Process/Exception/ProcessSignaledException.php',
                'ncc\\symfony\\component\\process\\exception\\processtimedoutexception' => '/ThirdParty/Symfony/Process/Exception/ProcessTimedOutException.php',
                'ncc\\symfony\\component\\process\\exception\\runtimeexception' => '/ThirdParty/Symfony/Process/Exception/RuntimeException.php',
                'ncc\\symfony\\component\\process\\executablefinder' => '/ThirdParty/Symfony/Process/ExecutableFinder.php',
                'ncc\\symfony\\component\\process\\inputstream' => '/ThirdParty/Symfony/Process/InputStream.php',
                'ncc\\symfony\\component\\process\\phpexecutablefinder' => '/ThirdParty/Symfony/Process/PhpExecutableFinder.php',
                'ncc\\symfony\\component\\process\\phpprocess' => '/ThirdParty/Symfony/Process/PhpProcess.php',
                'ncc\\symfony\\component\\process\\pipes\\abstractpipes' => '/ThirdParty/Symfony/Process/Pipes/AbstractPipes.php',
                'ncc\\symfony\\component\\process\\pipes\\pipesinterface' => '/ThirdParty/Symfony/Process/Pipes/PipesInterface.php',
                'ncc\\symfony\\component\\process\\pipes\\unixpipes' => '/ThirdParty/Symfony/Process/Pipes/UnixPipes.php',
                'ncc\\symfony\\component\\process\\pipes\\windowspipes' => '/ThirdParty/Symfony/Process/Pipes/WindowsPipes.php',
                'ncc\\symfony\\component\\process\\process' => '/ThirdParty/Symfony/Process/Process.php',
                'ncc\\symfony\\component\\process\\processutils' => '/ThirdParty/Symfony/Process/ProcessUtils.php',
                'ncc\\utilities\\functions' => '/Utilities/Functions.php',
                'ncc\\utilities\\pathfinder' => '/Utilities/PathFinder.php',
                'ncc\\utilities\\resolver' => '/Utilities/Resolver.php',
                'ncc\\utilities\\security' => '/Utilities/Security.php',
                'ncc\\utilities\\validate' => '/Utilities/Validate.php',
                'ncc\\ziproto\\abstracts\\options' => '/Extensions/ZiProto/Abstracts/Options.php',
                'ncc\\ziproto\\abstracts\\regex' => '/Extensions/ZiProto/Abstracts/Regex.php',
                'ncc\\ziproto\\bufferstream' => '/Extensions/ZiProto/BufferStream.php',
                'ncc\\ziproto\\decodingoptions' => '/Extensions/ZiProto/DecodingOptions.php',
                'ncc\\ziproto\\encodingoptions' => '/Extensions/ZiProto/EncodingOptions.php',
                'ncc\\ziproto\\exception\\decodingfailedexception' => '/Extensions/ZiProto/Exception/DecodingFailedException.php',
                'ncc\\ziproto\\exception\\encodingfailedexception' => '/Extensions/ZiProto/Exception/EncodingFailedException.php',
                'ncc\\ziproto\\exception\\insufficientdataexception' => '/Extensions/ZiProto/Exception/InsufficientDataException.php',
                'ncc\\ziproto\\exception\\integeroverflowexception' => '/Extensions/ZiProto/Exception/IntegerOverflowException.php',
                'ncc\\ziproto\\exception\\invalidoptionexception' => '/Extensions/ZiProto/Exception/InvalidOptionException.php',
                'ncc\\ziproto\\ext' => '/Extensions/ZiProto/Ext.php',
                'ncc\\ziproto\\packet' => '/Extensions/ZiProto/Packet.php',
                'ncc\\ziproto\\type\\binary' => '/Extensions/ZiProto/Type/Binary.php',
                'ncc\\ziproto\\type\\map' => '/Extensions/ZiProto/Type/Map.php',
                'ncc\\ziproto\\typetransformer\\maptransformer' => '/Extensions/ZiProto/TypeTransformer/MapTransformer.php',
                'ncc\\ziproto\\ziproto' => '/Extensions/ZiProto/ZiProto.php'
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
