<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

use LogicException;

trait UseIntercept {

    /**
     * @var \Cwola\Interceptor\Interceptor[]
     */
    protected static array $__staticInterceptors = [];

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
        $compiler = new Compiler($this);
        return $compiler->compile();
    }
}
