<?php

namespace Kanagama\CsvReader;

use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * @method array readLine()
 *
 * @author k-nagama <k.nagama0632@gmail.com>
 */
class CsvReader
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var bool
     */
    private $header;

    /**
     * @var string
     */
    private $delimiter;
    /**
     * @var resource|false
     */
    private $file;

    /**
     * @param  string  $filePath
     * @param  bool  $header
     * @param  string  $delimiter
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __construct(
        string $filePath,
        bool $header = true,
        string $delimiter = ','
    ) {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: " . $filePath);
        }
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
            throw new InvalidArgumentException("not Csv File: " . $filePath);
        }

        $this->filePath = $filePath;
        $this->header = $header;
        $this->delimiter = $delimiter;

        $this->file = fopen($this->filePath, "r");
        if (!$this->file) {
            throw new UnexpectedValueException("Unable to open file: " . $this->filePath);
        }

        // header は飛ばす
        if ($this->header) {
            fgetcsv($this->file, 0, $this->delimiter);
        }
    }

    /**
     * 破棄
     */
    public function __destruct()
    {
        if ($this->file) {
            fclose($this->file);
        }
    }

    /**
     * 1行ずつ読み込む
     *
     * @return Generator|array|false|null
     */
    public function readLine()
    {
        while (!feof($this->file)) {
            $row = fgetcsv($this->file, 0, $this->delimiter);
            if ($row === false) {
                continue;
            }

            // 文字コード変換
            foreach ($row as $key => $cell) {
                $row[$key] = $this->convertCellEncoding($cell, $key);
            }

            yield $row;
        }
    }

    /**
     * 文字コードが SJIS-win であれば utf8 に変換する
     *
     * @param string $cell
     * @param int $key
     *
     * @return string
     */
    private function convertCellEncoding(string $cell, int $key): string
    {
        $cellEncoding = mb_detect_encoding($cell, "SJIS-win,UTF-8,eucJP-win,SJIS,EUC-JP,ASCII");
        if ($cellEncoding === 'SJIS-win') {
            return mb_convert_encoding($cell, 'UTF-8', 'SJIS-win');
        }

        // UTFの場合はBOMがあるかも
        if ($cellEncoding !== 'UTF-8' || $this->header || $key > 0) {
            return $cell;
        }

        $this->header = true;

        $bom = pack('H*', 'EFBBBF');
        return preg_replace("/^$bom/", '', $cell);
    }
}