<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

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
     * @return bool
     */
    #[Attribute\DoNotIntercept]
    public function __enableIntercept() :bool {
        $this->__interceptable = true;
        return true;
    }

    /**
     * @param void
     * @return bool
     */
    #[Attribute\DoNotIntercept]
    public function __disableIntercept() :bool {
        $this->__interceptable = false;
        return true;
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    #[Attribute\DoNotIntercept]
    protected static function __onEnterStaticMethod(string $name, ...$args) :void {
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
    protected static function __onLeaveStaticMethod(string $name, ...$args) :void {
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
    protected function __onEnterInstanceMethod(string $name, ...$args) :void {
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
    protected function __onLeaveInstanceMethod(string $name, ...$args) :void {
        foreach ($this->__instanceInterceptors as $interceptor) {
            $interceptor->leaveMethod($name, ...$args);
        }
    }
}
