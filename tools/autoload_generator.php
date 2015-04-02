<?php
/**
 * Generates a class-map based autoloader file for the examples directory
 *
 * PHP version 5.4
 *
 * @category   LibDNS
 * @package    Tools
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace LibDNS\Tools;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \FilesystemIterator;

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($argv[1])) {
    $srcDir = getcwd();
} else if (in_array(strtolower($argv[1]), ['--help', '?', '/?'])) {
    exit("Syntax: " . __FILE__ . " [source directory]\n");
} else if (!is_dir($srcDir = $argv[1])) {
    exit("Invalid source directory\n\nSyntax: " . __FILE__ . " [source directory]\n");
}
$srcDir = str_replace('\\', '/', $srcDir);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $srcDir,
        FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
    )
);

$items = [];
$stripLength = strlen($srcDir) + 1;
$maxLength = 0;
foreach ($iterator as $item) {
    if ($item->isFile() && $item->getFilename() !== 'autoload.php' && strtolower($item->getExtension()) === 'php') {
        $classPath = substr($item->getPath() . '\\' . $item->getBasename('.' . $item->getExtension()), $stripLength);
        $lookupName = strtolower(str_replace('/', '\\', $classPath));
        $loadPath = "__DIR__ . '/$srcDir/" . str_replace('\\', '/', $classPath) . ".php'";

        $length = strlen($classPath);
        if ($length > $maxLength) {
            $maxLength = $length;
        }

        $items[$lookupName] = $loadPath;
    }
}
unset($iterator);

$output = <<<'PHP'
<?php
/**
 * This file was automatically generated by autoload_generator.php
 *
 * Do not edit this file directly
 */

spl_autoload_register(function($className) {
    static $classMap;
    if (!isset($classMap)) {
        $classMap = [
PHP;

$maxLength += 2;
foreach ($items as $lookupName => $loadPath) {
    $output .= "\n            " . str_pad("'" . $lookupName . "'", $maxLength, ' ', STR_PAD_RIGHT) . " => $loadPath,";
}

$output .= <<<'PHP'

        ];
    }

    $className = strtolower($className);
    if (isset($classMap[$className])) {
        /** @noinspection PhpIncludeInspection */
        require $classMap[$className];
    }
});

PHP;

file_put_contents(getcwd() . '/autoload.php', $output);
