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
         * Returns an array representation of the node recursively
         *
         * @param array|Node $node
         * @return array
         */
        public static function serialize(array|Node $node): array
        {
            if(is_array($node))
            {
                $serialized = [];
                foreach($node as $sub_node)
                {
                    $serialized[] = $sub_node->jsonSerialize();
                }
                return $serialized;
            }

            return $node->jsonSerialize();
        }

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

        /**
         * Reconstructs nodes from an array representation recursively
         *
         * @param $value
         * @return array|Comment|Node
         * @noinspection PhpMissingReturnTypeInspection
         * @throws ReflectionException
         */
        public static function decodeRecursive($value)
        {
            if (is_array($value))
            {
                if (isset($value['nodeType']))
                {
                    if ($value['nodeType'] === 'Comment' || $value['nodeType'] === 'Comment_Doc')
                    {
                        return self::decodeComment($value);
                    }

                    return self::decodeNode($value);
                }

                return self::decodeArray($value);
            }

            return $value;
        }

        /**
         * Decodes an array by recursively decoding each value
         *
         * @param array $array
         * @return array
         * @throws ReflectionException
         */
        private static function decodeArray(array $array) : array
        {
            $decoded_array = [];

            foreach ($array as $key => $value)
            {
                $decoded_array[$key] = self::decodeRecursive($value);
            }

            return $decoded_array;
        }

        /**
         * Returns the node from the node type
         *
         * @param array $value
         * @return Node
         * @throws ReflectionException
         */
        private static function decodeNode(array $value) : Node
        {
            $node_type = $value['nodeType'];
            if (!is_string($node_type))
            {
                throw new RuntimeException('Node type must be a string');
            }

            /** @var Node $node */
            $node = self::reflectionClassFromNodeType($node_type)->newInstanceWithoutConstructor();

            if (isset($value['attributes'])) {
                if (!is_array($value['attributes']))
                {
                    throw new RuntimeException('Attributes must be an array');
                }

                $node->setAttributes(self::decodeArray($value['attributes']));
            }

            foreach ($value as $name => $sub_node) {
                if ($name === 'nodeType' || $name === 'attributes')
                {
                    continue;
                }

                $node->$name = self::decodeRecursive($sub_node);
            }

            return $node;
        }

        /**
         * Returns the comment from the node type
         *
         * @param array $value
         * @return Comment
         */
        private static function decodeComment(array $value): Comment
        {
            $class_name = $value['nodeType'] === 'Comment' ? Comment::class : Comment\Doc::class;
            if (!isset($value['text']))
            {
                throw new RuntimeException('Comment must have text');
            }

            return new $class_name(
                $value['text'],
                $value['line'] ?? -1, $value['filePos'] ?? -1, $value['tokenPos'] ?? -1,
                $value['endLine'] ?? -1, $value['endFilePos'] ?? -1, $value['endTokenPos'] ?? -1
            );
        }

        /**
         * Returns the reflection class from the node type
         *
         * @param string $node_type
         * @return ReflectionClass
         * @throws ReflectionException
         */
        private static function reflectionClassFromNodeType(string $node_type): ReflectionClass
        {
            return new ReflectionClass(self::classNameFromNodeType($node_type));
        }

        /**
         * Returns the class name from the node type
         *
         * @param string $nodeType
         * @return string
         */
        private static function classNameFromNodeType(string $nodeType): string
        {
            $class_name = 'ncc\\ThirdParty\\nikic\\PhpParser\\Node\\' . str_replace('_', '\\', $nodeType);
            if (class_exists($class_name))
            {
                return $class_name;
            }

            $class_name .= '_';
            if (class_exists($class_name))
            {
                return $class_name;
            }

            throw new RuntimeException("Unknown node type \"$nodeType\"");
        }

        /**
         * Transforms include, include_once, require and require_once statements into function calls.
         *
         * @param Node|array $stmts The AST node or array of nodes to transform.
         * @param string|null $package Optionally. The package name to pass to the transformed function calls.
         * @return Node|array The transformed AST node or array of nodes.
         */
        public static function transformRequireCalls(Node|array $stmts, ?string $package=null): Node|array
        {
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new ExpressionTraverser($package));

            return $traverser->traverse($stmts);
        }
    }