<?php
/*
 * Copyright (c) Nosial 2022-2024, all rights reserved.
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
use ncc\ThirdParty\nikic\PhpParser\NodeAbstract;
use ReflectionClass;
use RuntimeException;
use function is_array;
use function is_string;

class Serializer
{
    /** @var ReflectionClass<Node>[] Node type to reflection class map */
    private static array $reflectionClassCache;

    /**
     * @param NodeAbstract[] $nodeAbstracts
     * @return array
     */
    public static function nodesToArray(array $nodeAbstracts): array
    {
        $serialized = [];

        foreach ($nodeAbstracts as $nodeAbstract)
        {
            $serialized[] = $nodeAbstract->jsonSerialize();
        }

        return $serialized;
    }

    /**
     * @param array $nodeAbstracts
     * @return NodeAbstract[]
     */
    public static function arrayToNodes(array $nodeAbstracts): array
    {
        return self::decodeRecursive($nodeAbstracts);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function decodeRecursive($value) {
        if (is_array($value)) {
            if (isset($value['nodeType'])) {
                if ($value['nodeType'] === 'Comment' || $value['nodeType'] === 'Comment_Doc') {
                    return self::decodeComment($value);
                }
                return self::decodeNode($value);
            }
            return self::decodeArray($value);
        }
        return $value;
    }

    private static function decodeArray(array $array): array {
        $decodedArray = [];
        foreach ($array as $key => $value) {
            $decodedArray[$key] = self::decodeRecursive($value);
        }
        return $decodedArray;
    }

    private static function decodeNode(array $value): Node {
        $nodeType = $value['nodeType'];
        if (!is_string($nodeType)) {
            throw new RuntimeException('Node type must be a string');
        }

        $reflectionClass = self::reflectionClassFromNodeType($nodeType);
        $node = $reflectionClass->newInstanceWithoutConstructor();

        if (isset($value['attributes'])) {
            if (!is_array($value['attributes'])) {
                throw new RuntimeException('Attributes must be an array');
            }

            $node->setAttributes(self::decodeArray($value['attributes']));
        }

        foreach ($value as $name => $subNode) {
            if ($name === 'nodeType' || $name === 'attributes') {
                continue;
            }

            $node->$name = self::decodeRecursive($subNode);
        }

        return $node;
    }

    private static function decodeComment(array $value): Comment {
        $className = $value['nodeType'] === 'Comment' ? Comment::class : Comment\Doc::class;
        if (!isset($value['text'])) {
            throw new RuntimeException('Comment must have text');
        }

        return new $className(
            $value['text'],
            $value['line'] ?? -1, $value['filePos'] ?? -1, $value['tokenPos'] ?? -1,
            $value['endLine'] ?? -1, $value['endFilePos'] ?? -1, $value['endTokenPos'] ?? -1
        );
    }

    private static function reflectionClassFromNodeType(string $nodeType): ReflectionClass {
        if (!isset(self::$reflectionClassCache[$nodeType])) {
            $className = self::classNameFromNodeType($nodeType);
            self::$reflectionClassCache[$nodeType] = new ReflectionClass($className);
        }
        return self::$reflectionClassCache[$nodeType];
    }

    /** @return class-string<Node> */
    private static function classNameFromNodeType(string $nodeType): string {
        $className = '\\ncc\\ThirdParty\\nikic\\PhpParser\\Node\\' . strtr($nodeType, '_', '\\');
        if (class_exists($className)) {
            return $className;
        }

        $className .= '_';
        if (class_exists($className)) {
            return $className;
        }

        throw new RuntimeException("Unknown node type \"$nodeType\"");
    }
}