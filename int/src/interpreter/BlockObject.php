<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, SolClass, Scope, Closure};
use IPP\Interpreter\Builtin\{BuiltinMethod};
use IPP\Interpreter\InputModel\{Block};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

use function IPP\Interpreter\Utils\makeBlockSelector;

class BlockObject extends SolObject
{
    public function __construct(Block $source, SolClass $contextClass, Scope $parentScope)
    {
        $selector = makeBlockSelector($source->arity);

        /** @var SolClass */
        $Block = $parentScope->getVariable('Block');

        /*
            The following can be translated to:
            ```
            class AnonymousBlock : Block { $selector [$source] }
            self := AnonymousBlock.
            ```
        */
        parent::__construct(new class ($selector, $Block) extends SolClass {
            public function __construct(string $selector, SolClass $Block)
            {
                parent::__construct('AnonymousBlock');

                $this->parent = $Block;

                $this->methods = [
                    $selector => new BuiltinMethod(function (array $args) {
                        /** @var Closure */
                        $closure = $this->internalAttribute;
                        return $closure->execute($args);
                    })
                ];
            }
        });

        $this->internalAttribute = new Closure($source, $contextClass, $parentScope);
    }
}
