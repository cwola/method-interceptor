<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

interface Interceptable {

    /**
     * @param void
     * @return string newInstanceName
     *
     * @throws \LogicException
     */
    public function intercept() :string;

}
