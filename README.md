# google-alerts-api-php

# This API is still deeply in WIP. No tests have been written yet and there is no way to modify alerts (yet).

## Thanks to https://github.com/adasq/google-alerts-api for developing a NodeJS API

A Google Alerts API that allows you to create Google Alerts via PHP and have them delievered to a RSS feed.

## The API currently only supports

- **creating alerts**
- **Removing alerts**

## Usage

#### NOTICE: Login via password and email is not possible (yet?) because google has changed their auth procedure. Only login with pre-defined cookies is working. See below for example

Copy the `GoogleAlert.php` class and the `config.php` from `google-alerts-api/GoogleAlert.php` into your project folder.

Edit the `config.php` and enter your base64 encoded cookies. (See below for an explanation)

(See `google-alerts-api/example.php` for a working example)

```php
  // creating a Google Alert object
  $ga = new GoogleAlert();

  /*
    creating a new google alert

    params      default-value     explanation

    $query      *none* required  name/title/query for the alert
    $lang       'en'              language of the alert results; 'en', 'de', 'ru'..
    $frequency  'happens'         how often new items should be delievered to the feed
    $type       'all'             type of source for new feed items. All includes blogs, news, etc.
    $quantity   'best'            returns only best results
  */

  /*
    creates the alert and returns the feed and the googleid
    (googleid is needed incase you want to delete an alert using the script)
  */
  $alert = $ga->create("Satoshi Nakamoto");
  echo json_encode($alert);

  /*
  {
    "rss": "https://www.google.de/alerts/feeds/somerssfeed/123",  // rssfeed:   articles will be delivered to this feed
    "googleid": "12341234b12341:a112341239364456:com:de:DE"       // googleid:  needed to delete and modify alerts
  }
*/
```

Deleting an alert by id

```php
  // deleting a Google Alert object
  $ga = new GoogleAlert();
  $ga->delete("12341234b12341:a112341239364456:com:de:DE");
```

### Generating cookies:

Cookies need to be pre-generated. Once you authenticated and logged in using your browser you can easily copy the cookies and save them in the `config.php`.

#### Logging in using the browser

Open Chrome in Incognito mode and login into `http://myaccount.google.com` using the account you want to use.

#### Copy SID, HSDI and SSID

1. Open dev tools
2. Go to **Application** and select **Cookies** for http://myaccount.google.com
3. Copy **SID**, **HSID** and **SSID** values

#### Turn your cookies into a base64 encoded auth string

1. Fill your cookie values into the given JSON object

```js
window.btoa(
  JSON.stringify([
    {
      key: "SID",
      value: "",
      domain: "google.com",
    },
    {
      key: "HSID",
      value: "",
      domain: "google.com",
    },
    {
      key: "SSID",
      value: "",
      domain: "google.com",
    },
  ])
);
```

2. Copy this code into the Console of your borwser and execute it
3. Copy the output (your auth string) into the `config.php`

## Make sure to enable "login from insecure apps" if having any issues connecting
