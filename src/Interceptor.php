<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

interface Interceptor {

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public function __enterMethod(string $name, ...$args) :void;

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public function __leaveMethod(string $name, ...$args) :void;
}
