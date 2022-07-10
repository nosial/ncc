<?php

    namespace ncc\Objects\NccVersionInformation;

    use ncc\Exceptions\ComponentVersionNotFoundException;

    class Component
    {
        /**
         * The Vendor of the component
         *
         * @var string
         */
        public $Vendor;

        /**
         * The name of the package
         *
         * @var string
         */
        public $PackageName;

        /**
         * Attempts to resolve the component's build version
         *
         * @param string $ncc_installation_path
         * @return string
         * @throws ComponentVersionNotFoundException
         */
        public function getVersion(string $ncc_installation_path): string
        {
            // Auto-resolve the trailing slash
            if(substr($ncc_installation_path, -1) !== '/')
            {
                $ncc_installation_path .= $ncc_installation_path . DIRECTORY_SEPARATOR;
            }

            $third_party_path = $ncc_installation_path . DIRECTORY_SEPARATOR . 'ThirdParty' . DIRECTORY_SEPARATOR;
            $component_path = $third_party_path . $this->Vendor . DIRECTORY_SEPARATOR . $this->PackageName . DIRECTORY_SEPARATOR;

            if(file_exists($component_path . 'VERSION') == false)
            {
                throw new ComponentVersionNotFoundException('The file \'' . $component_path . 'VERSION' . '\' does not exist');
            }

            return file_get_contents($component_path . 'VERSION');
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'vendor' => $this->Vendor,
                'package_name' => $this->PackageName
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Component
         */
        public static function fromArray(array $data): Component
        {
            $ComponentObject = new Component();

            if(isset($data['vendor']))
                $ComponentObject->Vendor = $data['vendor'];

            if(isset($data['package_name']))
                $ComponentObject->PackageName = $data['package_name'];

            return $ComponentObject;
        }
    }