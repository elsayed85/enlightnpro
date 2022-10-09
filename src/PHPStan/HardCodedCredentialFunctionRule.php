<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class HardCodedCredentialFunctionRule implements Rule
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
            || ! in_array($funcName = $node->name->toString(), [
                'bcrypt', 'crypt', 'password_hash', 'md5', 'sha1',
                'hash', 'hash_hmac',
            ])) {
            // If it's not a blacklisted function call, no errors.
            return [];
        }

        if (! in_array($node->name->toString(), ['hash', 'hash_hmac']) && isset($node->args[0])
            && $this->isString($node->args[0]->value)) {
            return ["Function call {$funcName} with hard coded credentials in your source code."];
        }

        if (in_array($node->name->toString(), ['hash', 'hash_hmac']) && isset($node->args[1])
            && $this->isString($node->args[1]->value)) {
            return ["Function call {$funcName} with hard coded credentials in your source code."];
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
