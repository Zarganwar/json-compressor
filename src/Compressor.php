<?php

namespace Zarganwar\JsonCompressor;


use Exception;
use Zarganwar\JsonCompressor\Exception\CompressorException;

final class Compressor
{


	public function compress($json, $returnArray = false)
	{
		$compressed = $this->jsonCompressor($json);

		return $returnArray
			? $compressed
			: json_encode($compressed);
	}


	/**
	 *
	 * @param string $json
	 * @param bool $returnArray
	 * @return string|array
	 * @throws Exception
	 */
	public function decompress($json, $returnArray = false)
	{
		$array = json_decode($json, true);

		if (!isset($array['compressedJson'], $array['compressedJson'])) {
			throw new CompressorException("Invalid JSON provided for decompression.");
		}

		$decompressed = $this->decompressArray(
			$array['compressedJson'],
			$array['compressionMap']
		);

		return $returnArray
			? $decompressed
			: json_encode($decompressed);
	}


	/***
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

		$compressionMap = [];
		$usedKeys = [];
		$nextChar = 'a';
		$compressedData = $this->compressRecursive($data, $compressionMap, $usedKeys, $nextChar);

		return [
			'compressionMap' => $compressionMap,
			'compressedJson' => $compressedData,
		];
	}


	/**
	 * @param array $data
	 * @param array $map
	 * @param array $usedKeys
	 * @param string $nextChar
	 * @return array
	 */
	private function compressRecursive($data, &$map, &$usedKeys, &$nextChar)
	{
		$compressed = [];

		foreach ($data as $key => $value) {
			$originalKey = (string) $key;
			$compressedKey = array_search($originalKey, $map);
			if ($compressedKey === false) {
				$compressedKey = $this->getCompressedKey($usedKeys, $nextChar);
				$map[$compressedKey] = $originalKey;
				$usedKeys[] = $compressedKey;
				$nextChar = $this->incrementChar($nextChar);
			}

			if (is_array($value)) {
				$compressed[$compressedKey] = $this->compressRecursive($value, $map, $usedKeys, $nextChar);
			} else {
				$compressed[$compressedKey] = $value;
			}
		}

		return $compressed;
	}


	/**
	 *
	 * @param array $usedKeys
	 * @param string $nextChar
	 * @return string
	 */
	private function getCompressedKey($usedKeys, &$nextChar)
	{
		while (in_array($nextChar, $usedKeys)) {
			$nextChar = $this->incrementChar($nextChar);
		}

		return $nextChar;
	}


	/***
	 * @param string $char
	 * @return string
	 */
	private function incrementChar($char)
	{
		$charArray = str_split($char);
		$i = count($charArray) - 1;

		while ($i >= 0) {
			if ($charArray[$i] === 'z') {
				$charArray[$i] = 'a';
				if ($i === 0) {
					array_unshift($charArray, 'a');
					break;
				}
			} else {
				$charArray[$i] = chr(ord($charArray[$i]) + 1);
				break;
			}
			$i--;
		}

		return implode('', $charArray);
	}


	/***
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
	 *
	 * @param array $data
	 * @param array $map
	 * @return array
	 */
	private function decompressRecursive($data, $map)
	{
		$decompressed = [];

		foreach ($data as $key => $value) {
			$originalKey = isset($map[$key]) ? $map[$key] : $key;

			if (is_array($value)) {
				$decompressed[$originalKey] = $this->decompressRecursive($value, $map);
			} else {
				$decompressed[$originalKey] = $value;
			}
		}

		return $decompressed;
	}

}