<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\BuiltinMethod;

/**
 * This is the root ancestor of all SOL classes.
 */
class ObjectClass extends SolClass
{
    public function __construct(private Scope $globalScope)
    {
        parent::__construct('Object');

        $this->methods = [
            'identicalTo:' => new BuiltinMethod(fn($args) => $this->compareObjects($args)),
            'equalTo:' => new BuiltinMethod(fn($args) => $this->compareObjects($args)),
            'isNumber' => new BuiltinMethod(fn($args) => $this->returnFalse()),
            'isString' => new BuiltinMethod(fn($args) => $this->returnFalse()),
            'isBlock' => new BuiltinMethod(fn($args) => $this->returnFalse()),
            'isNil' => new BuiltinMethod(fn($args) => $this->returnFalse()),
            'isBoolean' => new BuiltinMethod(fn($args) => $this->returnFalse()),
            'asString' => new BuiltinMethod(fn($args) => $this->returnEmptyString()),
        ];
    }

    /**
     * @param array<SolObject> $args
     */
    private function compareObjects(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        /** @var SolObject */
        return $self === $other
            ? $this->globalScope->getVariable('true')
            : $this->globalScope->getVariable('false');
    }

    private function returnFalse(): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable('false');
    }

    private function returnEmptyString(): SolObject
    {
        /** @var SolObject */
        $String = $this->globalScope->getVariable('String');
        return $String->send('new');
    }
}
