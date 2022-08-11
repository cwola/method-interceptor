# Collection

Providing Collection and TypedArray(Cwola library).

## Overview

Based on [illuminate/collections](https://github.com/illuminate/collections).  
So, it can be treated in the same way as [illuminate/collections](https://github.com/illuminate/collections).  
But, it is more lightweight than [illuminate/collections](https://github.com/illuminate/collections) because it has no dependencies.

## Requirement
- PHP7.0+

## Installation
```
composer require cwola/collection
```

## Usage
- Readable
```
<?php

use Cwola\Attribute\Readable;

class Foo {
    use Readable;

    /**
     * @var string
     */
    #[Readable]
    protected string $protectedString = 'Protected';
}

class Bar extends Foo {
    /**
     * @var string
     */
    protected string $override = 'OVER RIDE!!';

    /**
     * {@inheritDoc}
     */
    private function __read(string $name): mixed {
        return $this->override;
    }
}

$foo = new Foo;
echo $foo->protectedString;  // Protected
$foo->protectedString = 'modify';  // Error

$custom = new Bar;
echo $custom->protectedString;  // OVER RIDE!!
echo $custom->override;  // Error
```

## Licence

[MIT](https://github.com/cwola/collection/blob/main/LICENSE)
