<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, Scope, ExecutableBlock};
use IPP\Interpreter\InputModel\{Block as BlockSource, Assign, Parameter};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class Closure implements ExecutableBlock
{
    private Scope $parentScope;
    private BlockSource $body;

    public function __construct(BlockSource $source, Scope $parentScope)
    {
        $this->parentScope = $parentScope;
        $this->body = $source;
        self::validateBlock($this->body, $this->parentScope);
    }

    private static function validateBlock(BlockSource $block, Scope $parentScope): void
    {
        self::validateParams($block->parameters);

        $localScope = new Scope($parentScope, $block->parameters);

        foreach ($block->assigns as $assignment) {
            self::validateAssignment($assignment, $localScope);
        }
    }

    private static function validateAssignment(Assign $assignment, Scope $scope): void
    {
        // TODO implement assignment validation
    }

    /**
     * @param array<Parameter> $params
     */
    private static function validateParams(array $params): void
    {
        /** @var array<string, bool> (param name => true) */
        $visited = [];
        foreach ($params as $param) {
            $name = $param->name;
            // "_" always ignores its value and can never be assigned, even if it is a parameter
            if ($name == "_") {
                continue;
            }

            if (isset($visited[$name])) {
                throw new InterpreterError(
                    ErrorCode::SEM_ERROR,
                    "Duplicate parameter name '$name' in closure"
                );
            }
            $visited[$name] = true;
        }
    }

    public function execute(array $args): SolObject
    {
        // TODO implement closure execution
        return new SolObject(new SolMetaClass());
    }
}
