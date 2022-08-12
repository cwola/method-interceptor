# Interceptor

**Experimental: This is an experimental version.**

Providing Intercepter for PHP(Cwola library).

## Requirement
- PHP8.0 (PHP8.0 to PHP8.1)

## Installation
```
composer require cwola/interceptor
```

## Usage
```
<?php

use Cwola\Interceptor;

class Foo {
    use Interceptor\Interceptable;

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

    #[Interceptor\DoNotIntercept]
    protected function bold(string $message) :string {
        return '**' . $message . '**';
    }

    private function privateMessage(string $message) {
        echo $message . PHP_EOL;
    }
}

class InterceptTimer implements Interceptor\Interceptor {
    protected array $timers = [];

    public function enterMethod(string $name, ...$args) :void {
        $timers[$name] = new StopWatch;
    }

    public function leaveMethod(string $name, ...$args) :void {
        echo 'TIME : ' . $timers[$name]->stop()->time() . PHP_EOL;
    }
}

class InterceptGreet implements Interceptor\Interceptor {
    public function enterMethod(string $name, ...$args) :void {
        echo 'ENTER : ' . $name . PHP_EOL;
    }

    public function leaveMethod(string $name, ...$args) :void {
        echo 'LEAVE : ' . $name . PHP_EOL;
    }
}

$foo = new ((new Foo)->intercept());
// If you are using singleton instances:
// $foo = ((Foo::getInstance(...params))->intercept())::getInstance(...params);

$foo->run();
// output:
//
// [ENTER] : run
// [ENTER] : message
// **Hello**
// TIME : xxx
// [LEAVE] : message
// TIME : xxx
// [LEAVE] : run
//


/*
    Private methods cannot be intercepted.
*/

```

## Licence

[MIT](https://github.com/cwola/interceptor/blob/main/LICENSE)
