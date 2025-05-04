<?php

namespace Zarganwar\JsonCompressor;

use Exception;
use Zarganwar\JsonCompressor\Exception\CompressorException;

final class Compressor
{
	/**
	 * @param string $json
	 * @param bool $returnArray
	 * @return array|false|string
	 * @throws Exception
	 */
	public function compress($json, $returnArray = false)
	{
		$compressed = $this->jsonCompressor($json);

		return $returnArray
			? $compressed
			: json_encode($compressed, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param string $json
	 * @param bool $returnArray
	 * @return string|array
	 * @throws Exception
	 */
	public function decompress($json, $returnArray = false)
	{
		$array = json_decode($json, true);
		if (!isset($array['compressionMap'], $array['compressedJson'])) {
			throw new CompressorException("Invalid JSON provided for decompression.");
		}

		$decompressed = $this->decompressArray(
			$array['compressedJson'],
			$array['compressionMap']
		);

		return $returnArray
			? $decompressed
			: json_encode($decompressed, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param string $jsonString
	 * @return array
	 * @throws Exception
	 */
	private function jsonCompressor($jsonString)
	{
		$data = json_decode($jsonString, true);

		if ($data === null) {
			throw new Exception('Invalid JSON input.');
		}

		$keyCounts = [];
		$this->countKeys($data, $keyCounts);

		$compressionMap = [];
		$usedKeys = [];
		$nextChar = 'a';

		$compressedData = $this->compressRecursive(
			$data,
			$compressionMap,
			$usedKeys,
			$nextChar,
			$keyCounts
		);

		return [
			'compressionMap' => $compressionMap,
			'compressedJson' => $compressedData,
		];
	}


	/**
	 * Rekurzivně spočítá, kolikrát se který klíč objeví.
	 *
	 * @param array $data
	 * @param array $counts
	 * @return void
	 */
	private function countKeys(array $data, array &$counts)
	{
		foreach ($data as $key => $value) {
			$counts[$key] = (isset($counts[$key]) ? $counts[$key] : 0) + 1;
			if (is_array($value)) {
				$this->countKeys($value, $counts);
			}
		}
	}


	/**
	 * @param array $data
	 * @param array $map
	 * @param array $usedKeys
	 * @param string $nextChar
	 * @param array $counts
	 * @return array
	 */
	private function compressRecursive(
		array $data,
		array &$map,
		array &$usedKeys,
		&$nextChar,
		array &$counts
	)
	{
		$compressed = [];

		foreach ($data as $key => $value) {
			$originalKey = (string) $key;

			$existing = array_search($originalKey, $map, true);
			if ($existing !== false) {
				$compressedKey = $existing;
			} else {
				$freq = isset($counts[$originalKey]) ? $counts[$originalKey] : 0;
				$lenOrig = strlen($originalKey);
				$peek = $nextChar;
				$candidate = $this->getCompressedKey($usedKeys, $peek);
				$lenComp = strlen($candidate);
				$saved = $freq * ($lenOrig - $lenComp);
				// Costs: "komp":"orig" => lenComp + lenOrig + 5
				$overhead = $lenComp + $lenOrig + 5;

				if ($freq > 1 && $saved > $overhead) {
					$compressedKey = $candidate;
					$map[$compressedKey] = $originalKey;
					$usedKeys[] = $compressedKey;
					$nextChar = $this->incrementChar($candidate);
				} else {
					$compressedKey = $originalKey;
				}
			}

			if (is_array($value)) {
				$compressed[$compressedKey] = $this->compressRecursive(
					$value,
					$map,
					$usedKeys,
					$nextChar,
					$counts
				);
			} else {
				$compressed[$compressedKey] = $value;
			}
		}

		return $compressed;
	}


	/**
	 * @param array $usedKeys
	 * @param string $nextChar
	 * @return string
	 */
	private function getCompressedKey(array $usedKeys, &$nextChar)
	{
		while (in_array($nextChar, $usedKeys, true)) {
			$nextChar = $this->incrementChar($nextChar);
		}

		return $nextChar;
	}


	/**
	 * @param string $char
	 * @return string
	 */
	private function incrementChar($char)
	{
		$chars = str_split($char);
		$i = count($chars) - 1;

		while ($i >= 0) {
			if ($chars[$i] === 'z') {
				$chars[$i] = 'a';
				if ($i === 0) {
					array_unshift($chars, 'a');
					break;
				}
			} else {
				$chars[$i] = chr(ord($chars[$i]) + 1);
				break;
			}
			$i--;
		}

		return implode('', $chars);
	}


	/**
	 * @param array $compressedData
	 * @param array $compressionMap
	 * @return array
	 * @throws Exception
	 */
	private function decompressArray(array $compressedData, array $compressionMap)
	{
		return $this->decompressRecursive($compressedData, $compressionMap);
	}


	/**
	 * @param array $data
	 * @param array $map
	 * @return array
	 */
	private function decompressRecursive(array $data, array $map)
	{
		$decompressed = [];

		foreach ($data as $key => $value) {
			$origin = isset($map[$key]) ? $map[$key] : $key;
			if (is_array($value)) {
				$decompressed[$origin] = $this->decompressRecursive($value, $map);
			} else {
				$decompressed[$origin] = $value;
			}
		}

		return $decompressed;
	}

}
