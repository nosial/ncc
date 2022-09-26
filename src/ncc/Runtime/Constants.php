<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Runtime;

    use ncc\Exceptions\ConstantReadonlyException;
    use ncc\Exceptions\InvalidConstantNameException;
    use ncc\Objects\Constant;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\Validate;

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
         * @throws InvalidConstantNameException
         */
        public static function register(string $scope, string $name, string $value, bool $readonly=false): void
        {
            if(!Validate::constantName($name))
                throw new InvalidConstantNameException('The name specified is not valid for a constant name');

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
        public static function delete(string $scope, string $name): void
        {
            if(!Validate::constantName($name))
                return;

            $constant_hash = Resolver::resolveConstantHash($scope, $name);

            if(isset(self::$Constants[$constant_hash]) && self::$Constants[$constant_hash]->isReadonly())
            {
                throw new ConstantReadonlyException('Cannot delete the constant \'' .  self::$Constants[$constant_hash]->getFullName() .  '\', constant is readonly');
            }

            unset(self::$Constants[$constant_hash]);
        }
    }