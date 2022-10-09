<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Support\Facades\Hash;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class HardCodedCredentialHashFacadeRule implements Rule
{
    use AnalyzesNodes;

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if ($node->class instanceof Node\Name
            && $scope->resolveName($node->class) !== Hash::class
            && ! is_subclass_of($scope->resolveName($node->class), Hash::class)) {
            // We are only looking at static calls on the Hash facade
            return [];
        }

        if ($node->class instanceof Node\Expr && ! $this->isCalledOn($node->class, $scope, Hash::class)) {
            // We are only looking at static calls on the Hash facade
            return [];
        }

        if (! in_array($methodCall = $node->name->toString(), ['make', 'check',])) {
            return [];
        }

        if (isset($node->args[0])  && $this->isString($node->args[0]->value)) {
            return ["Hash::{$methodCall} call with hard coded credentials in your source code."];
        }

        return [];
    }

    /**
     * @param \PhpParser\Node $node
     * @return bool|null
     */
    protected function isString(Node $node)
    {
        if ($node instanceof Node\Scalar\String_) {
            return true;
        }
    }
}
