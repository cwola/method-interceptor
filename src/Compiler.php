<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

use ReflectionClass;
use ReflectionMethod;
use LogicException;
use PhpParser\Node;
use PhpParser\BuilderFactory;
use PhpParser\Builder;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

class Compiler {

    /**
     * @var \Cwola\Interceptor\Interceptable
     */
    protected Interceptable $class;

    /**
     * @var \PhpParser\BuilderFactory
     */
    protected BuilderFactory $factory;

    /**
     * @var \PhpParser\PrettyPrinterAbstract
     */
    protected \PhpParser\PrettyPrinterAbstract $printer;


    /**
     * @param \Cwola\Interceptor\Interceptable $class
     */
    public function __construct(Interceptable $class) {
        $this->class = $class;
        $this->factory = new BuilderFactory;
        $this->printer = new PrettyPrinter;
    }

    /**
     * @param void
     * @return string|null
     */
    public function compile() :string|null {
        $reflection = new ReflectionClass($this->class);
        foreach ($reflection->getAttributes(Intercepted::class) as $attr) {
            return null;
        }
        if ($reflection->isFinal()) {
            throw new LogicException('');
        }

        $classPath = $this->getClassPath();
        $newClassName = $classPath['className'] . '_' . $this->signature();

        $classBuilder = $this->createClassBuilder($newClassName, $classPath['className']);
        $this->appendClassMethods($reflection, $classBuilder);


        $stmts = [];
        if (\strlen($classPath['namespace']) > 0) {
            $stmts[] = $this->factory->namespace($classPath['namespace'])->getNode();
        }
        $stmts[] = $classBuilder->getNode();
        $source = $this->toSourceString($stmts);

        $path = __DIR__ . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $newClassName . '.t.php';
        \file_put_contents(
            $path,
            $source
        );
        \register_shutdown_function(function() use ($path) {
            @\unlink($path);
        });
        require $path;
        return $newClassName;
    }

    /**
     * @param void
     * @return string[]
     */
    protected function getClassPath() :array {
        $p = \explode('\\', $this->class::class);
        $className = \array_pop($p);
        $namespace = empty($p) ? '' : \implode('\\', $p);
        return [
            'className' => $className,
            'namespace' => $namespace
        ];
    }

    /**
     * @param void
     * @return string
     */
    protected function signature() :string {
        $classPath = $this->getClassPath();
        $hash = \md5(\uniqid($classPath['className'] . $classPath['namespace'], true));
        return $hash;
    }

    /**
     * @param string $name
     * @param string $parent
     * @return \PhpParser\Builder\Class_
     */
    protected function createClassBuilder(string $name, string $parent) :Builder\Class_ {
        $class = $this->factory->class($name)
                        ->extend($parent)
                        ->addAttribute(
                            $this->factory->attribute(Intercepted::class)
                        );
        return $class;
    }

    /**
     * @param string $name
     * @return \PhpParser\Builder\Method
     */
    protected function createMethodBuilder(string $name) :Builder\Method {
        $method = $this->factory->method($name);
        return $method;
    }

