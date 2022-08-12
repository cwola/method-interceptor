<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'DoNotIntercept.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Interceptable.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Intercepted.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Interceptor.php';


use Cwola\Interceptor;

class TEST {

    use Interceptor\Interceptable;

    public function __construct() {
        $this->__addInterceptor(new TEST_INTERCEPT);
    }

    public function test() {
        echo 'TEST';
    }

    #[Interceptor\DoNotIntercept]
    public function test2() {
        echo 'TEST2';
    }

    public function test3(int &$b = null) {
        $this->message();
    }

    protected function message() {
        $this->test4('Hello');
    }

    private function test4(string $message) {
        echo $message . PHP_EOL;
    }
}

class TEST_INTERCEPT implements Interceptor\Interceptor {
    public function __enterMethod(string $name, ...$args): void {
        echo '[ENTER] ' . $name . PHP_EOL;
    }

    public function __leaveMethod(string $name, ...$args): void {
        echo '[LEAVE] ' . $name . PHP_EOL;
    }
}

$test = new ((new TEST)->intercept());
$test->test3();

