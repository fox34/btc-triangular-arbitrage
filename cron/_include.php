<?php

if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) die('Diese Datei stellt gemeinsam genutzte Methoden fuer Cronskripte bereit und ist nicht fuer den oeffentlichen Zugriff eingerichtet.');

define('DATA_DIR', dirname(__DIR__) . '/data/');

// CSV-Format immer eines von:
// Time,Close
// Time,Average
// Time,Open,High,Low,Close
// Zeit = YYYY-MM-DDTHH:MM:SS.mmmmmmPPPPPP
// Zeit = 2018-01-13T13:30:21.029304+01:00
define('TIMEFORMAT', 'Y-m-d\TH:i:s.uP');
date_default_timezone_set('UTC');

header('Content-Type: text/plain; charset=UTF-8');
ini_set('html_errors', 0);

if (!defined('DIS_TOOLS_NO_OUTPUT')) {
    echo
        strftime('%d.%m.%Y, %H:%M:%S ') . 
        'Starting Dis-Tools. Timezone is: UTC. ' . 
        'This file was last updated: ' .
        strftime('%d.%m.%Y, %H:%M:%S', filemtime($_SERVER['SCRIPT_FILENAME'])) . ' UTC.' .
        PHP_EOL . PHP_EOL;
}

function asPrice($string) : string
{
    return number_format($string, 2, ',', '.');
}

function readISODate(string $isoDate): DateTime
{
    $dateTime = DateTime::createFromFormat(TIMEFORMAT, $isoDate);
    if ($dateTime === false) {
        throw new Exception('Could not parse time.');
    }
    return $dateTime;
}
function getISODate(DateTime $dateTime) : string
{
    return $dateTime->format(TIMEFORMAT);
}

function getUnixTimeWithMilliseconds(DateTime $time, int $decimals = 3) : string
{
    $decimals = max(min($decimals, 6), 0);
    return $time->format('U') . substr($time->format('u'), 0, $decimals);
}

function head(string $str, int $lines = 1, string $newLine = "\n")
{
    $result = strtok($str, $newLine);
    for ($i = 1; $i < $lines; $i++) {
        $result .= $newLine . strtok($newLine);
    }
    return $result;
}

// Source: https://gist.github.com/lorenzos/1711e81a9162320fde20
// Chosen by: https://stackoverflow.com/a/15025877
// modified by S. Manzer for custom newLines
/**
 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
 * @author Torleif Berger, Lorenzo Stanco
 * @link http://stackoverflow.com/a/15025877/995958
 * @license http://creativecommons.org/licenses/by/3.0/
 */
function tailCustom(string $filepath, int $lines = 1, bool $adaptive = true, string $newLine = "\n")
{
    // Open file
    $fp = fopen($filepath, 'rb');
    if ($fp === false) {
        return false;
    }
    
    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    if (!$adaptive) {
        $buffer = 4096;
    } else {
        $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
    }
    
    // Determine newline length
    $nlLength = strlen($newLine);
    
    // Jump to last character
    fseek($fp, -$nlLength, SEEK_END);
    
    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if (fread($fp, $nlLength) !== $newLine) {
        $lines -= $nlLength;
    }
    
    // Start reading
    $output = '';
    $chunk = '';
    
    // While we would like more
    while (ftell($fp) > 0 && $lines >= 0) {
    
        // Figure out how far back we should jump
        $seek = min(ftell($fp), $buffer);
        
        // Do the jump (backwards, relative to where we are)
        fseek($fp, -$seek, SEEK_CUR);
        
        // Read a chunk and prepend it to our output
        $chunk = fread($fp, $seek);
        $output = $chunk . $output;
        
        // Jump back to where we started reading
        fseek($fp, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        
        // Decrease our line counter
        $lines -= substr_count($chunk, $newLine);
    }
    
    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, $newLine) + $nlLength);
    }
    
    // Close file and return
    fclose($fp);
    
    return trim($output);
}

function formatFileSize(float $size) : string
{
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < (count($sizes) - 1); $i++) {
        $size /= 1024;
    }

    $length = strpos((string)$size, '.');
    return str_replace('.', ',', round($size, 3 - $length)) . ' ' . $sizes[$i];
}
