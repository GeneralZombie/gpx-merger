# GPX Merger

This library merges waypoints, routes and tracks from gpx files into a single gpx file.

## Installation

``` 
$ composer require twohundredcouches/gpx-merger
```

## Usage

```
use TwohundredCouches\GpxMerger\GpxMerger;
use TwohundredCouches\GpxMerger\Model\GpxMetaData;

$files = ['path/to/file1.gpx', 'path/to/file2.gpx', 'path/to/file3.gpx'];

$destination = 'path/to/frankfurt-merged.gpx'; 

$optionalMetaData = GpxMetaData::create(
    // name
    'Frankfurt Tour',
    // description 
    'My hike tour in Frankfurt',
    // author 
    'Jane Doe'
);

$compression = 0.0;

GpxMerger::merge($filesToMerge, $destination, $optionalMetaData, $compression);
```

## Classes

### `GpxMerger`

Main class of this package. Provides a static merge function.

##### `merge(array $files, ?string $destination = null, GpxMetaData $metaData = null, float $compression = 0.0): string`

*Return:* `string` 

*Throws:* `GpxMergerException`

Merges the provided array of `.gpx` files into a single file, that can be defined with `$destination`.
If a GpxMetaData object is provided the given metadata will be added to that file.
For compression you can pass a float value between 0 (no compression) and 1 (maximum compression).
Compression means that waypoint, route and track nodes get removed from the gpx file. A compression value of 1.0 means
that every node, except for the first and last one, which are always preserved, will be removed. A compression value of 
0.5 means that 50% of the nodes will be removed and 0.0 that there will be no nodes removed at all (this is the default).
Returns the path of the output file. 

### `GpxMetaData`

*Namespace:* `TwohundredCouches\GpxMerger\Model`

An object to add name, description and author as metadata to a merged file. Provides a static create method as alternative to the constructor.

##### `__construct(?string $name, ?string $description = null, ?string $author = null): GpxMetaData`

*Return:* `GpxMetaData` 

Returns a GpxMetaData object with the provided fields (all optional).

##### `create(?string $name, ?string $description = null, ?string $author = null): GpxMetaData`

*Return:* `GpxMetaData` 

Returns a GpxMetaData object with the provided fields (all optional).
