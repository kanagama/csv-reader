<?php

namespace Kanagama\CsvReader\Tests;

use InvalidArgumentException;
use Kanagama\CsvReader\CsvReader;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * @author k-nagama <k.nagama0632@gmail.com>
 */
final class CsvReaderTest extends TestCase
{
    /**
     * @var CsvReader
     */
    private $csvReader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return array
     */
    public function csvReaderProvider(): array
    {
        return [
            'SJIS' => [
                'path' => __DIR__ . '/../File/KEN_ALL.CSV',
            ],
            'UTF8_BOM' => [
                'path' => __DIR__ . '/../File/KEN_ALL_UTF8_BOM.CSV',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider csvReaderProvider
     *
     * @param  string  $path
     */
    public function readで全ての行が読み込める(string $path)
    {
        $this->csvReader = new CsvReader($path, ',', false);

        // header も読むため
        $lineCount = 1;
        $file = fopen($path, "r");
        while (!feof($file)) {
            fgetcsv($file);
            if (!feof($file)) {
                $lineCount++;
            }
        }
        fclose($file);

        // header 分は加算
        $readCount = 1;
        $iterator = $this->csvReader->readLine();
        foreach ($iterator as $line) {
            // 静的解析ツールがうるさいので $line を使う
            $readCount += (int) ((bool) $line);
        }

        $this->assertEquals($readCount, $lineCount, "iterator が{$readCount}件、csv が{$lineCount}で一致しません");
    }

    /**
     * @test
     * @dataProvider csvReaderProvider
     *
     * @param  string  $path
     */
    public function read行データが正常に読み込める(string $path)
    {
        $this->csvReader = new CsvReader($path, ',', false);

        $iterator = $this->csvReader->readLine();
        foreach ($iterator as $line) {
            foreach ($line as $value) {
                if (!empty($value) || $value === '0') {
                    continue;
                }

                $this->assertTrue(false, '不正なデータが検出されました');
                break;
            }
        }

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function パスが間違っている()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not found:');

        $file = new CsvReader('aaa');
    }

    /**
     * @test
     */
    public function CSVファイルではない()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not Csv File');

        $file = new CsvReader(__DIR__ . '/../../README.md');
    }

    /**
     * @test
     * @dataProvider csvReaderProvider
     *
     * @param  string  $path
     */
    public function 拡張子がCSV（※小文字でない）でも問題ない(string $path)
    {
        $this->csvReader = new CsvReader($path);

        // テストが通ればOK
        $this->expectNotToPerformAssertions();
    }

    // /**
    //  * @test
    //  *
    //  * @todo やり方が不明なので保留
    //  */
    // public function csvファイルが開けなければ例外()
    // {
    //     $this->expectException(UnexpectedValueException::class);
    //     $this->expectExceptionMessage('to open file:');
    // }
}
