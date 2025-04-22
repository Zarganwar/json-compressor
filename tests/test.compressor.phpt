<?php

use Tester\Assert;
use Zarganwar\JsonCompressor\Compressor;

require_once __DIR__ . '/../vendor/autoload.php';

$compressor = new Compressor();

$files = [
	'01-orders',
	'02-logs',
	'03-products',
	'04-nested-repeat',
	'05-5mb-sample',
];

foreach ($files as $file) {
	$source = file_get_contents(__DIR__ . "/files/{$file}.json");
	$expected = file_get_contents(__DIR__ . "/files/{$file}-compress.json");
	$compressed = $compressor->compress($source);
	$decompressed = $compressor->decompress($compressed);

	Assert::equal($expected, $compressed, "Compression {$file} failed");
	Assert::equal($source, $decompressed, "Decompression {$file} failed");

	$sourceLen = mb_strlen($source);
	$compressedLen = mb_strlen($compressed);

	Assert::true($sourceLen > $compressedLen, "Compression {$file} failed");
}