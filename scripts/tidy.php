#!/usr/bin/php
<?php

// Use the first argument as max size or default to ~10 MB
$maxSize = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] * 1048576 : 10e6;

// Stat all the .gz file in the cache dir
$files = [];
foreach (glob(__DIR__ . '/../storage/*.gz') as $filepath)
{
	$files[$filepath] = stat($filepath);
}

// Sort by creation time
array_multisort($files, 'ctime');

// Remove files from the list until we exceed $maxSize
$size = 0;
while (!empty($files) && $size < $maxSize)
{
	$file  = array_pop($files);
	$size += $file['size'];
}

// Delete the remaining files
array_map('unlink', $files);
