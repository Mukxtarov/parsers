<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Client;

$config = require __DIR__ . '/config/config.php';

/* FUNCTIONS */

/**
 * @param $value
 */
function debug($value)
{
    /*echo '<pre>';
    var_dump($value);
    echo '</pre>';*/
    echo $value . PHP_EOL;
}

/**
 * @param $url
 * @param $name
 * @param $extensions
 * @return int|mixed
 */
function download_file($url, $name, $extensions)
{
    try {
        $path = checkFolder($extensions);
        $file_path = fopen($path . $name, 'w');
        $client = new Client();
        $response = $client->get($url, ['save_to' => $file_path]);
        return $response->getStatusCode();

    } catch (Exception $error){
        return $error->getCode();
    }

}


function executeTime($sTime, $eTime) {
    echo "\n\n----------\n";
    echo (float)($eTime - $sTime);
    echo "\n\n";
}

function logger($message) {
    global $config;

    return file_put_contents($config['log'], date('H:i d-m-Y')." | $message" . PHP_EOL, FILE_APPEND);
}

function short_story($desc, $image) {
    $date = date('Y-m');
    return "<a class=\"highslide\" href=\"https://muzlik.uz/uploads/posts/images/$date/$image\" target=\"_blank\"><img src=\"https://muzlik.uz/uploads/posts/images/$date/$image\" alt=\"\" class=\"fr-dib\"></a>$desc";
}

function checkFolder($path) {
    $date = date('Y-m');
    if (!is_dir("$path/$date")) {
        $mkdir = mkdir("$path/$date");
        if (!$mkdir) throw new Exception("new folder created error!");
        return "$path/$date/";
    }
    else return "$path/$date/";
}

function makeKeywords($text) {
    $text = str_replace(['.', ',', '!', '?'], "", $text);
    $array = explode(' ', $text);
    return implode(", ", $array);
}