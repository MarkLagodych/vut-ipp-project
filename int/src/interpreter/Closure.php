<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolClass, Scope, ExecutableBlock};
use IPP\Interpreter\Validation\ValidationScope;
use IPP\Interpreter\InputModel\{Block as BlockSource, Assign, Parameter, Expr, Literal, Send, Arg};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class Closure implements ExecutableBlock
{
    protected Scope $parentScope;

    protected SolClass $class;

    /**
     * @var array<string>
     */
    protected array $params;

    /**
    * @var array<Assign>
    */
    protected array $body;

    public function __construct(BlockSource $source, SolClass $class, Scope $parentScope)
    {
        $this->parentScope = $parentScope;
        $this->class = $class;

        $this->body = $source->assigns;
        $this->params = array_map(fn(Parameter $param) => $param->name, $source->parameters);

        self::validateBlock($source, $this->parentScope);
    }

    /**
     * @param array<string> $params
     */
    private static function validateParams(array $params): void
    {
        asort($params);

        array_reduce($params, function ($prev, $next) {
            if ($prev === $next) {
                throw new InterpreterError(ErrorCode::SEM_ERROR, "Duplicate parameter '$prev'");
            }

            return $next;
        });
    }

    private static function validateBlock(BlockSource $block, Scope $parentScope): void
    {
        $params = array_map(fn(Parameter $param) => $param->name, $block->parameters);
        self::validateParams($params);

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

    /**
     * @param array<SolObject> $args
     */
    final public function execute(array $args): SolObject
    {
        $localScope = new Scope($this->parentScope, array_combine($this->params, $args));
        return $this->executeInScope($localScope);
    }

    /**
     * Override this method to access the local scope.
     */
    protected function executeInScope(Scope $localScope): SolObject
    {
        // TODO implement closure execution
        return new SolObject(new SolMetaClass());
    }
}