    /**
     * @param \ReflectionClass $classRef
     * @param \PhpParser\Builder\Class_ $class
     * @return void
     */
    protected function appendClassMethods(ReflectionClass $classRef, Builder\Class_ $class) :void {
        foreach ($classRef->getMethods() as $methodRef) {
            if (!$this->isInterceptableMethod($methodRef)) {
                continue;
            }
            if ($this->isIgnoreMethod($methodRef)) {
                continue;
            }

            $this->appendClassMethod($methodRef, $class);
        }
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @return bool
     */
    protected function isInterceptableMethod(ReflectionMethod $methodRef) :bool {
        return !(
            $methodRef->name == '__construct'
            || $methodRef->isPrivate()
            || $methodRef->isFinal()
        );
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @return bool
     */
    protected function isIgnoreMethod(ReflectionMethod $methodRef) :bool {
        foreach ($methodRef->getAttributes(DoNotIntercept::class) as $attrRef) {
            if ($attrRef->newInstance() instanceof DoNotIntercept) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Class_ $class
     * @return void
     */
    protected function appendClassMethod(ReflectionMethod $methodRef, Builder\Class_ $class) :void {
        $class->addStmt($this->buildClassMethod($methodRef));
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @return \PhpParser\Builder\Method
     */
    protected function buildClassMethod(ReflectionMethod $methodRef) :Builder\Method {
        $methodBuilder = $this->createMethodBuilder($methodRef->name);
        $this->appendAttributes($methodRef, $methodBuilder);
        $this->appendParams($methodRef, $methodBuilder);
        $this->setMethodSignature($methodRef, $methodBuilder);
        $this->appendMethodStmt($methodRef, $methodBuilder);
        return $methodBuilder;
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     */
    protected function appendAttributes(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        foreach ($methodRef->getAttributes() as $attrRef) {
            $methodBuilder->addAttribute(
                $this->factory->attribute(
                    $attrRef->getName(),
                    $attrRef->getArguments()
                )
            );
        }
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     */
    protected function appendParams(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        foreach ($methodRef->getParameters() as $paramRef) {
            $param = $this->factory->param($paramRef->name);

            if ($paramRef->isDefaultValueAvailable()) {
                $param->setDefault($paramRef->getDefaultValue());
            } else if ($paramRef->isOptional()) {
                $param->setDefault(null);
            }

            if ($paramRef->isPassedByReference()) {
                $param->makeByRef();
            }

            if ($paramRef->isVariadic()) {
                $param->makeVariadic();
            }
            $methodBuilder->addParam($param);
        }
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     */
    protected function setMethodSignature(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        if ($methodRef->isStatic()) {
            $methodBuilder->makeStatic();
        }
        if ($methodRef->isPublic()) {
            $methodBuilder->makePublic();
        } else if ($methodRef->isProtected()) {
            $methodBuilder->makeProtected();
        } else if ($methodRef->isPrivate()) {
            $methodBuilder->makePrivate();
        }
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     * @throws \LogicException
     */
    protected function appendMethodStmt(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        // STATIC.
        if ($methodRef->isStatic()) {
            $this->appendStaticInterceptStmt($methodRef, $methodBuilder);
        }
        // INSTANCE.
        else if ($methodRef->isPublic()) {
            $this->appendPublicInterceptStmt($methodRef, $methodBuilder);
        } else if ($methodRef->isProtected()) {
            $this->appendProtectedInterceptStmt($methodRef, $methodBuilder);
        } else if ($methodRef->isPrivate()) {
            /* not reached */
            $this->appendPrivateInterceptStmt($methodRef, $methodBuilder);
        }
        throw new LogicException('');
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     */
    protected function appendStaticInterceptStmt(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        $args = $this->collectArgs($methodBuilder);
        $parentMethodCaller = $this->staticCall('parent', $methodRef->name, $args);

        $interceptors = $this->staticPropertyFetch('static', '__staticInterceptors');
        $onEnter = $this->onEnterStmt(
            $interceptors,
            [
                new Node\Arg(new Node\Scalar\String_($methodRef->name)),
                ...$args
            ]
        );
        $onLeave = $this->onLeaveStmt(
            $interceptors,
            [
                new Node\Arg(new Node\Scalar\String_($methodRef->name)),
                ...$args
            ]
        );

        $this->appendInterceptStmt(
            $methodRef, $methodBuilder, $parentMethodCaller, $onEnter, $onLeave
        );
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     */
    protected function appendPublicInterceptStmt(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        $args = $this->collectArgs($methodBuilder);
        $parentMethodCaller = $this->staticCall('parent', $methodRef->name, $args);

        $interceptors = $this->propertyFetch('this', '__instanceInterceptors');
        $onEnter = $this->onEnterStmt(
            $interceptors,
            [
                new Node\Arg(new Node\Scalar\String_($methodRef->name)),
                ...$args
            ]
        );
        $onLeave = $this->onLeaveStmt(
            $interceptors,
            [
                new Node\Arg(new Node\Scalar\String_($methodRef->name)),
                ...$args
            ]
        );

        $this->appendInterceptStmt(
            $methodRef, $methodBuilder, $parentMethodCaller, $onEnter, $onLeave
        );
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     */
    protected function appendProtectedInterceptStmt(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        $this->appendPublicInterceptStmt($methodRef, $methodBuilder);
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return void
     */
    protected function appendPrivateInterceptStmt(ReflectionMethod $methodRef, Builder\Method $methodBuilder) :void {
        // STMT : $reflection = new \ReflectionClass($this);
        $methodBuilder->addStmt(new Node\Stmt\Expression(
            new Node\Expr\Assign(
                $this->factory->var('reflection'),
                $this->factory->new(
                    '\\ReflectionClass',
                    [new Node\Arg(new Node\Expr\Variable('this'))]
                )
            )
        ))
        // STMT : $parent = $reflection->getParentClass();
        ->addStmt(new Node\Stmt\Expression(
            new Node\Expr\Assign(
                $this->factory->var('parent'),
                $this->factory->methodCall(
                    $this->factory->var('reflection'),
                    'getParentClass'
                ),
            )
        ))
        // STMT : $method = $parent->getMethod({$method->name});
        ->addStmt(new Node\Stmt\Expression(
            new Node\Expr\Assign(
                $this->factory->var('method'),
                $this->factory->methodCall(
                $this->factory->var('parent'),
                    'getMethod',
                    [new Node\Arg(new Node\Scalar\String_($methodRef->name))]
                )
            )
        ))
        // STMT : $method->setAccessible(true);
        ->addStmt(new Node\Stmt\Expression(
            $this->factory->methodCall(
                $this->factory->var('method'),
                'setAccessible',
                [$this->factory->constFetch('true')]
            )
        ));

        $args = $this->collectArgs($methodBuilder);
        $parentMethodCaller = $this->methodCall(
            'method',
            'invoke', 
            [
                new Node\Arg($this->factory->var('parent')),
                ...$args
            ]
        );

        $interceptors = $this->propertyFetch('this', '__instanceInterceptors');
        $onEnter = $this->onEnterStmt(
            $interceptors,
            [
                new Node\Arg(new Node\Scalar\String_($methodRef->name)),
                ...$args
            ]
        );
        $onLeave = $this->onLeaveStmt(
            $interceptors,
            [
                new Node\Arg(new Node\Scalar\String_($methodRef->name)),
                ...$args
            ]
        );

        $this->appendInterceptStmt(
            $methodRef, $methodBuilder, $parentMethodCaller, $onEnter, $onLeave
        );
    }

    /**
     * @param \ReflectionMethod $methodRef
     * @param \PhpParser\Builder\Method $methodBuilder
     * @param \PhpParser\Node\Expr $parentMethodCaller
     * @param \PhpParser\Node\Stmt $onEnter
     * @param \PhpParser\Node\Stmt $onLeave
     * @return void
     */
    protected function appendInterceptStmt(
        ReflectionMethod $methodRef,
        Builder\Method $methodBuilder,
        Node\Expr $parentMethodCaller,
        Node\Stmt $onEnter,
        Node\Stmt $onLeave
    ) : void {
        $methodBuilder
            // STMT :   foreach ((static::$__staticInterceptors|$this->__instanceInterceptors) as $interceptor) {
            //              $interceptor->enterMethod({$this->name}, ...$args);
            //          }
            ->addStmt($onEnter)
            // STMT :   try {
            //              $res = ($method->invoke($this, ...$args)|parent::{$method->name}(...$args));
            //          } catch (\Throwable $e) {
            //              throw $e;
            //          } finally {
            //              foreach ((static::$__staticInterceptors|$this->__instanceInterceptors) as $interceptor) {
            //                  $interceptor->leaveMethod({$this->name}, ...$args);
            //              }
            //          }
            ->addStmt(new Node\Stmt\TryCatch(
                [new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->factory->var('res'),
                        $parentMethodCaller
                    )
                )],
                [new Node\Stmt\Catch_(
                    [new Node\Name\FullyQualified('Throwable')],
                    $this->factory->var('e'),
                    [new Node\Stmt\Throw_($this->factory->var('e'))]
                )],
                new Node\Stmt\Finally_([$onLeave])
            ))
        ;
        if (!$methodRef->hasReturnType() || (string)$methodRef->getReturnType() !== 'void') {
            // STMT : return $res;
            $methodBuilder->addStmt(new Node\Stmt\Return_(
                $this->factory->var('res')
            ));
        }
    }

    /**
     * @param string $class
     * @param string $property
     * @return Node\Expr\StaticPropertyFetch
     */
    protected function staticPropertyFetch(string $class, string $property) :Node\Expr\StaticPropertyFetch {
        return new Node\Expr\StaticPropertyFetch(
            new Node\Name($class),
            new Node\VarLikeIdentifier($property)
        );
    }

    /**
     * @param string $instance
     * @param string $property
     * @return Node\Expr\PropertyFetch
     */
    protected function propertyFetch(string $instance, string $name) :Node\Expr\PropertyFetch {
        return $this->factory->propertyFetch(
            $this->factory->var($instance),
            $name
        );
    }

    /**
     * @param \PhpParser\Builder\Method $methodBuilder
     * @return \PhpParser\Node\Arg[]
     */
    protected function collectArgs(Builder\Method $methodBuilder) :array {
        $args = [];
        foreach ($methodBuilder->getNode()->getParams() as $param) {
            $args[] = new Node\Arg($param->var);
        }
        return $args;
    }

    /**
     * @param string $class
     * @param string $methodName
     * @param \PhpParser\Node\Arg[] $args
     * @return \PhpParser\Node\Expr\StaticCall
     */
    protected function staticCall(string $class, string $methodName, array $args) :Node\Expr\StaticCall {
        return $this->factory->staticCall(
            $class,
            $methodName,
            $args
        );
    }

    /**
     * @param string $instance
     * @param string $methodName
     * @param \PhpParser\Node\Arg[] $args
     * @return \PhpParser\Node\Expr\MethodCall
     */
    protected function methodCall(string $instance, string $methodName, array $args) :Node\Expr\MethodCall {
        return $this->factory->methodCall(
            $this->factory->var($instance),
            $methodName,
            $args
        );
    }

    /**
     * @param \PhpParser\Node\Expr $iterator
     * @param string $as
     * @param \PhpParser\Node\Stmt[] $stmts
     * @return \PhpParser\Node\Stmt\Foreach_
     */
    protected function each(Node\Expr $iterator, string $as, array $stmts) :Node\Stmt\Foreach_ {
        return new Node\Stmt\Foreach_(
            $iterator,
            $this->factory->var($as),
            ['stmts' => $stmts]
        );
    }

    /**
     * @param \PhpParser\Node\Expr $interceptors
     * @param \PhpParser\Node\Arg[] $args
     * @return \PhpParser\Node\Stmt
     */
    protected function onEnterStmt(Node\Expr $interceptors, array $args) :Node\Stmt {
        return $this->each(
            $interceptors,
            'interceptor',
            [new Node\Stmt\Expression(
                $this->methodCall(
                    'interceptor',
                    'enterMethod',
                    $args
                )
            )]
        );
    }

    /**
     * @param \PhpParser\Node\Expr $interceptors
     * @param \PhpParser\Node\Arg[] $args
     * @return \PhpParser\Node\Stmt
     */
    protected function onLeaveStmt(Node\Expr $interceptors, array $args) :Node\Stmt {
        return $this->each(
            $interceptors,
            'interceptor',
            [new Node\Stmt\Expression(
                $this->methodCall(
                    'interceptor',
                    'leaveMethod',
                    $args
                )
            )]
        );
    }

    /**
     * @param \PhpParser\Node\Stmt[] $stmts
     * @return string
     */
    protected function toSourceString(array $stmts) :string {
        return $this->printer->prettyPrintFile($stmts);
    }
}
