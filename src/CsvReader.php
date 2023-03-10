<?php

namespace Kanagama\CsvReader;

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
     * @var bool
     */
    private $header;

    /**
     * @var string
     */
    private $filePath;
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
     * @param  string  $delimiter
     * @param  bool  $header
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __construct(
        string $filePath,
        string $delimiter = ',',
        bool $header = true
    ) {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: " . $filePath);
        }
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
            throw new InvalidArgumentException("not Csv File: " . $filePath);
        }

        $this->header = $header;
        $this->filePath = $filePath;
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
     * @return array|false|null
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
                $cellEncoding = mb_detect_encoding($cell, "SJIS-win,UTF-8,eucJP-win,SJIS,EUC-JP,ASCII");
                if ($cellEncoding === 'SJIS-win') {
                    $row[$key] = mb_convert_encoding($cell, 'UTF-8', 'SJIS-win');
                    continue;
                }

                // UTFの場合はBOMがあるかも
                if ($cellEncoding === 'UTF-8') {
                    if ($this->header || $key > 0) {
                        continue;
                    }
                    $this->header = true;

                    $bom = pack('H*', 'EFBBBF');
                    $row[0] = preg_replace("/^$bom/", '', $row[0]);
                }
            }

            yield $row;
        }
    }
}