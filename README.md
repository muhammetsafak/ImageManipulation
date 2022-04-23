# ImageManipulation

PHP Image Manipulation Class

[![Latest Stable Version](http://poser.pugx.org/muhammetsafak/image-manipulation/v)](https://packagist.org/packages/muhammetsafak/image-manipulation) [![Total Downloads](http://poser.pugx.org/muhammetsafak/image-manipulation/downloads)](https://packagist.org/packages/muhammetsafak/image-manipulation) [![License](http://poser.pugx.org/muhammetsafak/image-manipulation/license)](https://packagist.org/packages/muhammetsafak/image-manipulation) [![PHP Version Require](http://poser.pugx.org/muhammetsafak/image-manipulation/require/php)](https://packagist.org/packages/muhammetsafak/image-manipulation)

## Some Features

- Resize proportionally or disproportionately.
- Crop certain part of the image.
- Rotate or flip.
- Write text on the picture.
- Watermark.
- Apply filters to images.
- 

## Requirements

- PHP 7.4 and above
- [PHP GD Extension](https://www.php.net/manual/en/book.image.php)

## Installation

```
composer require muhammetsafak/image-manipulation
```

## Usage

```php
require_once "vendor/autoload.php";

$image = new \ImageManipulation\Image();

$image->setImage(__DIR__ . '/image.jpg');
$image->resize(300, 300);
// ... other operations
$image->save(__DIR__ . '/min_image.jpg');
$image->clean();
```

## Licence

This library is written by [Muhammet ŞAFAK](http://www.muhammetsafak.com.tr) and distributed under the [MIT License](./LICENSE).