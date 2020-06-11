# Uncaptcha

PHP библиотека для разгадывания капч на сайтах.

Работает в паре с одним из выбранных вами сервисов для разгадывания капч.

В примерах используется популярный сервис Рукапча, отличительной особенностью которого является качественная работа с кириллическими капчами:
https://rucaptcha.com/api-rucaptcha.

Поддерживает работу с сервисами следующих вид капч:
* ReCaptcha V2
* ReCaptcha V2 Invisible
* ReCaptcha V3
* FunCaptcha
* GeeTest
* hCaptcha
* Custom - гибкая настройка других видов капч

# Установка

Используйте [composer](https://getcomposer.org/) для установки.

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

# Пример использования библиотеки для распознавания текста на картинке

```php
// ImageToText и другие модули распознавания см. в директории /src/

include_once('%PATH_TO_COMPOSER%/vendor/autoload.php');

// создаем объект на основе модуля ImageToText - распознавание текстовой капчи
$uncaptcha = new \Topvisor\Uncaptcha\ImageToText();

$uncaptcha->setTimeout(20); // таймаут соедиения
$uncaptcha->setTaskTimeout(240); // таймаут разгадывания
$uncaptcha->setDebugLevel(1); // 0 - без лога, 1 - короткий лог, 2 - полный лог

$uncaptcha->setDebugLabel('rc');
$uncaptcha->setUseHTTPS(true);
$uncaptcha->setHost('rucaptcha.com');
$uncaptcha->setV(1); // in.php / res.php style
$uncaptcha->setKey('%API_KEY%');

$uncaptcha->setBodyFromFile('%URL_IMAGE%');

$result = $uncaptcha->resolve();
if(!$result){
	echo 'Ошибка разгадывания капчи: '.$uncaptcha->getErrorMessage();
	exit();
}

echo 'Капча разгадана: "'.$result.'" за '.$uncaptcha->getTaskElapsed().' сек.';

```

Логи, полученные в результате разгадывания, будут выведены на экран.
Дополнительно к ним можно получить доступ через $uncaptcha->getDebugLog(), например для записи в БД:

```php
$logs = $uncaptcha->getDebugLog();
```

В зависимости от того, принята ли капча сервером, можно отправить уведомление серису:

```php
// $uncaptcha->reportGood(); // капча разгадана верно
// $uncaptcha->reportGood(); // капча разгадана неверно
```

# Модули библиотеки

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
* Custom - модуль, для проивзольной настройки параметров капчи через $uncaptcha->setPost()

Каждый модуль может содержать различный набор методов для необходимой настройки

Для начала работы с одной из них необходимо создать объекта:
```php
$uncaptcha = new \Topvisor\Uncaptcha\ImageToText();

// далее необходимо указать доступ к сервису и опции для разгадывания капчи и запустить разгадывание (см. пример выше)
```

# Базоые методы

Базовые методы доступны для всех модулей

* $uncaptcha->setReferalId(string $referalId) - код referalId может испоьзвоаться в некоторых сервисах
* $uncaptcha->setUseHTTPS(bool $useHTTPS) - использовать https
* **$uncaptcha->setHost(string $host)** - хост сервиса для распознавания
* **$uncaptcha->setV(int $v)** - версия API сервиса, поддерживаеся два значения:
** 1: API style: $host/in.php / simplesite.com/res.php?action=%methodName%
** 2: API style: $host/%methodName%
* **$uncaptcha->setKey(string $clientKey)** - ваш API ключ к сервису
* $uncaptcha->setCreateTaskPost(array $createTaskPost) - проивзольный набор параметров запроса, в основном используется для настройки модуля Custom
* $uncaptcha->setTaskTimeout(int $timeout) - таймаут на разгадывание капчи, по умолчанию 240 секунд

* **$uncaptcha->resolve()** - запустить разгадывание, вернет результат
* $uncaptcha->getTaskElapsed() - получить время, затраченное на разгадывание капчи
* $uncaptcha->getTaskid() - получить id задачи, id создается при начале разгадывания, см. $uncaptcha->resolve()
* $uncaptcha->getErrorMessage() - получить текст последней ошибки
* $uncaptcha->getResult() - Иногда требуется получить больше информации, чем просто текст с картинки. Этот метод вернет объект с результатом