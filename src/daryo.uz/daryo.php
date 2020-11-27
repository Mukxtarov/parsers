<?php
/**
 * @author Umidjon Mukhtarov
 * @email < mukxtarov@mail.ru >
 * @created 13:00 27.11.2020
 */

$sTime = microtime(true);

date_default_timezone_set('Asia/Tashkent');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 0);
set_time_limit(0);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../functions/simple_dom.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/functions.php';

use Cocur\Slugify\Slugify;

/* DBAL Doctrine */
$db = DatabaseService::db();

/* Generate Slug */
$slugify = new Slugify();

$config = require __DIR__ . '/config/config.php';

$client = new GuzzleHttp\Client(['base_uri' => $config['base_uri']]);

$request = $client->request('GET', $config['route']);
$response = (string) $request->getBody()->getContents();
$html = str_get_html($response);
$section = $html->find('ul[class=latest-news-list]', 0);
$contents = $section->find('li');

for ($i = (count($contents) - 1); $i >= 0; $i--) {

    try{
        $category = $contents[$i]->find('div[class=itemCat]', 0);
        $category = explode(", ", trim($category->plaintext));

        $searchCategory = array_intersect($category, array_keys($config['categories']));
        if (empty($searchCategory)) continue;
        $categories = array_map(function ($cat) use ($config) {
            return $config['categories'][$cat];
        }, $searchCategory);
        
        $title = $contents[$i]->find('div.itemTitle', 0);
        $title = html_entity_decode($title->getElementByTagName('a')->plaintext);

        $description = $contents[$i]->find('div.postText', 0)->plaintext;

        $link = $contents[$i]->find('div.itemTitle', 0);
        $link = $link->getElementByTagName('a')->href;

        $image = $contents[$i]->find('div.itemImage', 0);
        $image = $image->getElementByTagName('a')->getAttribute('data-src');
        $download_image = download_file("https:$image", $slugify->slugify(substr($title, 0, 15)) . ".jpg", $config['image_path']);
        if ($download_image !== 200) logger("$download_image| The image could not be downloaded - https:$image");
        
        $childRequest = $client->request('GET', $link);
        $childResponse = (string) $childRequest->getBody()->getContents();
        $childHtml = str_get_html($childResponse);

        $childContent = $childHtml->find('div[class=postContent]', 0)->innertext;

        $db->insert('dle_post', [
            'autor' => 'admin',
            'date' => date('Y-m-d H:i:s'),
            'short_story' => short_story($description, $slugify->slugify(substr($title, 0, 15)) . ".jpg"),
            'full_story' => $childContent,
            'xfields' => '',
            'title' => $title,
            'descr' => $description,
            'keywords' => makeKeywords("$title $description"),
            'category' => implode(",", $categories),
            'alt_name' => $slugify->slugify($title),
            'approve' => 1,
            'symbol' => '',
            'tags' => '',
            'metatitle' => ''
        ]);

        $db->insert('dle_post_extras', [
            'news_id' => $db->lastInsertId(),
            'user_id' => '1'
        ]);
        
        array_map(function ($category_id) use ($db) {
            $db->insert('dle_post_extras_cats', [
                'news_id' => $db->lastInsertId(),
                'cat_id' => $category_id
            ]);
        }, $categories);

        file_put_contents($config['log'], date('H:i d-m-Y')." | $title" . PHP_EOL, FILE_APPEND);

    } catch(Exception $e) {
        file_put_contents($config['log'], date('H:i d-m-Y')." | ". $e->getMessage() . PHP_EOL, FILE_APPEND);
        echo "An error occurred while downloading the file";
    }
}

$eTime = microtime(true);

executeTime($sTime, $eTime);