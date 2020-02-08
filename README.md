# Superintendencia de Administración Tributaria of Guatemala PHP SDK
Provides an easy-to-use class for issuing, reading and parsing DTE documents issued by Superintendencia de
Administración Tributaria of Guatemala.

```php
<?php

use Sat\DteParser;

$xml_file = './dte-document.xml'; // Path to the XML file

$DteParser = new DteParser($xml_file);

$invoice = $DteParser->getParsedDocument(); // Returns an object with the parsed DTE data
```

## Pending Features
- Add the ability to void existing DTE documents.
- Add the ability to verify existing DTE documents.

## Requirements
PHP 7.1+. Other than that, this library has no external requirements.

## Installation
You can install this library via Composer.
```bash
$ composer require abdyfranco/sat
```

## License
The MIT License (MIT). Please see "LICENSE.md" File for more information.