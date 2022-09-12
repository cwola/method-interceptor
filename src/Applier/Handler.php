<?php

declare(strict_types=1);

namespace Cwola\Interceptor\Applier;

use Cwola\Interceptor\Compiler\Handler as Compiler;

class Handler {

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
    public function apply() :string|false {
        $this->error = '';
        if (($source = $this->getContents($this->filepath)) === false) {
            return false;
        };
        $compiler = new Compiler($source);
        if (($compiled = $compiler->compile()) === false) {
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
