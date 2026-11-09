<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Classes;

    class PhpClassParser
    {
        /**
         * Parses PHP source code to extract class, interface, trait, and enum names.
         *
         * @param string $phpCode The PHP source code to parse
         * @return array<string> Array of fully qualified class names found in the code
         */
        public static function parse(string $phpCode): array
        {
            $tokens = @token_get_all($phpCode);
            if(!is_array($tokens))
            {
                return [];
            }
            
            $classes = [];
            $namespace = '';
            $tokenCount = count($tokens);
            $bracketLevel = 0;
            $namespaceBracketLevel = 0;
            
            for($i = 0; $i < $tokenCount; $i++)
            {
                $token = $tokens[$i];
                
                if(!is_array($token))
                {
                    if($token === '{')
                    {
                        $bracketLevel++;
                    }
                    elseif($token === '}')
                    {
                        $bracketLevel--;
                        if($namespaceBracketLevel > 0 && $bracketLevel < $namespaceBracketLevel)
                        {
                            $namespace = '';
                            $namespaceBracketLevel = 0;
                        }
                    }
                    continue;
                }
                
                $tokenType = $token[0];
                
                // Handle namespace declarations
                if($tokenType === T_NAMESPACE)
                {
                    $namespace = self::extractNamespaceFromTokens($tokens, $i);
                    
                    // Check if this is a bracketed namespace
                    $j = $i + 1;
                    while($j < $tokenCount)
                    {
                        if(!is_array($tokens[$j]))
                        {
                            if($tokens[$j] === '{')
                            {
                                $namespaceBracketLevel = $bracketLevel + 1;
                            }
                            break;
                        }
                        $j++;
                    }
                    continue;
                }
                
                // Handle class/interface/trait/enum definitions
                $allowedTypes = [T_CLASS, T_INTERFACE];
                
                if(defined('T_TRAIT'))
                {
                    $allowedTypes[] = T_TRAIT;
                }
                
                if(defined('T_ENUM'))
                {
                    $allowedTypes[] = T_ENUM;
                }
                
                if(in_array($tokenType, $allowedTypes))
                {
                    // Skip anonymous classes
                    if(self::isAnonymousClass($tokens, $i))
                    {
                        continue;
                    }
                    
                    // Skip ::class constants
                    if(self::isClassConstant($tokens, $i))
                    {
                        continue;
                    }
                    
                    $className = self::extractClassNameFromTokens($tokens, $i);
                    if($className)
                    {
                        $classes[] = $namespace ? $namespace . '\\' . $className : $className;
                    }
                }
            }
            
            return array_unique($classes);
        }

        /**
         * Extracts namespace from tokens starting at a T_NAMESPACE token.
         *
         * @param array $tokens Token array
         * @param int $startPos Position of T_NAMESPACE token
         * @return string The namespace string
         */
        private static function extractNamespaceFromTokens(array $tokens, int $startPos): string
        {
            $namespace = '';
            $i = $startPos + 1;
            
            while($i < count($tokens))
            {
                $token = $tokens[$i];
                
                if(!is_array($token))
                {
                    if($token === ';' || $token === '{')
                    {
                        break;
                    }
                    $i++;
                    continue;
                }
                
                if($token[0] === T_STRING || $token[0] === T_NS_SEPARATOR)
                {
                    $namespace .= $token[1];
                }
                elseif(defined('T_NAME_QUALIFIED') && $token[0] === T_NAME_QUALIFIED)
                {
                    $namespace .= $token[1];
                }
                elseif(defined('T_NAME_FULLY_QUALIFIED') && $token[0] === T_NAME_FULLY_QUALIFIED)
                {
                    $namespace .= $token[1];
                }
                
                $i++;
            }
            
            return trim($namespace, '\\');
        }

        /**
         * Extracts class name from tokens starting at a class/interface/trait/enum token.
         *
         * @param array $tokens Token array
         * @param int $startPos Position of class-like token
         * @return string|null The class name or null if not found
         */
        private static function extractClassNameFromTokens(array $tokens, int $startPos): ?string
        {
            $i = $startPos + 1;
            
            while($i < count($tokens))
            {
                $token = $tokens[$i];
                
                if(is_array($token) && $token[0] === T_STRING)
                {
                    return $token[1];
                }
                
                $i++;
            }
            
            return null;
        }

        /**
         * Checks if a class token represents an anonymous class.
         *
         * @param array $tokens Token array
         * @param int $pos Position of T_CLASS token
         * @return bool True if anonymous class
         */
        private static function isAnonymousClass(array $tokens, int $pos): bool
        {
            $i = $pos + 1;
            while($i < count($tokens))
            {
                $token = $tokens[$i];
                
                if(!is_array($token))
                {
                    if($token === '(' || $token === '{')
                    {
                        return true;
                    }
                    $i++;
                    continue;
                }
                
                if($token[0] === T_WHITESPACE)
                {
                    $i++;
                    continue;
                }
                
                if($token[0] === T_STRING)
                {
                    return false;
                }
                
                break;
            }
            
            return false;
        }

        /**
         * Checks if a class token is part of a ::class constant.
         *
         * @param array $tokens Token array
         * @param int $pos Position of T_CLASS token
         * @return bool True if ::class constant
         */
        private static function isClassConstant(array $tokens, int $pos): bool
        {
            $i = $pos - 1;
            while($i >= 0)
            {
                $token = $tokens[$i];
                
                if(is_array($token) && $token[0] === T_WHITESPACE)
                {
                    $i--;
                    continue;
                }
                
                if(defined('T_DOUBLE_COLON') && is_array($token) && $token[0] === T_DOUBLE_COLON)
                {
                    return true;
                }
                
                if(!is_array($token) && $token === ':')
                {
                    if($i > 0 && !is_array($tokens[$i - 1]) && $tokens[$i - 1] === ':')
                    {
                        return true;
                    }
                }
                
                break;
            }
            
            return false;
        }
    }
