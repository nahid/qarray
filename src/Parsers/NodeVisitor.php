<?php

declare(strict_types=1);

namespace Nahid\QArray\Parsers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class NodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected mixed $output = null;

    protected string $func;

    public function __construct(string $func)
    {
        $this->func = $func;
    }

    /**
     * @param array<Node\Stmt\Expression> $nodes
     */
    public function beforeTraverse(array $nodes)
    {
//        dump($nodes);
        foreach ($nodes as $node) {
            if ($node->expr instanceof Node\Expr\FuncCall) {
                $this->output = $this->executeFunction($node->expr);

                return null;
            }
        }

        return null;
    }

    private function executeFunction(Node\Expr\FuncCall $node)
    {
        /**
         * @var Node\Name $name
         */
        $functionName = $node->name->toString();
        $args = [];

        /**
         * @var Node\Arg $arg
         */
        foreach ($node->args as $arg) {
            $args[] = $this->resolveArgument($arg->value);
        }

        // Dynamically call the method
        if (method_exists($this->func, $functionName)) {
            if ($functionName === 'column') {
                $args[] = $this->data;
            }

            $result = call_user_func_array([$this->func, $functionName], $args);
            return $result;
        } else {
            throw new \Exception("Method $functionName does not exist in MyClass");
        }
    }

    private function resolveArgument(Node $value): mixed
    {
        if ($value instanceof Node\Scalar\String_) {
            return $value->value;
        } elseif($value instanceof Node\Scalar\Int_) {
            return (int) $value->value;
        } elseif($value instanceof Node\Scalar\Float_) {
            return (float)$value->value;
        } elseif ($value instanceof Node\Expr\BinaryOp) {
            return $this->evaluateExpression($value);
        } elseif ($value instanceof Node\Expr\ConstFetch) {
            if (array_key_exists($value->name->toString(), $this->getData())) {
                return $this->data[$value->name->toString()];
            } else {
                throw new \Exception("Constant " . $value->name->toString() . " is not defined");
            }
        } elseif ($value instanceof Node\Expr\FuncCall) {
            // Recursively resolve nested function calls
            return $this->executeFunction($value);
        } else {
            throw new \Exception("Unsupported argument type: " . get_class($value));
        }
    }

    private function evaluateExpression(Node\Expr\BinaryOp $node): int|float
    {
        $left = $this->resolveBinaryOpValue($node->left);
        $right = $this->resolveBinaryOpValue($node->right);

        switch (get_class($node)) {
            case Node\Expr\BinaryOp\Plus::class:
                return $left + $right;
            case Node\Expr\BinaryOp\Minus::class:
                return $left - $right;
            case Node\Expr\BinaryOp\Mul::class:
                return $left * $right;
            case Node\Expr\BinaryOp\Div::class:
                return $left / $right;
            case Node\Expr\BinaryOp\Mod::class:
                return $left % $right;
            case Node\Expr\BinaryOp\Pow::class:
                return $left ** $right;
            default:
                throw new \Exception("Unsupported operation: " . get_class($node));
        }
    }

    private function resolveBinaryOpValue(Node $node): int|float
    {
        if ($node instanceof Node\Scalar\LNumber) {
            return $node->value;
        } elseif ($node instanceof Node\Scalar\DNumber) {
            return $node->value;
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            if (array_key_exists($node->name->toString(), $this->getData())) {
                return $this->data[$node->name->toString()];
            } else {
                throw new \Exception("Constant " . $node->name->toString() . " is not defined");
            }
        } elseif ($node instanceof Node\Expr\BinaryOp) {
            return $this->evaluateExpression($node);
        } else {
            throw new \Exception("Unsupported node type: " . get_class($node));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getOutput(): mixed
    {
        return $this->output;
    }
}
