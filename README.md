# GPX Merger

This library merges waypoints, routes and tracks from gpx files into a single gpx file.

## Installation

``` 
$ composer require twohundredcouches/gpx-merger
```

## Usage

```
<?php

import TwohundredCouches\GpxMerger\GpxMerger;

$files = ['path/to/file1.gpx', 'path/to/file2.gpx', 'path/to/file3.gpx'];

$destination = 'path/to/frankfurt-merged.gpx'; 

$optionalMetaData = new GpxMetaData(
    // name
    'Frankfurt Tour',
    // description 
    'My hike tour in Frankfurt',
    // author 
    'Jane Doe'
);

GpxMerger::merge($filesToMerge, $destination, $optionalMetaData);
```