<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor\Contracts;

interface Filter {

    /**
     * @param string $name
     * @param ...$args
     * @return bool
     */
    public function test(string $name, ...$args) :bool;
}
