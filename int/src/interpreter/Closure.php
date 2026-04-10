<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, Scope, ExecutableBlock};
use IPP\Interpreter\Validation\ValidationScope;
use IPP\Interpreter\InputModel\{Block as BlockSource, Assign, Parameter, Expr, Literal, Send, Arg};
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
        $params = array_map(fn(Parameter $param) => $param->name, $block->parameters);

        $localScope = new ValidationScope($parentScope, $params);

        foreach ($block->assigns as $assignment) {
            self::validateAssignment($assignment, $localScope);
        }
    }

    private static function validateAssignment(Assign $assignment, ValidationScope $scope): void
    {
        self::validateExpr($assignment->expr, $scope);
        $scope->checkAssign($assignment->target->name);
    }

    private static function validateExpr(Expr $expr, ValidationScope $scope): void
    {
        if ($expr->variable !== null) {
            $scope->checkDefined($expr->variable->name);
        } elseif ($expr->literal !== null) {
            if ($expr->literal->classId === 'class') {
                $scope->checkDefined($expr->literal->value);
            }
        } elseif ($expr->block !== null) {
            self::validateBlock($expr->block, $scope);
        } elseif ($expr->send !== null) {
            self::validateExpr($expr->send->receiver, $scope);
            foreach ($expr->send->args as $arg) {
                self::validateExpr($arg->expr, $scope);
            }
        }
    }

    public function execute(array $args): SolObject
    {
        // TODO implement closure execution
        return new SolObject(new SolMetaClass());
    }
}
