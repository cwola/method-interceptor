# MethodInterceptor

**Experimental: This is an experimental version.**

Intercept class method.

## Installation
```
composer require cwola/method-interceptor
```

## Usage
```
<?php

use Cwola\MethodInterceptor;

#[MethodInterceptor\Attribute\Interceptable]
class Foo {
    use MethodInterceptor\UseIntercept;

    public function __construct() {
        $this->__addInterceptor(new InterceptTimer);
        $this->__addInterceptor(new InterceptGreet);
    }

    public function run() :bool {
        $this->message('Hello');
        return true;
    }

    protected function message(string $message) {
        $this->privateMessage(
            $this->bold($message)
        );
    }

    #[MethodInterceptor\Attribute\DoNotIntercept]
    protected function bold(string $message) :string {
        return '**' . $message . '**';
    }

    private function privateMessage(string $message) {
        echo $message . PHP_EOL;
    }
}

class InterceptTimer implements MethodInterceptor\Visitor\Interceptor {
    protected array $timers = [];

    public function enterMethod(string $name, ...$args) :void {
        $timers[$name] = new StopWatch;
    }

    public function leaveMethod(string $name, ...$args) :void {
        echo 'TIME : ' . $timers[$name]->stop()->time() . PHP_EOL;
    }
}

class InterceptGreet implements MethodInterceptor\Visitor\Interceptor {
    public function enterMethod(string $name, ...$args) :void {
        echo 'ENTER : ' . $name . PHP_EOL;
    }

    public function leaveMethod(string $name, ...$args) :void {
        echo 'LEAVE : ' . $name . PHP_EOL;
    }
}

$foo = new Foo;

$foo->run();
// output:
/* Private methods cannot be intercepted. */
//
// [ENTER] : run
// [ENTER] : message
// **Hello**
// TIME : xxx
// [LEAVE] : message
// TIME : xxx
// [LEAVE] : run
//

```

## Licence

[MIT](https://github.com/cwola/method-interceptor/blob/main/LICENSE)
