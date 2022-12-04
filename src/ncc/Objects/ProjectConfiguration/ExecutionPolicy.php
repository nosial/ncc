<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Objects\InstallationPaths;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy\Execute;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy\ExitHandlers;
    use ncc\Utilities\Functions;

    class ExecutionPolicy
    {
        /**
         * The unique name of the execution policy
         *
         * @var string
         */
        public $Name;

        /**
         * The name of a supported runner instance
         *
         * @var string
         */
        public $Runner;

        /**
         * The message to display when the policy is invoked
         *
         * @var string|null
         */
        public $Message;

        /**
         * The execution process of the policy
         *
         * @var Execute
         */
        public $Execute;

        /**
         * The configuration for exit handling
         *
         * @var ExitHandlers
         */
        public $ExitHandlers;

        /**
         * @param bool $throw_exception
         * @return bool
         */
        public function validate(bool $throw_exception=True): bool
        {
            // TODO: Implement validation method
            return true;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if ($this->Name !== null && strlen($this->Name) > 0)
                $results[($bytecode ? Functions::cbc('name') : 'name')] = $this->Name;

            if ($this->Runner !== null && strlen($this->Runner) > 0)
                $results[($bytecode ? Functions::cbc('runner') : 'runner')] = $this->Runner;

            if ($this->Message !== null && strlen($this->Message) > 0)
                $results[($bytecode ? Functions::cbc('message') : 'message')] = $this->Message;

            if ($this->Execute !== null)
                $results[($bytecode ? Functions::cbc('execute') : 'execute')] = $this->Execute->toArray($bytecode);

            if ($this->ExitHandlers !== null)
                $results[($bytecode ? Functions::cbc('exit_handlers') : 'exit_handlers')] = $this->ExitHandlers->toArray($bytecode);

            return $results;
        }

        /**
         * @param array $data
         * @return ExecutionPolicy
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Name = Functions::array_bc($data, 'name');
            $object->Runner = Functions::array_bc($data, 'runner');
            $object->Message = Functions::array_bc($data, 'message');
            $object->Execute = Functions::array_bc($data, 'exec');
            $object->ExitHandlers = Functions::array_bc($data, 'exit_handlers');

            if($object->Execute !== null)
                $object->Execute = Execute::fromArray($object->Execute);

            if($object->ExitHandlers !== null)
                $object->ExitHandlers = ExitHandlers::fromArray($object->ExitHandlers);

            return $object;
        }
    }