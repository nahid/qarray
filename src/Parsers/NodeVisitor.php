<?php

namespace Nahid\QArray\Parsers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Nahid\QArray\Func;

class NodeVisitor extends NodeVisitorAbstract
{
    protected array $data = [];

    protected mixed $output = null;
    public function enterNode(Node $node)
    {
        /*if ($node instanceof Node\Expr\FuncCall) {
            $this->output[] = $this->executeFunction($node);
        }*/
    }

    /**
     * @param array<Node\Stmt\Expression> $nodes
     */
    public function afterTraverse(array $nodes)
    {

        foreach ($nodes as $node) {
            if ($node->expr instanceof Node\Expr\FuncCall) {
                $this->output = $this->executeFunction($node->expr);

                return;
            }
        }
    }

    private function executeFunction(Node\Expr\FuncCall $node)
    {
        $functionName = $node->name->toString(); // e.g., "func1" or "func2"
        $args = [];

        // Evaluate each argument
        foreach ($node->args as $arg) {
            $args[] = $this->resolveArgument($arg->value);
        }

        // Dynamically call the method
        if (method_exists(Func::class, $functionName)) {
            if ($functionName === 'column') {
                $args[] = $this->data;
            }

            $result = call_user_func_array([Func::class, $functionName], $args);
            return $result;
        } else {
            throw new \Exception("Method $functionName does not exist in MyClass");
        }
    }

    private function resolveArgument(Node $value)
    {
        if ($value instanceof Node\Scalar\String_) {
            return $value->value;
        } elseif($value instanceof Node\Scalar\Int_) {
            return (int) $value->value;
        } elseif($value instanceof Node\Scalar\Float_) {
            return (float) $value->value;
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

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getOutput(): mixed
    {
        return $this->output;
    }
}
