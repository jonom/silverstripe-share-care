# How to obtain a Facebook App Token and use it with the Share Care module

If you want to use the Facebook Graphics API to re-scrape your pages, you may need to use an access token. This is how to obtain a token and use it with the Share Care module.

## 1. Create a Facebook App

* Go to https://developers.facebook.com/apps and create an app.
* Name it "ShareCareScrapingAccess" for example.
* If you don't already have a Facebook Developer Account, you may be asked to create one first.

## 2. Get the App-Token

* Go to <https://developers.facebook.com/tools/explorer>
* In Dropdown "User or Page" select  "App Token"
(because User Tokens will expire after some time, that's why you'd like to use the App Token.  )
* In Dropdown "Facebook App" select your newly created App (e.g. "ShareCareScrapingAccess")
* For a test set the mode in the top form to `get` and the value to `?scrape=1&id=https://www.mydomain.com`
* Copy Token from the input Field at the to top.



The token will look something like this:
11111111111111111|ABcDEfG1hi1j1K11LMNop1QRSTU

You can also view your created App tokens afterwards here:
https://developers.facebook.com/tools/accesstoken/


## 3. Use the obtained token with Share Care module

You can set the token either in your .env file:

```
SS_SHARECARE_FBACCESSTOKEN="11111111111111111|ABcDEfG1hi1j1K11LMNop1QRSTU"
```

or in a yml configuration file:

```
JonoM\ShareCare\ShareCare:
  facebook_access_token: '11111111111111111|ABcDEfG1hi1j1K11LMNop1QRSTU'
```
