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

Используйте [composer](https://getcomposer.org/) для установки

composer.json
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

$uncaptcha = new \Topvisor\Uncaptcha\ImageToText();

$uncaptcha->setTimeout(10); // таймаут соедиения
$uncaptcha->setTaskTimeout(240); // таймаут распознавания
$uncaptcha->setDebugLevel(1); // 0 - без лога, 1 - короткий лог, 2 - полный лог

$uncaptcha->setDebugLabel('rc');
$uncaptcha->setUseHTTPS(true);
$uncaptcha->setHost('rucaptcha.com');
$uncaptcha->setV(1); // in.php / res.php style
$uncaptcha->setKey('ab67bdd53139c02b7e343819881f0c0a');

$uncaptcha->setBodyFromFile('https://otvet.imgsmail.ru/download/208657305_63b5eaa23dda595bd1997f979a875a3e_800.jpg');

$result = $uncaptcha->resolve();
if(!$result){
	echo 'Ошибка разгадывания капчи: '.$uncaptcha->getErrorMessage();
	exit();
}

echo 'Капча разгадана: "'.$result.'" за '.$uncaptcha->getTaskElapsed().' сек.';

// логи, полученные в результате разгадывания, будут выведены на экран, если же с ними нужно что-то сделать, их можно получить так:
// $logs = $Uncaptcha->getDebugLog();
//
// в зависимости от того, принята ли капча сервером, можно отправить уведомление серису:
// $Uncaptcha->reportGood(); // капча разгадана верно
// $Uncaptcha->reportGood(); // капча разгадана неверно
```