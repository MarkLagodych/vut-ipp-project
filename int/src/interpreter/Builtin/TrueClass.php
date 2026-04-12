<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};

/**
 * `true` is a singleton instance of this class.
 */
class TrueClass extends BuiltinClass
{
    public function __construct(Scope $globalScope)
    {
        parent::__construct('True', $globalScope);

        $this->methods = [
            'isBoolean' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'asString' => new BuiltinMethod(fn($args) => $this->returnString()),
            'not' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('false')),
            'and' => new BuiltinMethod(fn($args) => $this->doAnd($args)),
            'or' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'ifTrue:ifFalse:' => new BuiltinMethod(fn($args) => $this->ifTrueIfFalse($args)),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(fn (array $args) => $this->getBuiltinObject('true')),
            'from:' => new BuiltinMethod(fn (array $args) => $this->getBuiltinObject('true')),
        ];
    }

    /**
     * @param array<SolObject> $args
     */
    private function doAnd(array $args): SolObject
    {
        // Evaluate the argument, which must be a block
        $arg = $args[1];
        return $arg->send('value');
    }

    /**
     * @param array<SolObject> $args
     */
    private function ifTrueIfFalse(array $args): SolObject
    {
        // Evaluate the first argument ("ifTrue" branch), which must be a block
        $trueBlock = $args[1];
        return $trueBlock->send('value');
    }

    private function returnString(): SolObject
    {
        $str = $this->getBuiltinClass('String')->send('new');
        $str->internalAttribute = 'true';
        return $str;
    }
}
