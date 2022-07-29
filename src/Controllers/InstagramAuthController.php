<?php

namespace Iliain\Instagram\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;

class InstagramAuthController extends Controller
{
    private static $url_handlers = [
        'instagram/auth' => 'index'
    ];

    public function index($request)
    {
        $siteConfig = SiteConfig::current_site_config();

        $token = $request->getVar('access_token');
        $expires = $request->getVar('expires_in');

        if ($token) {
            $siteConfig->InstagramToken = $token;
        }

        if ($expires) {
            $siteConfig->InstagramExpires = date('Y/m/d H:i:s', strtotime('+' . $expires . ' seconds'));
        }

        if ($token && $expires) {
            $siteConfig->write();
        }

        if (!Director::is_cli()) {
            Controller::curr()->redirect('/admin/settings/#Root_Instagram');
        }
    }
}
