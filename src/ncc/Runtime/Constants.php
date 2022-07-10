<?php

    namespace ncc\Runtime;

    use ncc\Exceptions\ConstantReadonlyException;
    use ncc\Objects\Constant;
    use ncc\Utilities\Resolver;

    class Constants
    {
        /**
         * The registered constants in memory
         *
         * @var Constant[]
         */
        private static $Constants;

        /**
         * Registers a new constant
         *
         * @param string $scope The package name that owns this constant
         * @param string $name The name of the constant
         * @param string $value The value of the constant
         * @param bool $readonly Indicates if the constant cannot be changed with the registerConstant function once it's registered
         * @return void
         * @throws ConstantReadonlyException
         */
        public static function register(string $scope, string $name, string $value, bool $readonly=false)
        {
            // TODO: Add functionality to convert the constant name to be more memory-friendly with a size limit
            $constant_hash = Resolver::resolveConstantHash($scope, $name);

            if(isset(self::$Constants[$constant_hash]))
            {
                self::$Constants[$constant_hash]->setValue($value, $readonly);
                return;
            }

            self::$Constants[$constant_hash] = new Constant($scope, $name, $value, $readonly);
        }

        /**
         * Deletes the constant
         *
         * @param string $scope
         * @param string $name
         * @return void
         * @throws ConstantReadonlyException
         */
        public static function delete(string $scope, string $name)
        {
            $constant_hash = Resolver::resolveConstantHash($scope, $name);

            if(isset(self::$Constants[$constant_hash]) && self::$Constants[$constant_hash]->isReadonly())
            {
                throw new ConstantReadonlyException('Cannot delete the constant \'' .  self::$Constants[$constant_hash]->getFullName() .  '\', constant is readonly');
            }

            unset(self::$Constants[$constant_hash]);
        }
    }