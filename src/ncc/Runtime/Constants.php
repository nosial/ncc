<?php
/*
 * Copyright (c) Nosial 2022-2023, all rights reserved.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
 *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
 *  of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

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

        /**
         * Gets the constant
         *
         * @param string $scope
         * @param string $name
         * @return string|null
         */
        public static function get(string $scope, string $name): ?string
        {
            if(!Validate::constantName($name))
                return null;

            $constant_hash = Resolver::resolveConstantHash($scope, $name);

            if(isset(self::$Constants[$constant_hash]))
                return self::$Constants[$constant_hash]->getValue();

            return null;
        }
    }