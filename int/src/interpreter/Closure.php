<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolClass, Scope, ExecutableBlock};
use IPP\Interpreter\Validation\ValidationScope;
use IPP\Interpreter\InputModel\{Block as BlockSource, Assign, Parameter, Expr, Literal, Send, Arg};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};
use IPP\Interpreter\Utils\ExprType;

use function IPP\Interpreter\Utils\{
    getExprType, getExprVariable, getExprLiteral, getExprBlock, getExprSend
};

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
    }

    protected static function validateBlock(BlockSource $block, Scope $parentScope): void
    {
        $params = array_map(fn(Parameter $param) => $param->name, $block->parameters);
        self::validateParams($params);

        $localScope = new ValidationScope($parentScope, $params);

        foreach ($block->assigns as $assignment) {
            self::validateAssignment($assignment, $localScope);
        }
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

    private static function validateAssignment(Assign $assignment, ValidationScope $scope): void
    {
        self::validateExpr($assignment->expr, $scope);
        $scope->checkAssign($assignment->target->name);
    }

    private static function validateExpr(Expr $expr, ValidationScope $scope): void
    {
        switch (getExprType($expr)) {
            case ExprType::Variable:
                $var = getExprVariable($expr);
                $scope->checkDefined($var->name);
                break;

            case ExprType::Literal:
                $literal = getExprLiteral($expr);
                if ($literal->classId === 'class') {
                    $scope->checkDefined($literal->value);
                }
                break;

            case ExprType::Block:
                $block = getExprBlock($expr);
                self::validateBlock($block, $scope);
                break;

            case ExprType::Send:
                $send = getExprSend($expr);
                self::validateExpr($send->receiver, $scope);
                foreach ($send->args as $arg) {
                    self::validateExpr($arg->expr, $scope);
                }
                break;
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
        if (count($this->body) === 0) {
            /** @var SolObject (the "null" object is always defined and is never NULL) */
            $null = $localScope->getVariable('null');
            return $null;
        }

        foreach ($this->body as $assignment) {
            $lastValue = $this->evalExpr($assignment->expr, $localScope);
            $localScope->setVariable($assignment->target->name, $lastValue);
        }

        return $lastValue;
    }

    private function evalExpr(Expr $expr, Scope $scope): SolObject
    {
        switch (getExprType($expr)) {
            case ExprType::Variable:
                return $this->evalVariable(getExprVariable($expr)->name, $scope);

            case ExprType::Literal:
                $literal = getExprLiteral($expr);
                if (in_array($literal->classId, ['class', 'True', 'False', 'Nil'])) {
                    return $this->evalVariable($literal->value, $scope);
                }

                return $this->evalLiteral($literal, $scope);

            case ExprType::Block:
                return $this->evalBlock(getExprBlock($expr), $scope);

            case ExprType::Send:
                return $this->evalSend(getExprSend($expr), $scope);
        }
    }

    private function evalVariable(string $name, Scope $scope): SolObject
    {
        /** @var SolObject (all the variables are validated and so this is never null) */
        return $scope->getVariable($name);
    }

    private function evalLiteral(Literal $literal, Scope $scope): SolObject
    {
        throw new \RuntimeException("TODO");
    }

    private function evalBlock(BlockSource $block, Scope $scope): SolObject
    {
        throw new \RuntimeException("TODO");
    }

    private function evalSend(Send $send, Scope $scope): SolObject
    {
        throw new \RuntimeException("TODO");
    }
}
