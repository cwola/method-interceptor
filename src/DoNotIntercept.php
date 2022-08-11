<?php

declare(strict_types=1);

namespace Cwola\Interceptor;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class DoNotIntercept {
}
