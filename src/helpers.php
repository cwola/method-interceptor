<?php

declare(strict_types=1);

use Cwola\MethodInterceptor\Engine;
use Cwola\MethodInterceptor\Applier\Handler as Applier;

if (!function_exists('applyMethodIntercept')) {
    /**
     * @param string $path
     * @return string|false
     */
    function applyMethodIntercept(string $path) :string|false {
        $applier = new Applier($path);
        return $applier->apply();
    }
}

Engine::boot();
Engine::setUp();
