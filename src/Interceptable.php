<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

use ReflectionClass;
use LogicException;
use PhpParser\Node;
use PhpParser\BuilderFactory;

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
    public static function __registerStaticInterceptor(Interceptor $interceptor) :void {
        static::$__staticInterceptors[] = $interceptor;
    }

    /**
     * @param \Cwola\Interceptor\Interceptor $interceptor
     * @return static
     */
    #[DoNotIntercept]
    public function __registerInstanceInterceptor(Interceptor $interceptor) :static {
        $this->__instanceInterceptors[] = $interceptor;
        return $this;
    }

    /**
     * @param \Cwola\Interceptor\Interceptor $interceptor
     * @return static
     */
    #[DoNotIntercept]
    public function __registerInterceptor(Interceptor $interceptor) :static {
        static::__registerStaticInterceptor($interceptor);
        $this->__registerInstanceInterceptor($interceptor);
        return $this;
    }

    /**
     * @param void
     * @return static
     *
     * @throws \LogicException
     */
    #[DoNotIntercept]
    protected function __transformInstanceIntoInterceptable() :static {
        $reflection = new ReflectionClass($this);
        if ($reflection->isFinal()) {
            throw new LogicException('');
        }

        $uid = \md5(\uniqid(static::class, true));
        $factory = new BuilderFactory;
        $class = $factory->class(static::class . $uid)
                ->extend(static::class);

        foreach ($reflection->getMethods() as $method) {
            if ($method->isFinal()) {
                throw new LogicException('');
            }
            foreach ($method->getAttributes(DoNotIntercept::class) as $attr) {
                if ($attr->newInstance() instanceof DoNotIntercept) {
                    continue;
                }
            }

            $methodStmt = $factory->method($method->name)
                            ->addParam($factory->param('args')->makeVariadic());

            $isStatic = $method->isStatic();
            $isPrivate = $method->isPrivate();
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
                        new Node\Expr\Variable('reflection'),
                        new Node\Expr\New_(
                            new Node\Name\FullyQualified('ReflectionClass'),
                            [new Node\Arg(new Node\Expr\Variable('this'))]
                        )
                    )
                ));
                // STMT :
            }
        }
    }
}
