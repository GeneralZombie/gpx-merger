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

        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/data/expected-result.gpx', $destination);
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