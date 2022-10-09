<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Http\UploadedFile;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class UnrestrictedFileUploadRule implements Rule
{
    use AnalyzesNodes;

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->isCalledOn($node->var, $scope, UploadedFile::class)
            && ! $this->isMaybeCalledOn($node->var, $scope, UploadedFile::class)) {
            // If the method is not called on an UploadedFile, no errors.
            return [];
        }

        if (! $node->name instanceof Node\Identifier
            || ! in_array($methodName = $node->name->toString(), [
                'store', 'storePublicly', 'storePubliclyAs', 'storeAs',
            ])) {
            // If the method name doesn't match the names above, no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                "Method call {$methodName} on UploadedFile instance with request data may lead to an "
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
