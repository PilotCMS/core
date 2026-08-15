<?php

namespace Pilot\Core\GraphQL\Scalars;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\AST;

class Json extends ScalarType
{
    public function serialize(mixed $value): mixed
    {
        return $value;
    }

    public function parseValue(mixed $value): mixed
    {
        return $value;
    }

    /** @param array<string, mixed>|null $variables */
    public function parseLiteral(Node $valueNode, ?array $variables = null): mixed
    {
        return AST::valueFromASTUntyped($valueNode, $variables);
    }
}
