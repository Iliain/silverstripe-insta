# Silverstripe Instagram
Code for Silverstripe that interacts with the Express auth app. This is designed to query [the app found here](https://github.com/Iliain/insta-auth) and will not function without it. The code is designed to provide a login link, store incoming tokens, and use said tokens to request feed information from the Basic Display API, nothing more.

## Config

You'll need to define the following variables in your config:

```
Instagram:
  auth_handler_url: 'https://locationofauthhandler.app'
  cache_file: 'filename.txt'
```
`auth_handler_url` is used to define the URL where the auth app is located, while `cache_file` simply determines the name assigned to the local cache file for the feed. 

## Usage

Once configured, the code will add a new section to the site Settings in the CMS. Users can use this to log in and authorise an account, which will then store an access token. From here, you can then use the InstagramCacheTask to query the Basic Display API for you and retrieve some posts, which will then be stored in a local file in the public directory. You can use code like the following example to pull this data into the frontend:

```
public function getInstagramFeed()
{
    $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'SocialFeedCache.txt';

    $cache = file_get_contents($path);

    return unserialize($cache);
}
```

The fields available to display are the following: 

* ID
* Caption (stored as a Text DBField)
* Link
* Image (returns thumbnail_url if present, defaults to media_url if not)
* Timestamp (stored as a Datetime DBField)

