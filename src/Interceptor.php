<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

abstract class Interceptor {

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    abstract public function __enterMethod(string $name, ...$args) :void;

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    abstract public function __leaveMethod(string $name, ...$args) :void;
}
