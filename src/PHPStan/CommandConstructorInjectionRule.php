<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Illuminate\Console\Command;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class CommandConstructorInjectionRule implements Rule
{
    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier
            || $node->name->toString() !== '__construct') {
            return [];
        }

        if (is_array($node->params) && count($node->params) > 0
            && is_subclass_of($class = $scope->getClassReflection()->getName(), Command::class)
            && ! isset($class::$defaultName)) {
            // Starting Laravel 9x, we can lazy load commands using the static $defaultName property.
            // This rule only errors out if constructor injection is used in a command that isn't lazy loaded.
            return [
                sprintf(
                    "Detected constructor injection in non-lazy loaded command %s.",
                    $class
                )
            ];
        }

        return [];
    }
}
