<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};

/**
 * `nil` is a singleton instance of this class.
 */
class NilClass extends BuiltinClass
{
    public function __construct(Scope $globalScope)
    {
        parent::__construct('Nil', $globalScope);

        $this->methods = [
            'isNil' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'asString' => new BuiltinMethod(fn($args) => $this->returnNilString()),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('nil')),
            'from:' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('nil')),
        ];
    }

    private function returnNilString(): SolObject
    {
        $str = $this->getBuiltinObject('String')->send('new');
        $str->internalAttribute = 'nil';
        return $str;
    }
}
