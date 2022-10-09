<?php

namespace Enlightn\EnlightnPro\PHPStan;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class DirectoryTraversalFunctionRule extends DirectoryTraversalInstanceRule
{
    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Name
            || ! in_array($funcName = $node->name->toString(), [
                'file_get_contents', 'file_put_contents', 'chmod', 'copy', 'unlink', 'file', 'fopen', 'mkdir',
                'readfile', 'readlink', 'rmdir', 'symlink', 'touch',
            ])) {
            // If it's not a blacklisted function call, no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["Function call {$funcName} with request data could result in a directory traversal vulnerability."];
        }

        return [];
    }
}
