<?php

namespace Enlightn\EnlightnPro\PHPStan;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class RegexDosFunctionRule extends DirectoryTraversalInstanceRule
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
            || ! in_array($methodName = $node->name->toString(), [
                'preg_match', 'preg_filter', 'preg_grep', 'preg_match_all', 'preg_replace_callback_array',
                'preg_replace_callback', 'preg_replace', 'preg_split'
            ])) {
            // If it's not a blacklisted function call, no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["Function call {$methodName} on request data may lead to a regex DOS vulnerability."];
        }

        return [];
    }
}
