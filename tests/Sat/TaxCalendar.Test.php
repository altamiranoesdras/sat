<?php
declare(strict_types = 1);

use Tester\Assert;
use \Sat\TaxCalendar;

require __DIR__ . '/../bootstrap.php';

/**
 * Mock the original class.
 */
$TaxCalendar = new TaxCalendar();

/**
 * TaxCalendar::getEvents() Test
 *
 * @assert type Check if the returned response it's an array.
 * @assert contains Check if the returned array has the expected properties.
 */
Assert::type('array', $TaxCalendar->getEvents());
Assert::contains('Impuesto', $TaxCalendar->getEvents()[0]->title);

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
