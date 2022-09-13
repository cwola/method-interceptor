<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor\Filter;

use Cwola\MethodInterceptor\Contracts\Filter;

final class Accept implements Filter {

    /**
     * {@inheritDoc}
     */
    public function test(string $name, ...$args) :bool {
        return true;
    }
}
