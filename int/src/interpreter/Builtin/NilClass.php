<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\BuiltinMethod;

/**
 * `nil` is a singleton instance of this class.
 */
class NilClass extends SolClass
{
    public function __construct(private Scope $globalScope)
    {
        parent::__construct('Nil');

        /** @var SolClass */
        $Object = $this->globalScope->getVariable('Object');
        $this->parent = $Object;

        $this->methods = [
            'isNil' => new BuiltinMethod(fn($args) => $this->returnTrue()),
            'asString' => new BuiltinMethod(fn($args) => $this->returnNilString()),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(function (array $args) {
                /** @var SolClass */
                return $this->globalScope->getVariable('nil');
            }),
            'from:' => new BuiltinMethod(function (array $args) {
                /** @var SolClass */
                return $this->globalScope->getVariable('nil');
            }),
        ];
    }

    private function returnTrue(): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable('true');
    }

    private function returnNilString(): SolObject
    {
        /** @var SolObject */
        $String = $this->globalScope->getVariable('String');
        $str = $String->send('new');
        $str->internalAttribute = 'nil';
        return $str;
    }
}
