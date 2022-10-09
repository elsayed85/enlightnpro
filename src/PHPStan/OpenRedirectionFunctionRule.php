<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class OpenRedirectionFunctionRule implements Rule
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
            || $node->name->toString() !== 'redirect') {
            // If it's not a redirect function call, no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["Call to redirect helper function allows a redirect to a user provided URL."];
        }

        return [];
    }
}
