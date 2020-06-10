# Uncaptcha

ДАННЫЯ ВЕРСИЯ БИБЛИОТЕКИ НАХОДИТСЯ НА СТАДИИ ТЕСТИРОВАНИЯ
РАБОЧАЯ ВЕРСИЯ БУДЕТ ДОСТУПНА ЗАВТРА

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
// ImageToText и модули распознавания см. в директории /src/
$uncaptcha = $new \Topvisor\Uncaptcha\ImageToText();
```