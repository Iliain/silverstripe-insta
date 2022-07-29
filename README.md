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

