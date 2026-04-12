<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};

/**
 * This is the root ancestor of all SOL classes.
 */
class ObjectClass extends BuiltinClass
{
    public function __construct(Scope $globalScope)
    {
        parent::__construct('Object', $globalScope);

        $this->methods = [
            'identicalTo:' => new BuiltinMethod(fn($args) => $this->compareObjects($args)),
            'equalTo:' => new BuiltinMethod(fn($args) => $this->compareObjects($args)),
            'isNumber' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('false')),
            'isString' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('false')),
            'isBlock' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('false')),
            'isNil' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('false')),
            'isBoolean' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('false')),
            'asString' => new BuiltinMethod(fn($args) => $this->asString()),
        ];
    }

    /**
     * @param array<SolObject> $args
     */
    private function compareObjects(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        return $self === $other
            ? $this->getBuiltinObject('true')
            : $this->getBuiltinObject('false');
    }

    private function asString(): SolObject
    {
        return $this->getBuiltinClass('String')->send('new');
    }
}
