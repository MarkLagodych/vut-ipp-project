<?php

declare(strict_types=1);

namespace IPP\Interpreter\Validation;

use IPP\Interpreter\Scope;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

/**
 * This scope performs validation checks on every variable assignment.
 * It is intended to be used for validating blocks: it is constructed with block parameters as
 * initial variables and always checks that they are not assigned.
 */
class ValidationScope extends Scope
{
    /**
     * Contains variable names that are block parameters.
     * Such variables are immutable.
     * @var array<string, true>
     */
    private array $params;

    /**
     * @param array<string> $params
     */
    public function __construct(?Scope $parent, array $params = [])
    {
        $params = self::processParams($params);
        $this->params = array_fill_keys($params, true);
        parent::__construct($parent, array_fill_keys($params, new \stdClass()));
    }

    /**
     * @param array<string> $params
     * @return array<string>
     */
    private static function processParams(array $params): array
    {
        $params = array_filter($params, fn (string $name) => $name !== '_');
        asort($params);

        array_reduce($params, function ($prev, $next) {
            if ($prev === $next) {
                throw new InterpreterError(ErrorCode::SEM_ERROR, "Duplicate parameter '$prev'");
            }

            return $next;
        });

        return $params;
    }

    protected function tryUpdateVariable(string $name, object $value): bool
    {
        if (isset($this->params[$name])) {
            throw new InterpreterError(
                ErrorCode::SEM_COLLISION,
                "Cannot assign to parameter '$name'"
            );
        }

        return parent::tryUpdateVariable($name, $value);
    }

    /**
     * Simulates assignment to a variable. This makes it defined.
     * Throws if the variable is a parameter.
     */
    public function checkAssign(string $name): void
    {
        $this->setVariable($name, new \stdClass());
    }

    public function checkDefined(string $name): void
    {
        if ($this->getVariable($name) === null) {
            throw new InterpreterError(ErrorCode::SEM_UNDEF, "Undefined variable '$name'");
        }
    }
}
