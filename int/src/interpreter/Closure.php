<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolClass, Scope, ExecutableBlock, BlockObject};
use IPP\Interpreter\Validation\ValidationScope;
use IPP\Interpreter\InputModel\{Block, Assign, Parameter, Expr, Literal, Send, Arg};
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

    public function __construct(Block $source, SolClass $class, Scope $parentScope)
    {
        $this->parentScope = $parentScope;
        $this->class = $class;

        $this->body = $source->assigns;
        $this->params = array_map(fn(Parameter $param) => $param->name, $source->parameters);
    }

    protected static function validateBlock(Block $block, Scope $parentScope): void
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

        if ($expr->variable !== null) {
            $scope->checkDefined($expr->variable->name);
            return;
        }

        if ($expr->literal !== null) {
            if ($expr->literal->classId === 'class') {
                $scope->checkDefined($expr->literal->value);
            }
            return;
        }

        if ($expr->block !== null) {
            self::validateBlock($expr->block, $scope);
            return;
        }

        if ($expr->send !== null) {
            self::validateExpr($expr->send->receiver, $scope);
            foreach ($expr->send->args as $arg) {
                self::validateExpr($arg->expr, $scope);
            }
            return;
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
        if ($expr->variable !== null) {
            return $this->evalVariable($expr->variable->name, $scope);
        }

        if ($expr->literal !== null) {
            if (in_array($expr->literal->classId, ['class', 'True', 'False', 'Nil'])) {
                return $this->evalVariable($expr->literal->value, $scope);
            }

            return $this->evalLiteral($expr->literal, $scope);
        }

        if ($expr->block !== null) {
            return $this->evalBlock($expr->block, $scope);
        }

        if ($expr->send !== null) {
            return $this->evalSend($expr->send, $scope);
        }

        // unreachable
        throw new \RuntimeException("Invalid expression");
    }

    private function evalVariable(string $name, Scope $scope): SolObject
    {
        /** @var SolObject (all the variables are validated and so this is never null) */
        return $scope->getVariable($name);
    }

    private function evalLiteral(Literal $literal, Scope $scope): SolObject
    {
        if ($literal->classId === 'Integer') {
            /** @var SolClass */
            $Integer = $scope->getVariable('Integer');
            $result = $Integer->send('new');
            $result->internalAttribute = (int)$literal->value;
            return $result;
        }

        if ($literal->classId === 'String') {
            /** @var SolClass */
            $String = $scope->getVariable('String');
            $result = $String->send('new');
            $result->internalAttribute = $literal->value;
            return $result;
        }

        // unreachable
        throw new \RuntimeException("Invalid literal");
    }

    private function evalBlock(Block $block, Scope $scope): SolObject
    {
        return new BlockObject($block, $this->class, $scope);
    }

    private function evalSend(Send $send, Scope $scope): SolObject
    {
        throw new \RuntimeException("TODO");
    }
}
