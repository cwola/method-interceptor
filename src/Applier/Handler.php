<?php

declare(strict_types=1);

namespace Cwola\MethodInterceptor\Applier;

use Cwola\MethodInterceptor\Contracts\Handler as IHandler;
use Cwola\MethodInterceptor\Compiler\Handler as Compiler;

class Handler implements IHandler {

    /**
     * @var string
     */
    protected string $filepath;


    /**
     * @param string $filepath
     */
    public function __construct(string $filepath) {
        $this->filepath = $filepath;
    }

    /**
     * @param void
     * @return string|false
     */
    public function handle() :string|false {
        $this->error = '';
        if (($source = $this->getContents($this->filepath)) === false) {
            return false;
        };
        $compiler = new Compiler($source);
        if (($compiled = $compiler->handle()) === false) {
            $this->error = \sprintf('RuntimeException : %s.', $compiler->getError());
            return false;
        }
        return $compiled;
    }

    /**
     * @param void
     * @return string
     */
    public function getError() :string {
        return $this->error;
    }

    /**
     * @param string $filePath
     * @return string|false
     */
    protected function getContents(string $filePath) :string|false {
        if (($contents = \file_get_contents($filePath)) === false) {
            $this->error = \sprintf(\sprintf('RuntimeException : failed to load contents (%s).', $this->filepath));
            return false;
        }
        return $contents;
    }
}
