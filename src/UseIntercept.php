<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor;

trait UseIntercept {

    /**
     * @var \Cwola\MethodInterceptor\Visitor\Interceptor[]
     */
    protected static array $__staticInterceptors = [];

    /**
     * @var \Cwola\MethodInterceptor\Visitor\Interceptor[]
     */
    protected array $__instanceInterceptors = [];


    /**
     * @param \Cwola\MethodInterceptor\Visitor\Interceptor $interceptor
     * @return void
     */
    #[Attribute\DoNotIntercept]
    public static function __addStaticInterceptor(Visitor\Interceptor $interceptor) :void {
        static::$__staticInterceptors[] = $interceptor;
    }

    /**
     * @param \Cwola\MethodInterceptor\Visitor\Interceptor $interceptor
     * @return void
     */
    #[Attribute\DoNotIntercept]
    public function __addInstanceInterceptor(Visitor\Interceptor $interceptor) :void {
        $this->__instanceInterceptors[] = $interceptor;
    }

    /**
     * @param \Cwola\MethodInterceptor\Visitor\Interceptor $interceptor
     * @return static
     */
    #[Attribute\DoNotIntercept]
    public function __addInterceptor(Visitor\Interceptor $interceptor) :static {
        static::__addStaticInterceptor($interceptor);
        $this->__addInstanceInterceptor($interceptor);
        return $this;
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    #[Attribute\DoNotIntercept]
    public static function __onEnterStaticMethod(string $name, ...$args) :void {
        foreach (static::$__staticInterceptors as $interceptor) {
            $interceptor->enterMethod($name, ...$args);
        }
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    #[Attribute\DoNotIntercept]
    public static function __onLeaveStaticMethod(string $name, ...$args) :void {
        foreach (static::$__staticInterceptors as $interceptor) {
            $interceptor->leaveMethod($name, ...$args);
        }
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    #[Attribute\DoNotIntercept]
    public function __onEnterInstanceMethod(string $name, ...$args) :void {
        foreach ($this->__instanceInterceptors as $interceptor) {
            $interceptor->enterMethod($name, ...$args);
        }
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    #[Attribute\DoNotIntercept]
    public function __onLeaveInstanceMethod(string $name, ...$args) :void {
        foreach ($this->__instanceInterceptors as $interceptor) {
            $interceptor->leaveMethod($name, ...$args);
        }
    }
}
