# PHP client library for VideoAPI.cloud

## Install

To install this PHP library, you need [composer](http://getcomposer.org) first:

```console
curl -sS https://getcomposer.org/installer | php
```

Edit `composer.json`:

```javascript
{
    "require": {
        "videoapicloud/videoapicloud": "1.*"
    }
}
```

Install the depencies by executing `composer`:

```console
php composer.phar install
```

## Submitting the job

Example of `videoapicloud.conf`:

```ini
var s3 = s3://accesskey:secretkey@mybucket

set webhook = http://mysite.com/webhook/videoapicloud?videoID=$vid

-> mp4  = $s3/videos/video_$vid.mp4
-> webm = $s3/videos/video_$vid.webm
-> jpg:300x = $s3/previews/thumbs_#num#.jpg, number=3
```

Here is the PHP code to submit the config file:

```php
<?php

require_once('vendor/autoload.php');

$job = Videoapicloud\Job::create(array(
  'api_key' => 'k-api-key',
  'conf' => 'videoapicloud.conf',
  'source' => 'http://yoursite.com/media/video.mp4',
  'vars' => array('vid' => 1234)
));

if($job->{'status'} == 'processing') {
  echo $job->{'id'};
} else {
  echo $job->{'error_code'};
  echo $job->{'error_message'};
}

?>
```

You can also create a job without a config file. To do that you will need to give every settings in the method parameters. Here is the exact same job but without a config file:

```php
<?php

require_once('vendor/autoload.php');

$vid = 1234;
$s3 = 's3://accesskey:secretkey@mybucket';

$job = Videoapicloud\Job::create(array(
  'api_key' => 'k-api-key',
  'source' => 'http://yoursite.com/media/video.mp4',
  'webhook' => 'http://mysite.com/webhook/videoapicloud?videoId=' . $vid,
  'outputs' => array(
    'mp4' => $s3 . '/videos/video_' . $vid . '.mp4',
    'webm' => $s3 . '/videos/video_' . $vid . '.webm',
    'jpg:300x' => $s3 . '/previews/thumbs_#num#.jpg, number=3'
  )
));

?>
```

Other example usage:

```php
<?php
// Getting info about a job
$job = Videoapicloud\Job::get(18370773);

// Retrieving metadata
Videoapicloud\Job::getAllMetadata(18370773);

// Retrieving the source file metadata only
Videoapicloud\Job::getMetadataFor(18370773, 'source');
?>
```

Note that you can use the environment variable `VIDEOAPICLOUD_API_KEY` to set your API key.

*Released under the [MIT license](http://www.opensource.org/licenses/mit-license.php).*

---

* VideoAPI.cloud website: https://videoapi.cloud
* API documentation: https://videoapi.cloud/docs
* Contact: [support@videoapi.cloud](mailto:support@videoapi.cloud)
