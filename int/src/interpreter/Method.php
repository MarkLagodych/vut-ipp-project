<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, Closure};
use IPP\Interpreter\InputModel\{Method as MethodSource, Block as BlockSource};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

use function IPP\Interpreter\getSelectorArity;

class Method extends Closure
{
    private string $className;
    private string $selector;

    public function __construct(MethodSource $source, string $className, Scope $globalScope)
    {
        $this->className = $className;
        $this->selector = $source->selector;

        self::validateArity($source, $className);
        parent::__construct($source->block, $globalScope);
    }

    private static function validateArity(MethodSource $methodDef, string $className): void
    {
        $selectorArity = getSelectorArity($methodDef->selector);
        if ($selectorArity !== $methodDef->block->arity) {
            throw new InterpreterError(
                ErrorCode::SEM_ARITY,
                "Method selector '$className::$methodDef->selector' has arity $selectorArity,"
                . " but block has arity {$methodDef->block->arity}"
            );
        }
    }

    public function execute(array $args): SolObject
    {
        try {
            return parent::execute($args);
        } catch (InterpreterError $e) {
            throw new InterpreterError(
                $e->errorCode,
                $e->getMessage() . "\n\tat $this->className::$this->selector"
            );
        }
    }
}
