<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor;

trait UseIntercept {

    /**
     * @var \Cwola\MethodInterceptor\Contracts\Visitor[]
     */
    protected static array $__staticInterceptors = [];

    /**
     * @var \Cwola\MethodInterceptor\Contracts\Visitor[]
     */
    protected array $__instanceInterceptors = [];


    /**
     * @param \Cwola\MethodInterceptor\Contracts\Visitor $interceptor
     * @param \Cwola\MethodInterceptor\Contracts\Filter $filter [optional]
     * @return void
     */
    public static function __addStaticInterceptor(Contracts\Visitor $interceptor, Contracts\Filter $filter = null) :void {
        static::$__staticInterceptors[] = [
            'interceptor' => $interceptor,
            'filter' => $filter ?? new Filter\Accept
        ];
    }

    /**
     * @param \Cwola\MethodInterceptor\Contracts\Visitor $interceptor
     * @param \Cwola\MethodInterceptor\Contracts\Filter $filter [optional]
     * @return void
     */
    public function __addInstanceInterceptor(Contracts\Visitor $interceptor, Contracts\Filter $filter = null) :void {
        $this->__instanceInterceptors[] = [
            'interceptor' => $interceptor,
            'filter' => $filter ?? new Filter\Accept
        ];
    }

    /**
     * @param \Cwola\MethodInterceptor\Contracts\Visitor $interceptor
     * @param \Cwola\MethodInterceptor\Contracts\Filter $filter [optional]
     * @return static
     */
    public function __addInterceptor(Contracts\Visitor $interceptor, Contracts\Filter $filter = null) :static {
        static::__addStaticInterceptor($interceptor, $filter);
        $this->__addInstanceInterceptor($interceptor, $filter);
        return $this;
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public static function __onEnterStaticMethod(string $name, ...$args) :void {
        foreach (static::$__staticInterceptors as $interceptor) {
            if ($interceptor['filter']->test($name, ...$args)) {
                $interceptor['interceptor']->enterMethod($name, ...$args);
            }
        }
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public static function __onLeaveStaticMethod(string $name, ...$args) :void {
        foreach (static::$__staticInterceptors as $interceptor) {
            if ($interceptor['filter']->test($name, ...$args)) {
                $interceptor['interceptor']->leaveMethod($name, ...$args);
            }
        }
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public function __onEnterInstanceMethod(string $name, ...$args) :void {
        foreach ($this->__instanceInterceptors as $interceptor) {
            if ($interceptor['filter']->test($name, ...$args)) {
                $interceptor['interceptor']->enterMethod($name, ...$args);
            }
        }
    }

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public function __onLeaveInstanceMethod(string $name, ...$args) :void {
        foreach ($this->__instanceInterceptors as $interceptor) {
            if ($interceptor['filter']->test($name, ...$args)) {
                $interceptor['interceptor']->leaveMethod($name, ...$args);
            }
        }
    }
}
