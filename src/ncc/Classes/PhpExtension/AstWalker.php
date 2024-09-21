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

    namespace ncc\Classes\PhpExtension;

    use ncc\ThirdParty\nikic\PhpParser\Comment;
    use ncc\ThirdParty\nikic\PhpParser\Node;
    use ncc\ThirdParty\nikic\PhpParser\NodeTraverser;
    use ReflectionClass;
    use ReflectionException;
    use RuntimeException;

    class AstWalker
    {
        /**
         * Returns an array of classes associated with the node recursively
         *
         * @param Node|array $node
         * @param string $prefix
         * @return array
         */
        public static function extractClasses(Node|array $node, string $prefix=''): array
        {
            $classes = [];

            if(is_array($node))
            {
                foreach($node as $sub_node)
                {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $classes = array_merge($classes, self::extractClasses($sub_node, $prefix));
                }
                return $classes;
            }

            if ($node instanceof Node\Stmt\ClassLike)
            {
                $classes[] = $prefix . $node->name;
            }

            if ($node instanceof Node\Stmt\Namespace_)
            {
                if ($node->name && $node->name->getParts())
                {
                    $prefix .= implode('\\', $node->name->getParts()) . '\\';
                }
                else
                {
                    $prefix = '';
                }
            }

            foreach ($node->getSubNodeNames() as $node_name)
            {
                if ($node->$node_name instanceof Node)
                {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $classes = array_merge($classes, self::extractClasses($node->$node_name, $prefix));
                }
                elseif (is_array($node->$node_name))
                {
                    foreach ($node->$node_name as $sub_node)
                    {
                        if ($sub_node instanceof Node)
                        {
                            /** @noinspection SlowArrayOperationsInLoopInspection */
                            $classes = array_merge($classes, self::extractClasses($sub_node, $prefix));
                        }
                    }
                }
            }

            return $classes;
        }
    }