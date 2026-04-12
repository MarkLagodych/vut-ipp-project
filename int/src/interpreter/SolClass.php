<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolMetaClass, Scope, ExecutableBlock, MethodBlock};
use IPP\Interpreter\Builtin\BuiltinMethod;
use IPP\Interpreter\InputModel\{ClassDef};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

/**
 * A class is a first-order object in this implementation and is therefore itself a SolObject.
 *
 * By default, a SolClass inherits from SolMetaClass, which defines the `new` and `from:` static
 * methods.
 */
class SolClass extends SolObject
{
    /**
     * This is null only for the Object class in SOL.
     */
    public ?SolClass $parent = null;

    public string $name;

    /**
     * @var array<string, ExecutableBlock>
     */
    protected array $methods = [];

    /**
     * @var array<string, ExecutableBlock>
     */
    protected array $staticMethods = [];

    public function __construct(string $name)
    {
        $this->name = $name;

        // Semantically every `SolClass` is itself a `SolObject`.
        // Every `SolObject` needs a class for method lookup.
        // This anonymous class forwards method lookups to the static methods of the `SolClass`.
        parent::__construct(new class ($this) extends SolClass {
            public function __construct(private SolClass $myClass)
            {
                // Do not call parent::__construct here to avoid infinite recursion.
                $this->name = $myClass->name;
            }

            public function getMethod(string $selector): ?ExecutableBlock
            {
                return $this->myClass->getStaticMethod($selector);
            }
        });

        $this->staticMethods = [
            'new' => new BuiltinMethod(function (array $args) {
                return new SolObject($this);
            }),
            'from:' => new BuiltinMethod(function (array $args) {
                $sourceObj = $args[1];
                $obj = $this->send('new'); // Child classes can override `new`
                $obj->attributes = $sourceObj->attributes;
                $obj->internalAttribute = $sourceObj->internalAttribute;
                return $obj;
            }),
        ];
    }

    public function getMethod(string $selector): ?ExecutableBlock
    {
        return $this->methods[$selector]
            ?? $this->parent?->getMethod($selector);
    }

    public function getStaticMethod(string $selector): ?ExecutableBlock
    {
        return $this->staticMethods[$selector]
            ?? $this->parent?->getStaticMethod($selector);
    }

    public function loadMethods(Scope $globalScope, ClassDef $classDef): void
    {
        foreach ($classDef->methods as $methodDef) {
            if (isset($this->methods[$methodDef->selector])) {
                throw new InterpreterError(
                    ErrorCode::SEM_ERROR,
                    "Method '$this->name::$methodDef->selector' is already defined"
                );
            }

            $method = new Method($methodDef, $this, $globalScope);
            $this->methods[$methodDef->selector] = $method;
        }
    }
}
