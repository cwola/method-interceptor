<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

abstract class Interceptor {

    /**
     * @param string $name
     * @param mixed ...$args
     * @return void
     */
    abstract public function enterMethod(string $name, mixed ...$args) :void;

    /**
     * @param string $name
     * @param mixed ...$args
     * @return void
     */
    abstract public function leaveMethod(string $name, mixed ...$args) :void;
}
