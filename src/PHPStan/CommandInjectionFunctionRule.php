<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class CommandInjectionFunctionRule implements Rule
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
            || ! in_array($funcCall = $node->name->toString(), [
                'system', 'exec', 'passthru', 'shell_exec',
            ])) {
            // If it's not a blacklisted function call, no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                sprintf(
                    "Function call %s with request data exposes your application to command injection attacks.",
                    $funcCall
                )
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
        if ($node instanceof FuncCall
            && $node->name instanceof Node\Name
            && in_array($node->name->toString(), ['escapeshellarg', 'escapeshellcmd'])) {
            // If the code has a function call to escape commands/args, stop here and return. It's safe!
            return null;
        }

        return ($node instanceof Expr) &&
            ($this->isRequestData($node, $scope) || $this->isRequestArrayData($node, $scope));
    }
}
