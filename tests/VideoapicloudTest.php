<?php

use PHPUnit\Framework\TestCase;

class VideoapicloudTest extends TestCase {

  /*
    To run these tests, you need to set your API key with the
    environment variable `VIDEOAPICLOUD_API_KEY`
  */

  public function testSubmitJob() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'webhook' => 'http://mysite.com/webhook',
      'outputs' => array('mp4' => 's3://a:s@bucket/video.mp4')
    ));

    $job = Videoapicloud\Videoapicloud::submit($config);
    $this->assertEquals('processing', $job->{'status'});
    $this->assertTrue($job->{'id'} > 0);
  }

  public function testSubmitBadConfig() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4'
    ));

    $job = Videoapicloud\Videoapicloud::submit($config);
    $this->assertEquals('error', $job->{'status'});
    $this->assertEquals('config_not_valid', $job->{'error_code'});
  }

  public function testSubmitConfigWithAPIKey() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4'
    ));

    $job = Videoapicloud\Videoapicloud::submit($config, 'k-4d204a7fd1fc67fc00e87d3c326d9b75');
    $this->assertEquals('error', $job->{'status'});
    $this->assertEquals('authentication_failed', $job->{'error_code'});
  }

  public function testGenerateFullConfigWithNoFile() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'vars' => array(
        'vid' => 1234,
        'user' => 5098,
        's3' => 's3://a:s@bucket'
      ),
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'webhook' => 'http://mysite.com/webhook?vid=$vid&user=$user',
      'outputs' => array(
        'mp4' => '$s3/vid.mp4',
        'jpg_200x' => '$s3/thumb.jpg',
        'webm' => '$s3/vid.webm'
      )
    ));

    $generated = join("\n", array(
      'var s3 = s3://a:s@bucket',
      'var user = 5098',
      'var vid = 1234',
      '',
      'set source = https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'set webhook = http://mysite.com/webhook?vid=$vid&user=$user',
      '',
      '-> jpg_200x = $s3/thumb.jpg',
      '-> mp4 = $s3/vid.mp4',
      '-> webm = $s3/vid.webm'
    ));

    $this->assertEquals($generated, $config);
  }

  public function testGenerateConfigWithFile() {
    $file = fopen('videoapi.cloudnf', 'w');
    fwrite($file, 'var s3 = s3://a:s@bucket/video' . "\n" . 'set webhook = http://mysite.com/webhook?vid=$vid&user=$user' . "\n" . '-> mp4 = $s3/$vid.mp4');
    fclose($file);

    $config = Videoapicloud\Videoapicloud::config(array(
      'conf' => 'videoapi.cloudnf',
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'vars' => array('vid' => 1234, 'user' => 5098)
    ));

    $generated = join("\n", array(
      'var s3 = s3://a:s@bucket/video',
      'var user = 5098',
      'var vid = 1234',
      '',
      'set source = https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'set webhook = http://mysite.com/webhook?vid=$vid&user=$user',
      '',
      '-> mp4 = $s3/$vid.mp4'
    ));

    $this->assertEquals($generated, $config);

    unlink('videoapi.cloudnf');
  }

  public function testSubmitFile() {
    $file = fopen('videoapi.cloudnf', 'w');
    fwrite($file, 'var s3 = s3://a:s@bucket/video' . "\n" . 'set webhook = http://mysite.com/webhook?vid=$vid&user=$user' . "\n" . '-> mp4 = $s3/$vid.mp4');
    fclose($file);

    $job = Videoapicloud\Job::create(array(
      'conf' => 'videoapi.cloudnf',
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'vars' => array('vid' => 1234, 'user' => 5098)
    ));

    $this->assertEquals('processing', $job->{'status'});
    $this->assertTrue($job->{'id'} > 0);

    unlink('videoapi.cloudnf');
  }

  public function testSetApiKeyInJobOptions() {
    $job = Videoapicloud\Job::create(array(
      'api_key' => 'k-4d204a7fd1fc67fc00e87d3c326d9b75',
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4'
    ));

    $this->assertEquals('error', $job->{'status'});
    $this->assertEquals('authentication_failed', $job->{'error_code'});
  }

  public function testGetJobInfo() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'webhook' => 'http://mysite.com/webhook',
      'outputs' => array('mp4' => 's3://a:s@bucket/video.mp4')
    ));

    $job = Videoapicloud\Videoapicloud::submit($config);
    $info = Videoapicloud\Job::get($job->{"id"});

    $this->assertEquals($info->{"id"}, $job->{"id"});
  }

  public function testGetNotFoundJobReturnsNull() {
    $info = Videoapicloud\Job::get(1000);
    $this->assertNull($info);
  }

  public function testGetAllMetadata() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'webhook' => 'http://mysite.com/webhook',
      'outputs' => array('mp4' => 's3://a:s@bucket/video.mp4')
    ));

    $job = Videoapicloud\Videoapicloud::submit($config);
    sleep(4);

    $metadata = Videoapicloud\Job::getAllMetadata($job->{"id"});
    $this->assertNotNull($metadata);
  }

  public function testGetMetadataForSource() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'webhook' => 'http://mysite.com/webhook',
      'outputs' => array('mp4' => 's3://a:s@bucket/video.mp4')
    ));

    $job = Videoapicloud\Videoapicloud::submit($config);
    sleep(4);

    $metadata = Videoapicloud\Job::getMetadataFor($job->{"id"}, 'source');
    $this->assertNotNull($metadata);
  }

  public function testSetAPIVersion() {
    $config = Videoapicloud\Videoapicloud::config(array(
      'source' => 'https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'webhook' => 'http://mysite.com/webhook?vid=$vid&user=$user',
      'api_version' => 'beta',
      'outputs' => array(
        'mp4' => '$s3/vid.mp4',
      )
    ));

    $generated = join("\n", array(
      '',
      'set api_version = beta',
      'set source = https://s3-eu-west-1.amazonaws.com/files.videoapi.cloud/test.mp4',
      'set webhook = http://mysite.com/webhook?vid=$vid&user=$user',
      '',
      '-> mp4 = $s3/vid.mp4'
    ));

    $this->assertEquals($generated, $config);
  }
}
