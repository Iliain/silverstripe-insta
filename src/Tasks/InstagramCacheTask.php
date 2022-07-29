<?php

namespace Iliain\Instagram\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class InstagramCacheTask
 * @package Iliain\Instagram\Tasks
 */
class InstagramCacheTask extends BuildTask
{
    protected $title = 'Set Instagram Cache';

    protected $description = 'Updates the cached instagram data';

    private static $segment = 'set-instagram-cache';

    public function run($request)
    {
        set_time_limit(0);

        $confLink = Config::inst()->get('Instagram', 'auth_handler_url');
        $cacheFile = Config::inst()->get('Instagram', 'cache_file') ? Config::inst()->get('Instagram', 'cache_file') : 'SocialFeedCache.txt';

        if ($confLink) {
            $siteConfig = SiteConfig::current_site_config();

            $accessToken = $siteConfig->InstagramToken;
            if ($accessToken) {
                $fields = 'caption,id,media_type,media_url,permalink,thumbnail_url,timestamp,username,like_count,comment_count';
                $url = 'https://graph.instagram.com/me/media?fields=' . $fields . '&access_token=' . $accessToken;
        
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);
                curl_close($ch);
    
                $data = $this->setArrayData(json_decode($output, true)['data']);
        
                $this->setCache($data, $this->cache_file);
    
                Controller::curr()->redirect($confLink . '/instagram/auth/refresh?access_token=' . $accessToken . '&return=' . Director::absoluteBaseURL() . 'instagram/auth');
            } else {
                DB::alteration_message('No access token present', 'error');
            }
        } else {
            DB::alteration_message('Instagram auth handler url not set in config', 'error');
        }
    }

    public function setArrayData($output) 
    {
        $list = ArrayList::create();

        foreach ($output as $item) {
            $updatedData = [
                'ID' => $item['id'] ?? '',
                'Caption' => isset($item['caption']) ? DBField::create_field('Text', $item['caption']) : '',
                'Link' => $item['permalink'] ?? '',
                'Image' => isset($item['thumbnail_url']) ? $item['thumbnail_url'] : $item['media_url'],
                'Timestamp' => isset($item['timestamp']) ? DBField::create_field('Datetime', $item['timestamp']) : '',
                'Likes' => isset($item['like_count']) ? $item['like_count'] : 0,
                'Comments' => isset($item['comments_count']) ? $item['comments_count'] : 0,
            ];

            $list->push($updatedData);
        }

        return $list;
    }

    private function setCache($cache, $cacheFile)
    {
        $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . $cacheFile;
        file_put_contents($path, serialize($cache));
    }
}
