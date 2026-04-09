<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolBlock, SolMetaClass, ExecutableBlock};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

/**
 * A class is a first-order object in this implementation and is therefore itself a SolObject.
 *
 * By default, a SolClass inherits from SolMetaClass, which defines the `new` and `from` static
 * methods.
 */
abstract class SolClass extends SolObject
{
    /**
     * This is null only for the Object class in SOL.
     */
    public ?SolClass $parent = null;

    /**
     * Map: string selector => ExecutableBlock method source
     */
    protected array $methods = [];

    public function __construct()
    {
        parent::__construct(new SolMetaClass());
    }

    public function searchMethod(string $selector): ?ExecutableBlock
    {
        if ($this->methods[$selector] !== null) {
            return $this->methods[$selector];
        }

        if ($this->parent !== null) {
            return $this->parent->searchMethod($selector);
        }

        return null;
    }
}
