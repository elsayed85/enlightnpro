<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class DirectoryTraversalStorageFacadeRule extends DirectoryTraversalInstanceRule
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
            || ! in_array($funcName = $node->name->toString(), [
                'allDirectories', 'allFiles', 'directories', 'files', 'append', 'copy', 'delete', 'deleteDirectory',
                'exists', 'makeDirectory', 'move', 'prepend', 'get', 'readStream', 'temporaryUrl', 'url',
                'setVisibility', 'getVisibility', 'download',
            ])) {
            // We are only looking for the above method calls.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["Static call {$funcName} on Storage facade with request data could result in a directory traversal vulnerability."];
        }

        return [];
    }
}
