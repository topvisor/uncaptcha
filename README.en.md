# Uncaptcha

PHP library for solving captcha on sites.

Works in tandem with one of the services you have selected for solving captcha.

The examples use the popular Rukapcha service, the distinguishing feature of which is the high-quality work with Cyrillic captchas:
https://rucaptcha.com/api-rucaptcha.

It supports the following types of captcha:
* ImageToText
* ReCaptcha V2
* ReCaptcha V2 Invisible
* ReCaptcha V3
* FunCaptcha
* GeeTest
* hCaptcha
* Custom - flexible configuration of other types of captcha

# Installation

Use [composer](https://getcomposer.org/) to install.

composer.json:
```json
{
    "repositories":[
		{
			"url":"https://github.com/topvisor/uncaptcha.git",
			"type":"git"
		}
	],
    "require": {
        "topvisor/uncaptcha": "~1.3"
    }
}
```

# An example of using a library to recognize text in a picture

```php
// ImageToText and other recognition modules, see the directory /src/

include_once('%PATH_TO_COMPOSER%/vendor/autoload.php');

// create an object based on the ImageToText module - recognition of text captcha
$uncaptcha = new \Topvisor\Uncaptcha\ImageToText();

$uncaptcha->setTimeout(20); // connection timeout
$uncaptcha->setTaskTimeout(240); // captcha solving timeout
$uncaptcha->setDebugLevel(1); // 0 - no log, 1 - short log, 2 - full log

$uncaptcha->setDebugLabel('rc');
$uncaptcha->setUseHTTPS(true);
$uncaptcha->setHost('rucaptcha.com');
$uncaptcha->setV(1); // in.php / res.php style
$uncaptcha->setKey('%API_KEY%');

$uncaptcha->setBodyFromFile('%URL_IMAGE%');

$result = $uncaptcha->resolve();
if(!$result) {
	echo 'Error capturing the captcha:'.$uncaptcha->getErrorMessage();
	return;
}

echo 'Captcha solved: "'.$result.'" for'.$uncaptcha->getTaskElapsed().' sec.';

```

Logs obtained as a result of solving will be displayed on the screen.
Additionally, they can be accessed through getDebugLog(), for example, to write to the database:

```php
$logs = $uncaptcha->getDebugLog();
```

Depending on whether the captcha is accepted by the server, you can send a notification to the service:

```php
// $uncaptcha->reportGood(); // captcha solved correctly
// $uncaptcha->reportGood(); // captcha is solved incorrectly
```

# Library modules

* FunCaptcha
* FunCaptchaProxyless
* GeeTest
* GeeTestProxyless
* HCaptcha
* HCaptchaProxyless
* ImageToText
* ReCaptchaV2
* ReCaptchaV2Proxyless
* RecaptchaV3
* RecaptchaV3Proxyless
* Custom - a module for customizing captcha parameters via $uncaptcha->setPost()

Each module may contain a different set of methods for the necessary settings.

To start working with one of them, you need to create an object:
```php
$uncaptcha = new \Topvisor\Uncaptcha\ImageToText();

// Next, you need to specify access to the service and options for solving the captcha and start solving (see the example above)
```

# Basic methods

Basic methods are available for all modules.

Service Settings
* setReferalId (string $referalId) - the referalId code can be used in some services
* setUseHTTPS (bool $useHTTPS) - use https
* **setHost (string $host)** - host of the service for recognition
* **setV (int $v)** - version of the service API, two values ​​are supported:
* 1: API style: $host/in.php / simplesite.com/res.php?action=%methodName%
* 2: API style: $host/%methodName%
* **setKey (string $clientKey)** - your API key to the service
* setTimeout() - connection timeout, default 20 seconds

Captcha settings
* setCreateTaskPost (array $createTaskPost) - set of any query parameters, mainly used to configure the Custom module
* setTaskTimeout (int $timeout) - timeout for solving captcha, default is 240 seconds

Resolving process
* **resolve()** - start solving, if successful, will return the result
* getTaskid() - get the task id, the id is created at the beginning of solving, see resolve()
* getTaskElapsed() - get the time taken to solve the captcha
* getErrorMessage() - get the text of the last error
* getResult() - sometimes you need to get more information than just the text from the image. This method will return an object with the result

Debug / log
* setDebugLevel() - 0: without log, 1: short log, 2: detailed log
* setDebugFormat() - 0: text, 1: html
* setDebugLabel() - set label for task
* clearDebugLog() - clear the log
* getDebugLog() - get array strings of log