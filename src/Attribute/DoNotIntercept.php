<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class DoNotIntercept {
}
