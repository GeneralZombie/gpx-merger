<?php

declare (strict_types=1);

namespace TwohundredCouches\GpxMerger;

use TwohundredCouches\GpxMerger\Exception\GpxMergerException;
use TwohundredCouches\GpxMerger\Model\GpxMetaData;

class GpxMerger
{
    /**
     * @param array<string> $files The filepaths to merge.
     * @param string|null $destination The output filepath.
     * @param GpxMetaData|null $metaData Optional object with meta data which will be added to the output file.
     * @param float|null $compression   Optional compression. Use a value between 0.0 and 1.0.
     *                                  0.0 = no compression,
     *                                  0.5 = 50% compression (removes half of the nodes in each file),
     *                                  1.0 = maximum compression (keeps only first and last node in each file)
     * @return string
     * @throws GpxMergerException
     */
    public static function merge(
        array $files,
        ?string $destination = null,
        GpxMetaData $metaData = null,
        float $compression = 0.0
    ): string
    {
        if (!$destination) {
            $destination = sprintf('%s/%s.gpx', __DIR__, time());
        }

        if (pathinfo($destination, PATHINFO_EXTENSION) !== 'gpx') {
            $destination .= '.gpx';
        }

        // normalize $compression betweeen 0.0 and 1.0
        $compression = max(0.0, min(1.0, $compression));

        $dom = self::createDomDocument();

        $gpx = self::createGpxElement($dom);

        $gpx = self::addMetaData($dom, $gpx, $metaData);

        $gpx = self::appendFiles($dom, $gpx, $files, $compression);

        $saved = self::save($dom, $gpx, $destination);

        return $destination;
    }

    protected static function createDomDocument(): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        return $dom;
    }

    protected static function createGpxElement(\DOMDocument $dom): \DOMElement
    {
        $gpx = $dom->createElement('gpx');

        $version = $dom->createAttribute('version');
        $version->value = '1.1';
        $gpx->appendChild($version);

        $creator = $dom->createAttribute('creator');
        $creator->value = 'twohundredcouches/gpx-merger';
        $gpx->appendChild($creator);

        $xmlns = $dom->createAttribute('xmlns');
        $xmlns->value = 'http://www.topografix.com/GPX/1/1';
        $gpx->appendChild($xmlns);

        $xmlnsXsi = $dom->createAttribute('xmlns:xsi');
        $xmlnsXsi->value = 'http://www.w3.org/2001/XMLSchema-instance';
        $gpx->appendChild($xmlnsXsi);

        $xsiSchemaLocation = $dom->createAttribute('xsi:schemaLocation');
        $xsiSchemaLocation->value = 'http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd';
        $gpx->appendChild($xsiSchemaLocation);

        return $gpx;
    }

    protected static function addMetaData(\DOMDocument $dom, \DOMElement $gpx, ?GpxMetaData $metaData): \DOMElement
    {
        if ($metaData) {
            $metaDataElement = $dom->createElement('metadata');

            if ($metaData->getName()) {
                $name = $dom->createElement('name', $metaData->getName());
                $metaDataElement->appendChild($name);
            }

            if ($metaData->getDescription()) {
                $desc = $dom->createElement('desc', $metaData->getDescription());
                $metaDataElement->appendChild($desc);
            }

            if ($metaData->getAuthor()) {
                $author = $dom->createElement('author');
                $name = $dom->createElement('name', $metaData->getName());
                $author->appendChild($name);
                $metaDataElement->appendChild($author);
            }

            $gpx->appendChild($metaDataElement);
        }

        return $gpx;
    }

    protected static function appendFiles(
        \DOMDocument $dom,
        \DOMElement $gpx,
        array $files,
        float $compression
    ): \DOMElement
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new GpxMergerException(sprintf('File %s does not exist', $file));
            }

            if (pathinfo($file, PATHINFO_EXTENSION) !== 'gpx') {
                throw new GpxMergerException(sprintf('File %s has invalid type. Has to be gpx.', $file));
            }

            $xmlFile = new \DOMDocument();
            $xmlFile->preserveWhiteSpace = false;
            $xmlFile->load($file);

            $waypoints = $xmlFile->getElementsByTagName('wpt');
            if ($compression > 0) {
                self::compress($waypoints, $compression);
            }

            foreach ($waypoints as $waypoint) {
                $gpx->appendChild($dom->importNode($waypoint, true));
            }

            foreach ($xmlFile->getElementsByTagName('rte') as $route) {
                if ($compression > 0) {
                    self::compress($route->getElementsByTagName('rtept'), $compression);
                }

                $gpx->appendChild($dom->importNode($route, true));
            }

            foreach ($xmlFile->getElementsByTagName('trk') as $track) {
                if ($compression > 0) {
                    self::compress($track->getElementsByTagName('trkpt'), $compression);
                }

                $gpx->appendChild($dom->importNode($track, true));
            }
        }

        return $gpx;
    }

    protected static function compress(\DOMNodeList $nodeList, float $compression): \DOMNodeList
    {
        $nodeListCount = $nodeList->count();

        // remove evey x nodes
        $compressionStep = 1.0 / $compression;

        // first node to be removed has to reach this index
        $nextIndexToRemove = $compressionStep;

        // we have to track the number of removed items, because we have to account for the shortened array after we removed an item
        $removedCount = 0;

        for ($i = 0; $i < $nodeListCount; $i++) {

            // Keep first and last node no matter what
            if ($i === 0 || $i === $nodeListCount - 1) {
                continue;
            }

            if ($i >= $nextIndexToRemove) {
                // remove node
                $node = $nodeList->item($i - $removedCount);
                $node->parentNode->removeChild($node);

                // update next index to remove and removed count
                $nextIndexToRemove += $compressionStep;
                $removedCount ++;
            }
        }

        return $nodeList;
    }

    protected static function save(\DOMDocument $dom, \DOMElement $gpx, string $filePath): bool
    {
        $xmlAsString = sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n%s", $dom->saveXML($gpx));

        $result = file_put_contents($filePath, $xmlAsString);

        if (!$result) {
            throw new GpxMergerException(sprintf('Error while saving file %s', $filePath));
        }

        return $result !== false;
    }
}