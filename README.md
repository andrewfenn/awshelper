# AWS Helper

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Quality Score](https://img.shields.io/scrutinizer/g/andrewfenn/awshelper.svg?style=flat-square)](https://scrutinizer-ci.com/g/andrewfenn/awshelper)

[![Total Downloads](https://img.shields.io/packagist/dd/andrewfenn/aws-helper.svg?style=flat-square)](https://packagist.org/packages/andrewfenn/aws-helper)
[![Total Downloads](https://img.shields.io/packagist/dm/andrewfenn/aws-helper.svg?style=flat-square)](https://packagist.org/packages/andrewfenn/aws-helper)
[![Total Downloads](https://img.shields.io/packagist/dt/andrewfenn/aws-helper.svg?style=flat-square)](https://packagist.org/packages/andrewfenn/aws-helper)

This library is an extension of the AWS SDK to make it easier to intergrate
AWS security token service where you refresh the access and secret token via
the AWS API on the server itself.

This is better than having a static access and secret key as it constantly
changes and only your server on AWS can get access to this information. I
have provided a small step by step list below on how to set this up on
your AWS account.

It's not meant to be an all inclusive library that can do everything,
if you need more advanced use cases then please consider using the AWS
SDK directly.

### How to install

Add the following to your composer.json file and run ```composer update```.

```json
    "require": {
        "andrewfenn/aws-helper": "0.3.0"
    }
```

This library is set up to use amazon's roles for getting the access and
secret key. If you don't want to do this then, for example you're developing
on your own system outside of AWS then see below.

### How to setup your AWS account

If you wish to use amazon's roles so that you're not storing the access
or secret key to your services in the code base, do the following.

* Login to your amazon console
* Go to Identity and Access Management
* Click the Roles link
* Create a new role, open it up and click on "attach role policy"
* Select from the policy template for the service you wish to use
i.e. "Amazon S3 Full Access", "Amazon SQS Full Access"
* Add the role name you created to the "iam_role" configuration option

Once you've done these steps the key, secret, and security tokens will be
downloadable from the a url inside amazon's infrastructure that will provide
your server with access.

### How to setup for development outside of amazon

For local development make a file like below called development.json

```json
{
  "Code" : "Success",
  "LastUpdated" : "2015-02-06T07:50:20Z",
  "AccessKeyId" : "<PUT YOUR ACCESS KEY HERE>",
  "SecretAccessKey" : "<PUT YOUR SECRET KEY HERE>",
  "Token" : "",
  "Expiration" : "2030-02-19T04:45:53+00:00"
}

```

Change the AccessKeyId, and SecretAccessKey fields to your appropiate
settings. Make sure the Expiration date is a date in the future or the code
will attempt to grab a new key every time you run your code.

```php
$awsHelper = new AwsHelper('development.json', 'http://url-to-root-folder-with-file/');
```

*I recommend you do not commit the development.json file*. This file should sit
somewhere secure that only your development team can access as it contains your
AWS Access and Secret key details.

### Quick use of the S3 Helper

To use the S3 helper library you'll do something similar as below.

A quick note that $file_pointer returns a file pointer instead of the contents
of the file as a design choice, as some files may be too big to fit into PHP
memory. Please also consider this when using this code so that you don't make
mistakes in loading huge files into PHP.

```php
<?php
use AwsHelper\AwsHelper;
use AwsHelper\S3Helper;

/* instantiate S3 Helper */
$adapter = new S3Helper(new AwsHelper('iam-role'), 'bucket-name');

/* Get a file from S3, store it temporarily on the system */
$file_pointer = $adapter->getFile('some/place/in/the/bucket/foo.txt');
if ($file_pointer === false)
    return false;

/* Read the entire file's contents into php */
$files_contents = fread($file_pointer, fstat($file_pointer)['size']);

/* The file downloaded to $file_pointer is destroyed upon script end
if you need to use this file afterwards then you need to copy it out
to somewhere else on the system. */
```

### Quick use of the SQS Helper

Included is a small SQS helper library that allows you to clearly setup
pushing and recieving from a queue with little hassle.

This helper will refresh the security token if the expiration date has
passed so if you have a long running process the code continues to run
without throwing an expiration exception.

```php
// Change the URL of your SQS queue in the appropiate field
$adapter = new SqsHelper(new AwsHelper('iam-role'), 'https://sqs.eu-west-1.amazonaws.com/****/queue-name-here');

// Push a message to SQS
$adapter->push('hello');

// The listen() command will block until a message comes in
foreach($adapter->listen() as $message)
{
    // Grab the Body of the message
    echo $message->get('Body')."\n";

    // Delete the message off the queue
    $adapter->remove($message);

    // Stop the listener thereby exiting the foreach
    $adapter->stop();
}
echo "Done\n";
```

The aforementioned code will generate the following output.

```shell
hello
Done
```
