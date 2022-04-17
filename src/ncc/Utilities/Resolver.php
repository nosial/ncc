<?php

    namespace ncc\Utilities;

    use ncc\Abstracts\Scopes;

    class Resolver
    {
        /**
         * @param string|null $input
         * @return string
         */
        public static function resolveScope(?string $input=null): string
        {
            // Set the scope to automatic if it's null
            if($input == null)
            {
                $input = Scopes::Auto;
            }

            $input = strtoupper($input);

            // Resolve the scope if it's set to automatic
            if($input == Scopes::Auto)
            {
                if(posix_getuid() == 0)
                {
                    $input = Scopes::System;
                }
                else
                {
                    $input = Scopes::User;
                }
            }

            // Auto-Correct the scope if the current user ID is 0
            if($input == Scopes::User && posix_getuid() == 0)
            {
                $input = Scopes::System;
            }

            return $input;
        }
    }