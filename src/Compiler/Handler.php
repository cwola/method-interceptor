<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor\Compiler;

use Exception;
use RuntimeException;
use PhpParser\ParserFactory;
use PhpParser\Parser;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeFinder;
use PhpParser\BuilderFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use Cwola\MethodInterceptor\Attribute;


class Handler {

    /**
     * @var string
     */
    protected string $source;

    /**
     * @var string
     */
    protected string $error;

    /**
     * @var \PhpParser\BuilderFactory
     */
    protected BuilderFactory $factory;


    /**
     * @param string $source
     */
    public function __construct(string $source) {
        $this->source = $source;
        $this->error = '';
        $this->factory = new BuilderFactory;
    }

    /**
     * @param void
     * @return string
     */
    public function getError() :string {
        return $this->error;
    }

    /**
     * @param void
     * @return string|false
     */
    public function compile() :string|false {
        $this->error = '';
        if (($parser = $this->createParser()) === false) {
            return false;
        }
        if (($ast = $this->parse($parser)) === false) {
            return false;
        }
        $targetClasses = $this->findByStatement($ast, [$this, 'isInterceptTargetClass']);
        if (\count($targetClasses) < 1) {
            return $this->source;
        }
        foreach ($targetClasses as $class) {
            if (!$this->applyIntercept($class)) {
                return false;
            }
        }
        return (new PrettyPrinter)->prettyPrintFile($ast);
    }

    /**
     * @param void
     * @return \PhpParser\Parser|false
     */
    protected function createParser() :Parser|false {
        try {
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        } catch (Exception $e) {
            $this->error = $e::class . ' : ' . $e->getMessage();
            return false;
        }
        return $parser;
    }

    /**
     * @param \PhpParser\Parser $parser
     * @return \PhpParser\Node\Stmt[]|false
     */
    protected function parse(Parser $parser) :array|false {
        try {
            $ast = $parser->parse($this->source);
            if ($ast === null) {
                throw new RuntimeException('failed parse.');
            }
            $traverser = new NodeTraverser;
            $traverser->addVisitor(new NameResolver);
            $ast = $traverser->traverse($ast);
        } catch (Exception $e) {
            $this->error = $e::class . ' : ' . $e->getMessage();
            return false;
        }
        return $ast;
    }

    /**
     * @param \PhpParser\Node\Stmt[]|\PhpParser\Node\Stmt $stmt
     * @param callable $filter
     * @return \PhpParser\Node[]
     */
    protected function findByStatement(array|Node\Stmt $stmt, callable $filter) :array {
        $nodeFinder = new NodeFinder;
        return $nodeFinder->find($stmt, $filter);
    }

    /**
     * @param \PhpParser\Node $node
     * @return bool
     */
    public function isInterceptTargetClass(Node $node) :bool {
        return $node instanceof Node\Stmt\Class_
                && !empty($this->findByStatement($node, [$this, 'isInterceptAttribute']))
        ;
    }

    /**
     * @param \PhpParser\Node $node
     * @return bool
     */
    public function isInterceptTargetClassMethod(Node $node) :bool {
        return $node instanceof Node\Stmt\ClassMethod
                && $node->name->toString() !== '__construct'
                && empty($this->findByStatement($node, [$this, 'isDoNotInterceptAttribute']))
        ;
    }

    /**
     * @param \PhpParser\Node $node
     * @return bool
     */
    public function isInterceptAttribute(Node $node) :bool {
        return $node instanceof Node\Attribute
                && $node->name->toString() === Attribute\Interceptable::class
        ;
    }

    /**
     * @param \PhpParser\Node $node
     * @return bool
     */
    public function isDoNotInterceptAttribute(Node $node) :bool {
        return $node instanceof Node\Attribute
                && $node->name->toString() === Attribute\DoNotIntercept::class
        ;
    }

    /**
     * @param \PhpParser\Node\Stmt\Class_ $class
     * @return bool
     */
    protected function applyIntercept(Node\Stmt\Class_ $class) :bool {
        $this->applyMethodIntercept($class);
        return true;
    }

    /**
     * @param \PhpParser\Node\Stmt\Class_ $class
     * @return bool
     */
    protected function applyMethodIntercept(Node\Stmt\Class_ $class) :bool {
        /**
         * @var \PhpParser\Node\Stmt\ClassMethod $method
         */
        foreach ($this->findByStatement($class, [$this, 'isInterceptTargetClassMethod']) as $method) {
            if ($method->isStatic()) {
                $this->appendStaticInterceptStmt($method);
            } else {
                $this->appendInstanceInterceptStmt($method);
            }
        }
        return true;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $method
     * @return void
     */
    protected function appendStaticInterceptStmt(Node\Stmt\ClassMethod $method) :void {
        $args = [$method->name->toString(), ...$this->collectArgs($method)];
        $onEnter = $this->staticCall('static', '__onEnterStaticMethod', $args);
        $onLeave = $this->staticCall('static', '__onLeaveStaticMethod', $args);
        $this->appendInterceptStmt($method, $onEnter, $onLeave);
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $method
     * @return void
     */
    protected function appendInstanceInterceptStmt(Node\Stmt\ClassMethod $method) :void {
        $args = [$method->name->toString(), ...$this->collectArgs($method)];
        $onEnter = $this->methodCall('this', '__onEnterInstanceMethod', $args);
        $onLeave = $this->methodCall('this', '__onLeaveInstanceMethod', $args);
        $this->appendInterceptStmt($method, $onEnter, $onLeave);
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $method
     * @param \PhpParser\Node\Stmt\Expression $onEnter
     * @param \PhpParser\Node\Stmt\Expression $onLeave
     * @return void
     */
    protected function appendInterceptStmt(
        Node\Stmt\ClassMethod $method,
        Node\Stmt\Expression $onEnter,
        Node\Stmt\Expression $onLeave
    ) : void {
        $newStmts = [$onEnter];
        foreach ($method->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_) {
                $newStmts[] = $onLeave;
            }
            $newStmts[] = $stmt;
        }
        if (!($newStmts[\count($newStmts) - 1] instanceof Node\Stmt\Return_)) {
            $newStmts[] = $onLeave;
        }
        $method->stmts = $newStmts;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $method
     * @return \PhpParser\Node\Arg[]
     */
    protected function collectArgs(Node\Stmt\ClassMethod $method) :array {
        $args = [];
        foreach ($method->getParams() as $param) {
            $args[] = new Node\Arg($param->var);
        }
        return $args;
    }

    /**
     * @param string $class
     * @param string $methodName
     * @param \PhpParser\Node\Arg[] $args
     * @return \PhpParser\Node\Stmt\Expression
     */
    protected function staticCall(string $class, string $methodName, array $args) :Node\Stmt\Expression {
        return new Node\Stmt\Expression($this->factory->staticCall(
            $class,
            $methodName,
            $args
        ));
    }

    /**
     * @param string $instance
     * @param string $methodName
     * @param \PhpParser\Node\Arg[] $args
     * @return \PhpParser\Node\Stmt\Expression
     */
    protected function methodCall(string $instance, string $methodName, array $args) :Node\Stmt\Expression {
        return new Node\Stmt\Expression($this->factory->methodCall(
            $this->factory->var($instance),
            $methodName,
            $args
        ));
    }
}
