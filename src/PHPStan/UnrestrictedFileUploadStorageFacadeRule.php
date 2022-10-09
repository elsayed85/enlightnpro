<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class UnrestrictedFileUploadStorageFacadeRule implements Rule
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
        if ($node->class instanceof Node\Name
            && $scope->resolveName($node->class) !== Storage::class
            && ! is_subclass_of($scope->resolveName($node->class), Storage::class)) {
            // We are only looking at static calls on the Storage facade class.
            return [];
        }

        if ($node->class instanceof Node\Expr && ! $this->isCalledOn($node->class, $scope, Storage::class)) {
            // We are only looking at static calls on the Storage facade class.
            return [];
        }

        if (! $node->name instanceof Node\Identifier
            || ! in_array($methodName = $node->name->toString(), [
                'putFile', 'putFileAs', 'put', 'writeStream',
            ])) {
            // We are only looking for the above method calls.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                "Static call {$methodName} on Storage facade with request data may lead to an "
                ."unrestricted file upload vulnerability."
            ];
        }

        return [];
    }

    /**
     * @param \PhpParser\Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool|null
     */
    protected function hasRequestCall(Node $node, Scope $scope)
    {
        if ($node instanceof Node\Expr\FuncCall
            && $node->name instanceof Node\Name
            && in_array($node->name->toString(), ['realpath', 'basename'])) {
            // If the code has a realpath or basename function call, stop here and return. It's safe!
            return null;
        }

        return ($node instanceof Expr) &&
            ($this->isRequestData($node, $scope) || $this->isRequestArrayData($node, $scope));
    }
}
