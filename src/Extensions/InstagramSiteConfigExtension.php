<?php

namespace Iliain\Instagram\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;

class InstagramSiteConfigExtension extends DataExtension
{
    private static $db = [
        'InstagramToken'    => 'Varchar(255)',
        'InstagramExpires'	=> 'Datetime',
        'FacebookToken'     => 'Varchar(255)',
        'FacebookExpires'	=> 'Datetime'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(['InstagramToken', 'InstagramExpires']);
        $instaConfLink = Config::inst()->get('Instagram', 'auth_handler_url');
        $fbConfLink = Config::inst()->get('Facebook', 'auth_handler_url');

        if ($instaConfLink) {
            $instaNotifier = LiteralField::create('InstaNotifier', '');

            $instaStatus = $this->owner->InstagramToken ? 'Connected' : 'Not Connected';
            if ($this->owner->InstagramToken && (date('Y-m-d H:i:s') > $this->owner->InstagramExpires)) {
                $instaNotifier = LiteralField::create('Notifier', '<p class="message error">Your access to Instagram has expired. Please reconnect your account.</p>');
                $instaStatus = 'Expired';
            }

            $fields->addFieldsToTab('Root.Instagram', [
                HeaderField::create('InstagramHeader', 'Instagram Account'),
                $instaNotifier,
                ReadonlyField::create('InstagramStatus', 'Status', $instaStatus),
                DateField::create('InstagramExpires', 'Access Expires')->setReadonly(true),
                TextareaField::create('InstagramToken', 'Your Access Token')->setReadonly(true),
                LiteralField::create('InstagramButton', '
                    <div class="insta-default">
                        <a href="' . $instaConfLink . '/instagram/auth?return=' . Director::absoluteBaseURL() . 'auth/instagram' . '" class="insta-default"><i class="fab fa-instagram"></i> Connect Account</a>
                    </div>
                    <div class="form-group"></div>'
                )
            ]);
        }

        if ($fbConfLink) {
            $fbNotifier = LiteralField::create('FbNotifier', '');

            $fbStatus = $this->owner->FacebookToken ? 'Connected' : 'Not Connected';
            if ($this->owner->FacebookToken && (date('Y-m-d H:i:s') > $this->owner->FacebookExpires)) {
                $fbNotifier = LiteralField::create('FbNotifier', '<p class="message error">Your access to Facebook has expired. Please reconnect your account.</p>');
                $fbStatus = 'Expired';
            }

            //Random number to prevent CSRF
            $state = rand();

            $fields->addFieldsToTab('Root.Instagram', [
                HeaderField::create('FacebookHeader', 'Facebook Account'),
                $fbNotifier,
                ReadonlyField::create('FacebookStatus', 'Status', $fbStatus),
                DateField::create('FacebookExpires', 'Access Expires')->setReadonly(true),
                TextareaField::create('FacebookToken', 'Your Access Token')->setReadonly(true),
                LiteralField::create('FacebookButton', '
                    <div class="fb-button">
                        <a href="' . $fbConfLink . '/facebook/auth?state=' . $state . '&return=' . Director::absoluteBaseURL() . 'auth/facebook' . '"><i class="fab fa-facebook-square"></i> Connect Account</a>
                    </div>'
                )
            ]);
        }

        Requirements::css('iliain/silverstripe-insta:client/css/instagram.css');
        Requirements::insertHeadTags('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">');
    }
    
    public function getInstagramPosts($limit = null)
    {
        $accessToken = $this->owner->InstagramToken;
        
        if ($accessToken) {
            $fields = 'caption,id,media_type,media_url,permalink,thumbnail_url,timestamp,username';
            $url = 'https://graph.instagram.com/me/media?fields=' . $fields . '&access_token=' . $accessToken;
            
            if ($limit) {
                $url .= '&limit=' . $limit;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            
            // @todo check if feed data has been returned

            return json_decode($output, true)['data'];
        }
    }
    
    public function getCachedFeed($type)
    {
        $cacheFile = Config::inst()->get($type, 'cache_file') ?? $type . '-cache.txt';
        $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . $cacheFile;

        $cache = file_get_contents($path);
        if ($cache) {
            return unserialize($cache);
        } else {
            return null;
        }
    }
}
