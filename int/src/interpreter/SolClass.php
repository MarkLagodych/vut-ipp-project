<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolMetaClass, Scope, ExecutableBlock, MethodBlock};
use IPP\Interpreter\InputModel\{ClassDef};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

/**
 * A class is a first-order object in this implementation and is therefore itself a SolObject.
 *
 * By default, a SolClass inherits from SolMetaClass, which defines the `new` and `from` static
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

    public function __construct(string $name)
    {
        $this->name = $name;
        parent::__construct(new SolMetaClass());
    }

    final public function getMethod(string $selector): ?ExecutableBlock
    {
        if (isset($this->methods[$selector])) {
            return $this->methods[$selector];
        }

        if ($this->parent === null) {
            return null;
        }

        return $this->parent->getMethod($selector);
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
