<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolClass, Closure};
use IPP\Interpreter\Validation\ValidationScope;
use IPP\Interpreter\InputModel\{Method as MethodSource, Block as BlockSource};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

use function IPP\Interpreter\Utils\getSelectorArity;

class Method extends Closure
{
    private string $selector;

    public function __construct(MethodSource $source, SolClass $class, Scope $globalScope)
    {
        $this->selector = $source->selector;

        self::validateArity($source, $class->name);

        $validationScope = new ValidationScope($globalScope, ['self', 'super']);
        parent::validateBlock($source->block, $validationScope);

        parent::__construct($source->block, $class, $globalScope);

        // Accept the receiver as an implicit first parameter
        $this->params = ['self', ...$this->params];
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

    protected function executeInScope(Scope $localScope): SolObject
    {
        /** @var SolObject (this is never null) */
        $self = $localScope->getVariable('self');

        // 'super' as a variable is just an alias to 'self' for instance methods.
        $localScope = new Scope($localScope, ['super' => $self]);

        try {
            return parent::executeInScope($localScope);
        } catch (InterpreterError $e) {
            throw new InterpreterError(
                $e->errorCode,
                $e->getMessage() . "\n\tat {$this->class->name}::$this->selector"
            );
        }
    }
}
