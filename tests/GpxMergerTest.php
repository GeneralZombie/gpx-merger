<?php

declare (strict_types=1);

namespace TwohundredCouches\GpxMergerTests;

use PHPUnit\Framework\TestCase;
use TwohundredCouches\GpxMerger\GpxMerger;
use TwohundredCouches\GpxMerger\Model\GpxMetaData;

class GpxMergerTest extends TestCase
{
    private const TEMP_DIR = __DIR__ . '/temp';

    /**
     * @beforeClass
     */
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(self::TEMP_DIR)) {
            mkdir(self::TEMP_DIR);
        }
    }

    /**
     * @afterClass
     */
    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::TEMP_DIR)) {
            self::removeDirectory(self::TEMP_DIR);
        }
    }

    public function testMerge(): void
    {
        $destination = self::TEMP_DIR . '/expected-result.gpx';

        GpxMerger::merge(
            [
                __DIR__ . '/data/file01.gpx',
                __DIR__ . '/data/file02.gpx'
            ],
            $destination,
            GpxMetaData::create('Test', 'This is a test', 'Jane Doe')
        );

        $this->assertFileExists($destination);

        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/data/expected-result-0-compression.gpx', $destination);
    }

    /**
     * @dataProvider compressionProvider
     */
    public function testCompression($compression, $expectedResult): void
    {
        $destination = self::TEMP_DIR . $expectedResult;

        GpxMerger::merge(
            [
                __DIR__ . '/data/file01.gpx',
                __DIR__ . '/data/file02.gpx'
            ],
            $destination,
            GpxMetaData::create('Test', 'This is a test', 'Jane Doe'),
            $compression
        );

        $this->assertFileExists($destination);

        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/data/' . $expectedResult, $destination);
    }

    public function compressionProvider(): array
    {
        return [
            [0.0, 'expected-result-0-compression.gpx'],
            [0.25, 'expected-result-25-compression.gpx'],
            [0.5, 'expected-result-50-compression.gpx'],
            [0.75, 'expected-result-75-compression.gpx'],
            [1.0, 'expected-result-100-compression.gpx'],
        ];
    }

    /**
     * @dataProvider fileExtensionEnforcementProvider
     */
    public function testFileExtensionEnforcement($givenDestination, $expectedDestination): void
    {
        GpxMerger::merge(
            [
                __DIR__ . '/data/file01.gpx',
                __DIR__ . '/data/file02.gpx'
            ],
            $givenDestination
        );

        $this->assertFileExists($expectedDestination);
    }

    public function fileExtensionEnforcementProvider(): array
    {
        return [
            [self::TEMP_DIR . '/result1.gpx', self::TEMP_DIR . '/result1.gpx'],
            [self::TEMP_DIR . '/result2', self::TEMP_DIR . '/result2.gpx'],
            [self::TEMP_DIR . '/result3.txt', self::TEMP_DIR . '/result3.txt.gpx']
        ];
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $dirHandle = opendir(self::TEMP_DIR);

        while ($directoryItem = readdir($dirHandle)) {
            if ($directoryItem === '.' || $directoryItem === '..') {
                continue;
            }

            if (is_dir($directory . '/' . $directoryItem)) {
                self::removeDirectory($directory . '/' . $directoryItem);
            } else {
                unlink($directory . '/' . $directoryItem);
            }
        }

        closedir($dirHandle);

        rmdir($directory);
    }
}