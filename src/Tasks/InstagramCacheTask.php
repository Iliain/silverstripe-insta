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
        
        $limit = $request->getVar('limit') ?? null;

        $confLink = Config::inst()->get('Instagram', 'auth_handler_url');
        $cacheFile = Config::inst()->get('Instagram', 'cache_file') ?? 'SocialFeedCache.txt';

        if ($confLink) {
            $siteConfig = SiteConfig::current_site_config();
            $accessToken = $siteConfig->InstagramToken;
            
            $rawData = $siteConfig->getInstagramPosts($limit);
            if ($rawData) {
                $data = $this->setArrayData($rawData);
        
                $this->setCache($data, $cacheFile);
                DB::alteration_message('Cache has been updated', 'success');
                
                // If current token is older than 24 hours but younger than 60 days, we can refresh it
                $expiryDate = $siteConfig->InstagramExpires;
                $isOldEnough = date('Y/m/d H:i:s', strtotime('-24 hours', time())) > $expiryDate;
                $isYoungEnough = date('Y/m/d H:i:s') < $expiryDate;
                
                if ($isOldEnough && $isYoungEnough) {
                    Controller::curr()->redirect($confLink . '/instagram/auth/refresh?access_token=' . $accessToken . '&return=' . Director::absoluteBaseURL() . 'instagram/auth');
                }
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
                'Username' => $item['username'] ?? '',
                'Caption' => isset($item['caption']) ? DBField::create_field('Text', $item['caption']) : '',
                'Link' => $item['permalink'] ?? '',
                'Image' => isset($item['thumbnail_url']) ? $item['thumbnail_url'] : $item['media_url'],
                'Timestamp' => isset($item['timestamp']) ? DBField::create_field('Datetime', $item['timestamp']) : ''
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
