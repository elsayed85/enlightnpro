<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class ExtractRule implements Rule
{
    use AnalyzesNodes;

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
            || $node->name->toString() !== 'extract') {
            // If it's not an extract function call, no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["You must never use extract on untrusted user data as it can overwrite variables including "
                ."superglobals. This could expose your application to code injection attacks."];
        }

        return [];
    }
}
