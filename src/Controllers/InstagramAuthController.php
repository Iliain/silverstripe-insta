<?php

namespace Iliain\Instagram\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;

class InstagramAuthController extends Controller
{
    private static $allowed_actions = [
        'instagram',
        'facebook'
    ];

    private static $url_handlers = [
        'auth/instgram' => 'instagram',
        'auth/facebook' => 'facebook'
    ];

    public function setData($type, $request)
    {
        $siteConfig = SiteConfig::current_site_config();

        $token = $request->getVar('access_token');
        $expires = $request->getVar('expires_in');

        if ($type == 'instagram') {
            if ($token) {
                $siteConfig->InstagramToken = $token;
            }
    
            if ($expires) {
                $siteConfig->InstagramExpires = date('Y/m/d H:i:s', strtotime('+' . $expires . ' seconds'));
            }
        } else if ($type == 'facebook') {
            if ($token) {
                $siteConfig->FacebookToken = $token;
            }
    
            if ($expires) {
                $siteConfig->FacebookExpires = date('Y/m/d H:i:s', strtotime('+' . $expires . ' seconds'));
            }
        }

        if ($token && $expires) {
            $siteConfig->write();
        }

        if (!Director::is_cli()) {
            Controller::curr()->redirect('/admin/settings/#Root_Instagram');
        }
    }

    public function instagram($request)
    {
        $this->setData('instagram', $request);
    }

    public function facebook($request)
    {
        $this->setData('facebook', $request);
    }
}
