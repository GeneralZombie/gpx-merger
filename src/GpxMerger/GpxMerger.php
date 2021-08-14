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
     * @return string
     * @throws GpxMergerException
     */
    public static function merge(array $files, ?string $destination = null, GpxMetaData $metaData = null): string
    {
        if (!$destination) {
            $destination = sprintf('%s/%s.gpx', __DIR__, time());
        }

        $dom = self::createDomDocument();

        $gpx = self::createGpxElement($dom);

        $gpx = self::addMetaData($dom, $gpx, $metaData);

        $gpx = self::appendFiles($dom, $gpx, $files);

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

    protected static function appendFiles(\DOMDocument $dom, \DOMElement $gpx, array $files): \DOMElement
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new GpxMergerException(sprintf('File %s does not exist', $file));
            }

            if (pathinfo($file, PATHINFO_EXTENSION) !== 'gpx') {
                throw new GpxMergerException(sprintf('File %s has invalid type. Has to be gpx.', $file));
            }

            $xmlFile = new \DOMDocument();
            $xmlFile->load($file);

            foreach ($xmlFile->getElementsByTagName('wpt') as $waypoint) {
                $gpx->appendChild($dom->importNode($waypoint, true));
            }

            foreach ($xmlFile->getElementsByTagName('rte') as $route) {
                $gpx->appendChild($dom->importNode($route, true));
            }

            foreach ($xmlFile->getElementsByTagName('trk') as $track) {
                $gpx->appendChild($dom->importNode($track, true));
            }
        }

        return $gpx;
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