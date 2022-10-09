<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Filesystem\Filesystem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class DirectoryTraversalInstanceRule implements Rule
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
        if (! $this->isCalledOn($node->var, $scope, ResponseFactory::class)
            && ! $this->isCalledOn($node->var, $scope, FilesystemContract::class)
            && ! $this->isCalledOn($node->var, $scope, Filesystem::class)) {
            // If the method is not called on a ResponseFactory or Filesystem, no errors.
            // Apparently, the Filesystem doesn't implement the Filesystem contract, so we have to check for both.
            return [];
        }

        if (! $node->name instanceof Node\Identifier
            || ! in_array($methodName = $node->name->toString(), [
                'download', 'file', 'get', 'put', 'prepend', 'append', 'delete', 'copy', 'move', 'exists',
                'chmod', 'replace', 'sharedGet', 'link', 'relativeLink', 'deleteDirectory', 'deleteDirectories',
                'cleanDirectory', 'makeDirectory', 'moveDirectory', 'copyDirectory',
            ])) {
            // If the method name doesn't match the names above, no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["Method call {$methodName} with request data could result in a directory traversal vulnerability."];
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
