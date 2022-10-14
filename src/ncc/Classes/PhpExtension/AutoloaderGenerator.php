<?php

    namespace ncc\Classes\PhpExtension;

    use ArrayIterator;
    use ncc\Exceptions\AutoloadGeneratorException;
    use ncc\Exceptions\NoUnitsFoundException;
    use ncc\Objects\ProjectConfiguration;
    use ncc\ThirdParty\theseer\Autoload\CollectorException;
    use ncc\ThirdParty\theseer\Autoload\CollectorResult;
    use ncc\ThirdParty\theseer\Autoload\Config;
    use ncc\ThirdParty\theseer\Autoload\Factory;
    use ncc\ThirdParty\theseer\DirectoryScanner\Exception;
    use ncc\Utilities\Console;
    use SplFileInfo;

    class AutoloaderGenerator
    {
        /**
         * @var ProjectConfiguration
         */
        private ProjectConfiguration $project;

        /**
         * @param ProjectConfiguration $project
         */
        public function  __construct(ProjectConfiguration $project)
        {
            $this->project = $project;
        }

        /**
         * Processes the project and generates the autoloader source code.
         *
         * @param string $src
         * @param string $output
         * @param bool $static
         * @return string
         * @throws AutoloadGeneratorException
         * @throws CollectorException
         * @throws Exception
         * @throws NoUnitsFoundException
         */
        public function generateAutoload(string $src, string $output, bool $static=false): string
        {
            // Construct configuration
            $configuration = new Config([$src]);
            $configuration->setFollowSymlinks(false);
            $configuration->setOutputFile($output);
            $configuration->setTrusting(false); // Paranoid

            // Construct factory
            $factory = new Factory();
            $factory->setConfig($configuration);

            // Create Collector
            $result = self::runCollector($factory, $configuration);

            // Exception raises when there are no files in the project that can be processed by the autoloader
            if(!$result->hasUnits())
            {
                throw new NoUnitsFoundException('No units were found in the project');
            }

            if(!$result->hasDuplicates())
            {
                foreach($result->getDuplicates() as $unit => $files)
                {
                    Console::outWarning((count($files) -1). ' duplicate unit(s) detected in the project: ' . $unit);
                }
            }

            $template = @file_get_contents($configuration->getTemplate());
            if ($template === false)
            {
                throw new AutoloadGeneratorException("Failed to read the template file '" . $configuration->getTemplate() . "'");
            }

            $builder = $factory->getRenderer($result);
            return $builder->render($template);
        }

        /**
         * Iterates through the target directories through the collector and returns the collector results.
         *
         * @param Factory $factory
         * @param Config $config
         * @return CollectorResult
         * @throws CollectorException
         * @throws Exception
         */
        private static function runCollector(Factory $factory, Config $config): CollectorResult
        {
            $collector = $factory->getCollector();
            foreach($config->getDirectories() as $directory)
            {
                if(is_dir($directory))
                {
                    $scanner = $factory->getScanner()->getIterator($directory);
                    $collector->processDirectory($scanner);
                    unset($scanner);
                }
                else
                {
                    $file = new SplFileInfo($directory);
                    $filter = $factory->getFilter(new ArrayIterator(array($file)));
                    foreach($filter as $file)
                    {
                        $collector->processFile($file);
                    }
                }
            }

            return $collector->getResult();
        }

    }