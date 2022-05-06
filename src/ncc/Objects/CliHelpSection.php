<?php

    namespace ncc\Objects;

    class CliHelpSection
    {
        /**
         * An array of parameters that are accepted to invoke the option
         *
         * @var array|null
         */
        public $Parameters;

        /**
         * A description of the option
         *
         * @var string|null
         */
        public $Description;

        /**
         * The default value of the option
         *
         * @var string|null
         */
        public $Default;

        /**
         * Public Constructor
         *
         * @param array|null $parameters
         * @param string|null $description
         * @param string|null $default
         */
        public function __construct(?array $parameters=null, ?string $description=null, ?string $default=null)
        {
            $this->Parameters = $parameters;
            $this->Description = $description;
            $this->Default = $default;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'parameters' => $this->Parameters,
                'description' => $this->Description,
                'default' => $this->Default
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return CliHelpSection
         */
        public static function fromArray(array $data): CliHelpSection
        {
            $Object = new CliHelpSection();

            if(isset($data['parameters']))
                $Object->Parameters = $data['parameters'];

            if(isset($data['description']))
                $Object->Description = $data['description'];

            if(isset($data['default']))
                $Object->Default = $data['default'];

            return $Object;
        }

        /**
         * Returns a string representation of the object
         *
         * @return string
         */
        public function toString(int $param_padding=0)
        {
            $out = [];

            if(count($this->Parameters) > 0)
            {
                if($param_padding > 0)
                {
                    $out[] .= str_pad(implode(' ', $this->Parameters), $param_padding, ' ', STR_PAD_RIGHT);
                }
                else
                {
                    $out[] .= implode(' ', $this->Parameters);
                }
            }

            if($this->Description !== null)
            {
                $out[] = $this->Description;
            }

            if($this->Default !== null)
            {
                $out[] = '(Default: ' . $this->Default . ')';
            }

            return implode(' ', $out);
        }
    }