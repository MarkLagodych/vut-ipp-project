<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolClass;
use IPP\Interpreter\Builtin\Method;

/**
 * A metaclass contains all the static methods of a class.
 */
class SolMetaClass extends SolClass
{
    public function __construct()
    {
        $this->methods = [
            "new" => new Method(function (array $args) {
                /** @var SolClass */
                $thisClass = $args[0];

                return new SolObject($thisClass);
            }),
            "from:" => new Method(function (array $args) {
                /** @var SolClass */
                $thisClass = $args[0];
                $sourceObject = $args[1];

                $obj = new SolObject($thisClass);
                $obj->attributes = $sourceObject->attributes;
                $this->copyInternalAttributes($obj, $sourceObject);
                return $obj;
            }),
        ];
    }

    protected function copyInternalAttributes(SolObject &$target, SolObject $source): void
    {
    }
}
