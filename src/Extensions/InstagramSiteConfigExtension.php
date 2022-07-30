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
        'InstagramToken'	  => 'Varchar(255)',
        'InstagramExpires'	=> 'Datetime'
    ];
  
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(['InstagramToken', 'InstagramExpires']);
        $confLink = Config::inst()->get('Instagram', 'auth_handler_url');
      
        if ($confLink) {
            $notifier = LiteralField::create('Notifier', '');

            $instaStatus = $this->owner->InstagramToken ? 'Connected' : 'Not Connected';
            if ($this->owner->InstagramToken && (date("Y-m-d H:i:s") > $this->owner->InstagramExpires)) {
                $notifier = LiteralField::create('Notifier', '<p class="message error">Your access to Instagram has expired. Please reconnect your account.</p>');
                $instaStatus = 'Expired';
            }

            $fields->addFieldsToTab('Root.Instagram', [
                HeaderField::create('InstagramHeader', 'Instagram Account'),
                $notifier,
                ReadonlyField::create('InstagramStatus', 'Status', $instaStatus),
                DateField::create('InstagramExpires', 'Access Expires')->setReadonly(true),
                TextareaField::create('InstagramToken', 'Your Access Token')->setReadonly(true),
                LiteralField::create('InstagramButton', '
                    <div class="insta-default">
                      <a href="' . $confLink . '/instagram/auth?return=' . Director::absoluteBaseURL() . 'instagram/auth' . '" class="insta-default"><i class="fa fa-instagram"></i> Connect Account</a>
                    </div>'
                )
            ]);
            
            Requirements::css('iliain/silverstripe-insta:client/css/instagram.css');
        }
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
}
