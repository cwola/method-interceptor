<?php

declare(strict_types=1);

use Cwola\Interceptor\Engine;
use Cwola\Interceptor\Applier\Handler as Applier;

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
