<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\BuiltinMethod;

/**
 * `false` is a singleton instance of this class.
 */
class FalseClass extends SolClass
{
    public function __construct(private Scope $globalScope)
    {
        parent::__construct('False');

        /** @var SolClass */
        $Object = $this->globalScope->getVariable('Object');
        $this->parent = $Object;

        $this->methods = [
            'isBoolean' => new BuiltinMethod(fn($args) => $this->returnTrue()),
            'asString' => new BuiltinMethod(fn($args) => $this->returnString()),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(function (array $args) {
                /** @var SolClass */
                return $this->globalScope->getVariable('false');
            }),
            'from:' => new BuiltinMethod(function (array $args) {
                /** @var SolClass */
                return $this->globalScope->getVariable('false');
            }),
        ];
    }

    private function returnTrue(): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable('true');
    }

    private function returnString(): SolObject
    {
        /** @var SolObject */
        $String = $this->globalScope->getVariable('String');
        $result = $String->send('new');
        $result->internalAttribute = 'false';
        return $result;
    }
}
