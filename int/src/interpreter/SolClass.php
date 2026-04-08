<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\InputModel\{Block, Method, ClassDef};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};
use IPP\Interpreter\SolState;

class SolClass
{
    /**
     * This is null only for the Object class.
     */
    public ?SolClass $parent = null;

    /**
     * Map: string selector => Block method source
     */
    private array $methods = [];

    private function __construct()
    {
    }

    public static function fromSource(ClassDef $source, SolState $state): self
    {
        $instance = new self();

        $instance->parent = $state->getClass($source->parent);

        $instance->methods = [];
        foreach ($source->methods as $method) {
            $selectorArity = self::getSelectorArity($method->selector);

            if ($selectorArity !== $method->block->arity) {
                throw new InterpreterError(
                    ErrorCode::SEM_ARITY,
                    "$source->name::$method->selector arity mismatch: "
                    . "expected $selectorArity, got {$method->block->arity}"
                );
            }

            if (isset($instance->methods[$method->selector])) {
                throw new InterpreterError(
                    ErrorCode::SEM_ERROR,
                    "redefinition of method: $method->selector in class $source->name"
                );
            }

            $instance->methods[$method->selector] = $method->block;
        }

        return $instance;
    }

    public static function getSelectorArity(string $selector): int
    {
        return substr_count($selector, ':');
    }

    public function searchMethod(string $selector): ?Block
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
