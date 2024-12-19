<?php

declare(strict_types=1);

namespace Nahid\QArray\Parsers;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

class AstParser
{
    protected NodeVisitorAbstract $visitor;

    /**
     * @param NodeVisitorAbstract $visitor
     * @param array<string, mixed> $data
     */
    public function __construct(NodeVisitor $visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * @param string $expression
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function execute(string $expression, array $data): mixed
    {
        $this->visitor->setData($data);

        $codeWithPhp = "<?php $expression;";
        try {
            $parser = (new ParserFactory())->createForVersion(PhpVersion::fromString('8.0'));
            $ast = $parser->parse($codeWithPhp);

            $traverser = new NodeTraverser();

            $traverser->addVisitor($this->visitor);
            $traverser->traverse($ast);

            return $this->visitor->getOutput();
        } catch (\Exception $e) {
            return $expression;
        }

    }

    public static function isValidFunctionCall(mixed $input): bool
    {
        if (!is_string($input)) {
            return false;
        }
        // Regular expression to match PHP function call syntax
        $pattern = '/^[a-zA-Z_][a-zA-Z0-9_]*\s*\((.*)\)$/';

        // Check if the string matches the pattern
        return preg_match($pattern, $input) === 1;
    }

}
