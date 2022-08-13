<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

use LogicException;
use Cwola\Interceptor\Compiler\Handler as Compiler;

trait UseIntercept {

    /**
     * @var \Cwola\Interceptor\Visitor\Interceptor[]
     */
    protected static array $__staticInterceptors = [];

    /**
     * @var \Cwola\Interceptor\Visitor\Interceptor[]
     */
    protected array $__instanceInterceptors = [];


    /**
     * @param \Cwola\Interceptor\Visitor\Interceptor $interceptor
     * @return void
     */
    #[Attribute\DoNotIntercept]
    public static function __addStaticInterceptor(Visitor\Interceptor $interceptor) :void {
        static::$__staticInterceptors[] = $interceptor;
    }

    /**
     * @param \Cwola\Interceptor\Visitor\Interceptor $interceptor
     * @return static
     */
    #[Attribute\DoNotIntercept]
    public function __addInstanceInterceptor(Visitor\Interceptor $interceptor) :static {
        $this->__instanceInterceptors[] = $interceptor;
        return $this;
    }

    /**
     * @param \Cwola\Interceptor\Visitor\Interceptor $interceptor
     * @return static
     */
    #[Attribute\DoNotIntercept]
    public function __addInterceptor(Visitor\Interceptor $interceptor) :static {
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
    #[Attribute\DoNotIntercept]
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
    #[Attribute\DoNotIntercept]
    protected function __transformInstanceIntoInterceptable() :string|null {
        $compiler = new Compiler($this);
        return $compiler->compile();
    }
}
