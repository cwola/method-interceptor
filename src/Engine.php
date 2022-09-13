<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor;

use LogicException;
use RuntimeException;
use Nikic\IncludeInterceptor\Interceptor;
use Nikic\IncludeInterceptor\FileFilter;

class Engine {

    /**
     * @var \Nikic\IncludeInterceptor\Interceptor|null
     */
    protected static Interceptor|null $interceptor = null;

    /**
     * @var \Nikic\IncludeInterceptor\FileFilter|null
     */
    protected static FileFilter|null $filter = null;


    /**
     * @param void
     * @param void
     */
    public static function boot() :void {
        static::setInterceptor(new Interceptor([__CLASS__, 'run']));
        static::setFileFilter(FileFilter::createAllWhitelisted());
    }

    /**
     * @param \Nikic\IncludeInterceptor\Interceptor $interceptor
     * @return void
     */
    public static function setInterceptor(Interceptor $interceptor) :void {
        static::$interceptor = $interceptor;
    }

    /**
     * @param \Nikic\IncludeInterceptor\FileFilter $filter
     * @return void
     */
    public static function setFileFilter(FileFilter $filter) :void {
        static::$filter = $filter;
    }

    /**
     * @param void
     * @return void
     *
     * @throws \LogicException
     * @thrown
     */
    public static function setUp() :void {
        if (static::$interceptor === null) {
            throw new LogicException('$interceptor is null.');
        }
        static::$interceptor->setUp();
    }

    /**
     * @param void
     * @return void
     *
     * @throws \LogicException
     */
    public static function tearDown() :void {
        if (static::$interceptor === null) {
            throw new LogicException('$interceptor is null.');
        }
        static::$interceptor->tearDown();
    }

    /**
     * @param string $path
     * @return string|null
     *
     * @throws \RuntimeException
     */
    public static function run(string $path) :string|null {
        if (!static::$filter->test($path)) {
            return null;
        }
        if (($compiled = \applyMethodIntercept($path)) === false) {
            throw new RuntimeException('failed: apply intercept.');
        }
        return $compiled;
    }
}
