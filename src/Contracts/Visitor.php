<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor\Contracts;

interface Visitor {

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public function enterMethod(string $name, ...$args) :void;

    /**
     * @param string $name
     * @param ...$args
     * @return void
     */
    public function leaveMethod(string $name, ...$args) :void;
}
