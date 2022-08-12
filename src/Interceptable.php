<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

use ReflectionClass;
use LogicException;
use PhpParser\Node;
use PhpParser\BuilderFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

trait Interceptable {

    /**
     * @var \Cwola\Interceptor\Interceptor[]
     */
    protected static $__staticInterceptors = [];

    /**
     * @var \Cwola\Interceptor\Interceptor[]
     */
    protected array $__instanceInterceptors = [];


    /**
     * @param \Cwola\Interceptor\Interceptor $interceptor
     * @return void
     */
    #[DoNotIntercept]
    public static function __addStaticInterceptor(Interceptor $interceptor) :void {
        static::$__staticInterceptors[] = $interceptor;
    }

    /**
     * @param \Cwola\Interceptor\Interceptor $interceptor
     * @return static
     */
    #[DoNotIntercept]
    public function __addInstanceInterceptor(Interceptor $interceptor) :static {
        $this->__instanceInterceptors[] = $interceptor;
        return $this;
    }

    /**
     * @param \Cwola\Interceptor\Interceptor $interceptor
     * @return static
     */
    #[DoNotIntercept]
    public function __addInterceptor(Interceptor $interceptor) :static {
        static::__addStaticInterceptor($interceptor);
        $this->__addInstanceInterceptor($interceptor);
        return $this;
    }

    /**
     * @param void
     * @return string newInstanceName
     *
     * @throws \LogicException
     */
    #[DoNotIntercept]
    public function intercept() :string {
        $new = $this->__transformInstanceIntoInterceptable();
        if ($new === null) {
            throw new LogicException('');
        }
        return $new;
    }

    /**
     * @param void
     * @return string|null
     *
     * @throws \LogicException
     */
    #[DoNotIntercept]
    protected function __transformInstanceIntoInterceptable() :string|null {
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getAttributes(Intercepted::class) as $attr) {
            return null;
        }
        if ($reflection->isFinal()) {
            throw new LogicException('');
        }

        $p = \explode('\\', static::class);
        $className = \array_pop($p);
        $namespace = empty($p) ? '' : \implode('\\', $p);
        $hash = \md5(\uniqid($className . $namespace, true));
        $newClassName = $className . '_' . $hash;
        $factory = new BuilderFactory;
        $class = $factory->class($newClassName)
                ->extend($className)
                ->addAttribute(
                    $factory->attribute(Intercepted::class)
                );

