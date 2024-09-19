<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser;

use ncc\ThirdParty\nikic\PhpParser\Node\Arg;
use ncc\ThirdParty\nikic\PhpParser\Node\Expr;
use ncc\ThirdParty\nikic\PhpParser\Node\Expr\BinaryOp\Concat;
use ncc\ThirdParty\nikic\PhpParser\Node\Identifier;
use ncc\ThirdParty\nikic\PhpParser\Node\Name;
use ncc\ThirdParty\nikic\PhpParser\Node\Scalar\String_;
use ncc\ThirdParty\nikic\PhpParser\Node\Stmt\Use_;

class BuilderFactory {
    /**
     * Creates an attribute node.
     *
     * @param string|Name $name Name of the attribute
     * @param array $args Attribute named arguments
     */
    public function attribute($name, array $args = []): ncc\ThirdParty\nikic\PhpParser\Node\Attribute {
        return new ncc\ThirdParty\nikic\PhpParser\Node\Attribute(
            BuilderHelpers::normalizeName($name),
            $this->args($args)
        );
    }

    /**
     * Creates a namespace builder.
     *
     * @param null|string|ncc\ThirdParty\nikic\PhpParser\Node\Name $name Name of the namespace
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Namespace_ The created namespace builder
     */
    public function namespace($name): ncc\ThirdParty\nikic\PhpParser\Builder\Namespace_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Namespace_($name);
    }

    /**
     * Creates a class builder.
     *
     * @param string $name Name of the class
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Class_ The created class builder
     */
    public function class(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Class_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Class_($name);
    }

    /**
     * Creates an interface builder.
     *
     * @param string $name Name of the interface
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Interface_ The created interface builder
     */
    public function interface(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Interface_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Interface_($name);
    }

    /**
     * Creates a trait builder.
     *
     * @param string $name Name of the trait
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Trait_ The created trait builder
     */
    public function trait(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Trait_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Trait_($name);
    }

    /**
     * Creates an enum builder.
     *
     * @param string $name Name of the enum
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Enum_ The created enum builder
     */
    public function enum(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Enum_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Enum_($name);
    }

    /**
     * Creates a trait use builder.
     *
     * @param ncc\ThirdParty\nikic\PhpParser\Node\Name|string ...$traits Trait names
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\TraitUse The created trait use builder
     */
    public function useTrait(...$traits): ncc\ThirdParty\nikic\PhpParser\Builder\TraitUse {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\TraitUse(...$traits);
    }

    /**
     * Creates a trait use adaptation builder.
     *
     * @param ncc\ThirdParty\nikic\PhpParser\Node\Name|string|null $trait Trait name
     * @param ncc\ThirdParty\nikic\PhpParser\Node\Identifier|string $method Method name
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\TraitUseAdaptation The created trait use adaptation builder
     */
    public function traitUseAdaptation($trait, $method = null): ncc\ThirdParty\nikic\PhpParser\Builder\TraitUseAdaptation {
        if ($method === null) {
            $method = $trait;
            $trait = null;
        }

        return new ncc\ThirdParty\nikic\PhpParser\Builder\TraitUseAdaptation($trait, $method);
    }

    /**
     * Creates a method builder.
     *
     * @param string $name Name of the method
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Method The created method builder
     */
    public function method(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Method {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Method($name);
    }

    /**
     * Creates a parameter builder.
     *
     * @param string $name Name of the parameter
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Param The created parameter builder
     */
    public function param(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Param {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Param($name);
    }

    /**
     * Creates a property builder.
     *
     * @param string $name Name of the property
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Property The created property builder
     */
    public function property(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Property {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Property($name);
    }

    /**
     * Creates a function builder.
     *
     * @param string $name Name of the function
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Function_ The created function builder
     */
    public function function(string $name): ncc\ThirdParty\nikic\PhpParser\Builder\Function_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Function_($name);
    }

    /**
     * Creates a namespace/class use builder.
     *
     * @param ncc\ThirdParty\nikic\PhpParser\Node\Name|string $name Name of the entity (namespace or class) to alias
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Use_ The created use builder
     */
    public function use($name): ncc\ThirdParty\nikic\PhpParser\Builder\Use_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Use_($name, Use_::TYPE_NORMAL);
    }

    /**
     * Creates a function use builder.
     *
     * @param ncc\ThirdParty\nikic\PhpParser\Node\Name|string $name Name of the function to alias
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Use_ The created use function builder
     */
    public function useFunction($name): ncc\ThirdParty\nikic\PhpParser\Builder\Use_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Use_($name, Use_::TYPE_FUNCTION);
    }

    /**
     * Creates a constant use builder.
     *
     * @param ncc\ThirdParty\nikic\PhpParser\Node\Name|string $name Name of the const to alias
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\Use_ The created use const builder
     */
    public function useConst($name): ncc\ThirdParty\nikic\PhpParser\Builder\Use_ {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\Use_($name, Use_::TYPE_CONSTANT);
    }

    /**
     * Creates a class constant builder.
     *
     * @param string|Identifier $name Name
     * @param ncc\ThirdParty\nikic\PhpParser\Node\Expr|bool|null|int|float|string|array $value Value
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\ClassConst The created use const builder
     */
    public function classConst($name, $value): ncc\ThirdParty\nikic\PhpParser\Builder\ClassConst {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\ClassConst($name, $value);
    }

    /**
     * Creates an enum case builder.
     *
     * @param string|Identifier $name Name
     *
     * @return ncc\ThirdParty\nikic\PhpParser\Builder\EnumCase The created use const builder
     */
    public function enumCase($name): ncc\ThirdParty\nikic\PhpParser\Builder\EnumCase {
        return new ncc\ThirdParty\nikic\PhpParser\Builder\EnumCase($name);
    }

    /**
     * Creates node a for a literal value.
     *
     * @param Expr|bool|null|int|float|string|array|\UnitEnum $value $value
     */
    public function val($value): Expr {
        return BuilderHelpers::normalizeValue($value);
    }

    /**
     * Creates variable node.
     *
     * @param string|Expr $name Name
     */
    public function var($name): Expr\Variable {
        if (!\is_string($name) && !$name instanceof Expr) {
            throw new \LogicException('Variable name must be string or Expr');
        }

        return new Expr\Variable($name);
    }

    /**
     * Normalizes an argument list.
     *
     * Creates Arg nodes for all arguments and converts literal values to expressions.
     *
     * @param array $args List of arguments to normalize
     *
     * @return list<Arg>
     */
    public function args(array $args): array {
        $normalizedArgs = [];
        foreach ($args as $key => $arg) {
            if (!($arg instanceof Arg)) {
                $arg = new Arg(BuilderHelpers::normalizeValue($arg));
            }
            if (\is_string($key)) {
                $arg->name = BuilderHelpers::normalizeIdentifier($key);
            }
            $normalizedArgs[] = $arg;
        }
        return $normalizedArgs;
    }

    /**
     * Creates a function call node.
     *
     * @param string|Name|Expr $name Function name
     * @param array $args Function arguments
     */
    public function funcCall($name, array $args = []): Expr\FuncCall {
        return new Expr\FuncCall(
            BuilderHelpers::normalizeNameOrExpr($name),
            $this->args($args)
        );
    }

    /**
     * Creates a method call node.
     *
     * @param Expr $var Variable the method is called on
     * @param string|Identifier|Expr $name Method name
     * @param array $args Method arguments
     */
    public function methodCall(Expr $var, $name, array $args = []): Expr\MethodCall {
        return new Expr\MethodCall(
            $var,
            BuilderHelpers::normalizeIdentifierOrExpr($name),
            $this->args($args)
        );
    }

    /**
     * Creates a static method call node.
     *
     * @param string|Name|Expr $class Class name
     * @param string|Identifier|Expr $name Method name
     * @param array $args Method arguments
     */
    public function staticCall($class, $name, array $args = []): Expr\StaticCall {
        return new Expr\StaticCall(
            BuilderHelpers::normalizeNameOrExpr($class),
            BuilderHelpers::normalizeIdentifierOrExpr($name),
            $this->args($args)
        );
    }

    /**
     * Creates an object creation node.
     *
     * @param string|Name|Expr $class Class name
     * @param array $args Constructor arguments
     */
    public function new($class, array $args = []): Expr\New_ {
        return new Expr\New_(
            BuilderHelpers::normalizeNameOrExpr($class),
            $this->args($args)
        );
    }

    /**
     * Creates a constant fetch node.
     *
     * @param string|Name $name Constant name
     */
    public function constFetch($name): Expr\ConstFetch {
        return new Expr\ConstFetch(BuilderHelpers::normalizeName($name));
    }

    /**
     * Creates a property fetch node.
     *
     * @param Expr $var Variable holding object
     * @param string|Identifier|Expr $name Property name
     */
    public function propertyFetch(Expr $var, $name): Expr\PropertyFetch {
        return new Expr\PropertyFetch($var, BuilderHelpers::normalizeIdentifierOrExpr($name));
    }

    /**
     * Creates a class constant fetch node.
     *
     * @param string|Name|Expr $class Class name
     * @param string|Identifier|Expr $name Constant name
     */
    public function classConstFetch($class, $name): Expr\ClassConstFetch {
        return new Expr\ClassConstFetch(
            BuilderHelpers::normalizeNameOrExpr($class),
            BuilderHelpers::normalizeIdentifierOrExpr($name)
        );
    }

    /**
     * Creates nested Concat nodes from a list of expressions.
     *
     * @param Expr|string ...$exprs Expressions or literal strings
     */
    public function concat(...$exprs): Concat {
        $numExprs = count($exprs);
        if ($numExprs < 2) {
            throw new \LogicException('Expected at least two expressions');
        }

        $lastConcat = $this->normalizeStringExpr($exprs[0]);
        for ($i = 1; $i < $numExprs; $i++) {
            $lastConcat = new Concat($lastConcat, $this->normalizeStringExpr($exprs[$i]));
        }
        return $lastConcat;
    }

    /**
     * @param string|Expr $expr
     */
    private function normalizeStringExpr($expr): Expr {
        if ($expr instanceof Expr) {
            return $expr;
        }

        if (\is_string($expr)) {
            return new String_($expr);
        }

        throw new \LogicException('Expected string or Expr');
    }
}
