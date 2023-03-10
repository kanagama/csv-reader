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
     * @var string
     */
    private const CSV_PATH = __DIR__ . '/../File/KEN_ALL.CSV';

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

        $this->csvReader = new CsvReader(self::CSV_PATH);
    }

    /**
     * @test
     */
    public function readで全ての行が読み込める()
    {
        $lineCount = 0;
        $file = fopen(self::CSV_PATH, "r");
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
     */
    public function read行データが正常に読み込める()
    {
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
     */
    public function 拡張子がCSV（※小文字でない）でも問題ない()
    {
        // テストが通ればOK
        $this->expectNotToPerformAssertions();
    }
}