        foreach ($reflection->getMethods() as $method) {
            $isStatic = $method->isStatic();
            $isPrivate = $method->isPrivate();
            if ($method->name === '__construct') {
                continue;
            }
            if ($isPrivate) {
                // private method ...... :(
                continue;
            }
            if ($method->isFinal()) {
                throw new LogicException('');
            }

            $methodStmt = $factory->method($method->name);
            foreach ($method->getAttributes() as $attr) {
                if ($attr->newInstance() instanceof DoNotIntercept) {
                    continue 2;
                }
                $methodStmt->addAttribute(
                    $factory->attribute(
                        $attr->getName(),
                        $attr->getArguments()
                    )
                );
            }

            $methodStmt = $factory->method($method->name);
            foreach ($method->getParameters() as $parameter) {
                $param = $factory->param($parameter->name);
                if ($parameter->isDefaultValueAvailable()) {
                    $param->setDefault($parameter->getDefaultValue());
                } else if ($parameter->isOptional()) {
                    $param->setDefault(null);
                }
                if ($parameter->isPassedByReference()) {
                    $param->makeByRef();
                }
                if ($parameter->isVariadic()) {
                    $param->makeVariadic();
                }
                $methodStmt->addParam($param);
            }

            if ($isStatic) {
                if ($isPrivate) {
                    throw new LogicException('');
                }
                $methodStmt->makeStatic();
            }
            if ($method->isPublic()) {
                $methodStmt->makePublic();
            } else if ($method->isProtected()) {
                $methodStmt->makeProtected();
            } else if ($isPrivate) {
                $methodStmt->makePrivate();
            }

            if ($isPrivate) {
                // STMT : $reflection = new \ReflectionClass($this);
                $methodStmt->addStmt(new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $factory->var('reflection'),
                        $factory->new(
                            '\\ReflectionClass',
                            [new Node\Arg(new Node\Expr\Variable('this'))]
                        )
                    )
                ))
                // STMT : $parent = $reflection->getParentClass();
                ->addStmt(new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $factory->var('parent'),
                        $factory->methodCall(
                            $factory->var('reflection'),
                            'getParentClass'
                        ),
                    )
                ))
                // STMT : $method = $parent->getMethod({$method->name});
                ->addStmt(new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $factory->var('method'),
                        $factory->methodCall(
                            $factory->var('parent'),
                            'getMethod',
                            [new Node\Arg(new Node\Scalar\String_($method->name))]
                        )
                    )
                ))
                // STMT : $method->setAccessible(true);
                ->addStmt(new Node\Stmt\Expression(
                    $factory->methodCall(
                        $factory->var('method'),
                        'setAccessible',
                        [$factory->constFetch('true')]
                    )
                ));
            }

            $interceptors = null;
            // STATIC METHOD
            if ($isStatic) {
                $interceptors = new Node\Expr\StaticPropertyFetch(
                    new Node\Name('static'),
                    new Node\VarLikeIdentifier('__staticInterceptors')
                );
            }
            // INSTANCE METHOD
            else {
                $interceptors = $factory->propertyFetch(
                    $factory->var('this'),
                    '__instanceInterceptors'
                );
            }

            $args = [];
            foreach ($methodStmt->getNode()->getParams() as $param) {
                $args[] = new Node\Arg($param->var);
            }
            $callParentMethod = null;
            if (!$isPrivate) {
                $callParentMethod = $factory->staticCall(
                    'parent',
                    $method->name,
                    $args
                );
            }
            else {
                $callParentMethod = $factory->methodCall(
                    $factory->var('method'),
                    'invoke',
                    [
                        new Node\Arg($factory->var('parent')),
                        ...$args
                    ]
                );
            }
            $onEnter = new Node\Stmt\Foreach_(
                $interceptors,
                $factory->var('interceptor'),
                ['stmts' => [
                    new Node\Stmt\Expression(
                        $factory->methodCall(
                            $factory->var('interceptor'),
                            '__enterMethod',
                            [
                                new Node\Arg(new Node\Scalar\String_($method->name)),
                                ...$args
                            ]
                        )
                    )
                ]]
            );
            $onLeave = new Node\Stmt\Foreach_(
                $interceptors,
                $factory->var('interceptor'),
                ['stmts' => [
                    new Node\Stmt\Expression(
                        $factory->methodCall(
                            $factory->var('interceptor'),
                            '__leaveMethod',
                            [
                                new Node\Arg(new Node\Scalar\String_($method->name)),
                                ...$args
                            ]
                        )
                    )
                ]]
            );

            $methodStmt
                // STMT :   foreach ((static::$__staticInterceptors|$this->__instanceInterceptors) as $interceptor) {
                //              $interceptor->__enterMethod({$this->name}, ...$args);
                //          }
                ->addStmt($onEnter)
                // STMT :   try {
                //              $res = ($method->invoke($this, ...$args)|parent::{$method->name}(...$args));
                //          } catch (\Throwable $e) {
                //              throw $e;
                //          } finally {
                //              foreach ((static::$__staticInterceptors|$this->__instanceInterceptors) as $interceptor) {
                //                  $interceptor->__leaveMethod({$this->name}, ...$args);
                //              }
                //          }
                ->addStmt(new Node\Stmt\TryCatch(
                    [new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            $factory->var('res'),
                            $callParentMethod
                        )
                    )],
                    [new Node\Stmt\Catch_(
                        [new Node\Name\FullyQualified('Throwable')],
                        $factory->var('e'),
                        [new Node\Stmt\Throw_($factory->var('e'))]
                    )],
                    new Node\Stmt\Finally_([$onLeave])
                ))
            ;
            if (!$method->hasReturnType() || (string)$method->getReturnType() !== 'void') {
                // STMT : return $res;
                $methodStmt->addStmt(new Node\Stmt\Return_(
                    $factory->var('res')
                ));
            };

            $class->addStmt($methodStmt);
        }

        $stmts = [];
        if (\strlen($namespace) > 0) {
            $stmts[] = $factory->namespace($namespace)->getNode();
        }
        $stmts[] = $class->getNode();
        $printer = new PrettyPrinter;
        $source = $printer->prettyPrintFile($stmts);
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
}
