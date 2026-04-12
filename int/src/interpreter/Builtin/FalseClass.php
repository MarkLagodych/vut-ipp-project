<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};

/**
 * `false` is a singleton instance of this class.
 */
class FalseClass extends BuiltinClass
{
    public function __construct(Scope $globalScope)
    {
        parent::__construct('False', $globalScope);

        $this->methods = [
            'isBoolean' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'asString' => new BuiltinMethod(fn($args) => $this->returnString()),
            'not' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'and' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('false')),
            'or' => new BuiltinMethod(fn($args) => $this->doOr($args)),
            'ifTrue:ifFalse:' => new BuiltinMethod(fn($args) => $this->ifTrueIfFalse($args)),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(fn (array $args) => $this->getBuiltinObject('false')),
            'from:' => new BuiltinMethod(fn (array $args) => $this->getBuiltinObject('false')),
        ];
    }

    /**
    * @param array<SolObject> $args
    */
    private function doOr(array $args): SolObject
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
        // Evaluate the second argument ("ifFalse" branch), which must be a block
        $falseBlock = $args[2];
        return $falseBlock->send('value');
    }

    private function returnString(): SolObject
    {
        $str = $this->getBuiltinClass('String')->send('new');
        $str->internalAttribute = 'false';
        return $str;
    }
}
