<?php

use Tester\Assert;
use Zarganwar\JsonCompressor\Compressor;

require_once __DIR__ . '/../vendor/autoload.php';

$compressor = new Compressor();
$data = file_get_contents(__DIR__ . '/test.json');
Assert::type('array', json_decode($data, true));

$compressedJson = $compressor->compress($data);
$compressedArray = $compressor->compress($data, true);

Assert::type('string', $compressedJson);
Assert::type('array', $compressedArray);
Assert::true(array_key_exists('compressionMap', $compressedArray));
Assert::true(array_key_exists('compressedJson', $compressedArray));

$decompressedString = $compressor->decompress($compressedJson);
$decompressedArray = $compressor->decompress($compressedJson, true);

Assert::type('string', $decompressedString);
Assert::type('array', $decompressedArray);
Assert::type('bool', $decompressedArray['userData']['preferences']['notifications']['email']);
Assert::equal($data, $compressedJson);