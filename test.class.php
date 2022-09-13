<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Cwola\Interceptor;

#[Interceptor\Attribute\Interceptable]
abstract class T {
    public function test() {
        echo 'T';
    }
}

#[Interceptor\Attribute\Interceptable]
class TEST extends T {
    use Interceptor\UseIntercept;

    public function __construct() {
        $this->__addInterceptor(new TEST_INTERCEPT);
    }

    public function test() {
        echo 'TEST';
    }

    #[Interceptor\Attribute\DoNotIntercept]
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

    protected function test5(?string $message) :string|int {
        echo $message . PHP_EOL;
        return '';
    }

    protected function test6(string|int $message) :string|int|null {
        echo $message . PHP_EOL;
        return '';
    }

    protected function test7(?string $message) :?string {
        echo $message . PHP_EOL;
        return '';
    }
}

class TEST_INTERCEPT implements Interceptor\Visitor\Interceptor {
    public function enterMethod(string $name, ...$args): void {
        echo '[ENTER] ' . $name . PHP_EOL;
    }

    public function leaveMethod(string $name, ...$args): void {
        echo '[LEAVE] ' . $name . PHP_EOL;
    }
}
