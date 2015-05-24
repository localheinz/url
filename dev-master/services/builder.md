---
layout: default
title: The URL Formatter
---

# The Builder

The Builder service is a class that aim at easing URL manipulation. It provides simple public API to quickly modify part of an URL. A side effect is that chaining between `Builder` modifying methods is possible.

For more complex modification it is recommended to directly use the [League\Url\Url](/dev-master/url/manipulation) class.

All modifying methods proxy methods attached to URL parts or URL component corresponding classes. To get a better understanding on how they work and the parameters they need a link to the corresponding URL part API is provided.

## Instantiation

To create a new builder object you can simply create a new instance and lately call the `Builder::setUrl` method

~~~php
use League\Url\Services\Builder;

$urlBuilder = new Builder();
$urlBuilder->setUrl('http://www.example.com');
~~~

Or provide to the `__construct` method a string:

~~~php
use League\Url\Services\Builder;

$urlBuilder = new Builder('http://www.example.com');
~~~

Or a object which exposes the `__toString` method like the `League\Url\Url` object:

~~~php
use League\Url\Url;
use League\Url\Services\Builder;

$urlBuilder = new Builder(Url::createFromServer($_SERVER));
~~~

## Accessing the resulting URL

As the name implied the builder role is only to build an URL. To get access the built URL you must call the `Builder::getUrl` method which returns a `League\Url\Url` object. The `League\Url\Url` follow the PSR-7 `UriInterface` so the following is possible:

~~~php
use League\Url\Services\Builder;

$urlBuilder = new Builder();
$urlBuilder->setUrl('http://www.example.com/path/to/the/sky.php?foo=bar#~typo');
$url = $urlBuilder->getUrl(); //$url is a League\Url\Url object
echo $url; //display 'http://www.example.com/path/to/the/sky.php?foo=bar#~typo'
echo $url->getHost(); //display www.example.com using a PSR-7 UriInterface method
~~~

## Modifying URL query parameters

The following methods proxy the [Query methods](/dev-master/components/query/#modifying-a-query) :

### Adding or Updating query parameters

~~~php
$urlBuilder = new Builder('http://www.example.com//the/sky.php?foo=toto#~typo');
echo $urlBuilder->mergeQueryValues(['foo' => 'bar', 'taz' => ''])->getURL()->getQuery();
//display 'foo=bar&taz'
~~~

### Removing query parameters

~~~php
$urlBuilder = new Builder('http://www.example.com/to/sky.php?foo=toto&p=y+olo#~typo');
echo $urlBuilder->withoutQueryValues(['foo'])->getURL()->getQuery();
//display 'p=y%20olo'
~~~

### Filtering query parameters

~~~php
$urlBuilder = new Builder('http://www.example.com/to/sky.php?foo=toto&p=y+olo#~typo');
echo $urlBuilder->filterQueryValues(function ($value) {
	return ! is_array($value);
})->getURL()->getQuery();
//display 'foo=toto&p=y%20olo'
//will update the query string by removing all array-like parameters
~~~

## Modifying URL path segments

The following methods proxy the [Path methods](/dev-master/components/path/#path-normalization) :

### Appending path segments

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->appendSegments('/foo/bar')->getURL()->getPath();
//display /path/to/the/sky.php/foo/bar
~~~

### Prepending path segments

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->prependSegments('/foo/bar')->getURL()->getPath();
//display /foo/bar/path/to/the/sky.php
~~~

### Replacing a path segment

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->replaceSegment('/foo/bar', 0)->getURL()->getPath();
//display /foo/bar/to/the/sky.php
~~~

### Removing path segments

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->withoutSegments([0, 1])->getURL()->getPath();
//display /the/sky.php
~~~

### Filtering path segments

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->filterSegments(function ($segment) {
	return strpos($segment, 't') === false;
})->getURL()->getPath();
//display /sky.php
~~~

### Removing internal empty segments

~~~php
$urlBuilder = new Builder('http://www.example.com///path//to/the////sky//');
echo $urlBuilder->withoutDuplicateSegments()->getURL()->getPath();
//display /path/to/the/sky/
~~~

### Updating the path extension

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->withExtension('csv')->getURL()->getPath();
//display /path/to/the/sky.csv
~~~

## Modifying URL host labels

The following methods proxy the [Host methods](/dev-master/components/host/#modifying-the-host) :

### Appending host labels

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->appendLabels('be')->getURL()->getHost();
//display example.com.be
~~~

### Prepending host labels

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->prependLabels('shop')->getURL()->getHost();
//display shop.www.example.com
~~~

### Replacing a host label

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->replaceLabel('thephpleague', 1)->getURL()->getHost();
//display shop.www.thephpleague.com
~~~

### Removing host labels

~~~php
$urlBuilder = new Builder('http://www.example.com/path/to/the/sky.php');
echo $urlBuilder->withoutLabels([0])->getURL()->getHost();
//display example.com
~~~

### Filtering host labels

~~~php
$urlBuilder = new Builder('http://www.eshop.com/path/to/the/sky.php');
echo $urlBuilder->filterLabels(function ($label) {
	return strpos($label, 'shop') === false;
})->getURL()->getHost();
//display www.com
//will keep all labels which do not contain the word 'shop'
~~~