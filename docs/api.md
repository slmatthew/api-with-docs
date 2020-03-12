# API
Движок API.

```php
$api = new API();
```

## Константы
Коды некоторых ошибок занесены в константы класса.

| Константа                | Значение | Описание                       |
|--------------------------|----------|--------------------------------|
| API_ERROR_UNKNOWN_METHOD | 1        | Неизвестный метод              |
| API_ERROR_MISSING_PARAMS | 2        | Пропущен обязательный параметр |
| API_ERROR_ACCESS_DENIED  | 3        | Передан невалидный `key`       |

## addMethod
Добавление обработчик метода.

Название метода получается из `$_REQUEST['method']`.

| Параметр | Тип      | Описание                                                     |
|----------|----------|--------------------------------------------------------------|
| $name    | string   | Название метода                                              |
| $params  | array    | Обязательные параметры, например: `['username', 'password']` |
| $handler | callable | Обработчик                                                   |
| $version | string   | Версия. Получается из `$_REQUEST['v']`                       |

```php
$api->addMethod('login', ['username', 'password'], function() use($api) {
	$api->wrapResult($_REQUEST['username'] == 'admin' && $_REQUEST['password'] == '12345qwerty');
});
```

## getMethod и getVersion
Получение метода и версии.

```php
$api->getMethod(); // string / если не указан, возвращается `default`
$api->getVersion(); // string / например, 1.0
```

## wrapResult
Отобразить ответ и выйти из скрипта. Принимает единственный аргумент `array $data`.

```php
$api->wrapResult([
	'donate' => true,
	'link' => 'https://donate.slmatthew.dev'
]);

/**
 * Ответ:
 * {"ok":true,"result":{"donate":true,"link":"https:\/\/donate.slmatthew.dev"}}
 */
```

## wrapError
Отобразить ошибку и выйти из скрипта.

| Параметр | Тип    | Описание                                    |
|----------|--------|---------------------------------------------|
| $code    | int    | Код ошибки                                  |
| $message | string | Описание ошибки                             |
| $params  | array  | Дополнительные параметры. По умолчанию `[]` |

```php
$api->wrapError(4, 'some error', [
	'wtf' => [
		'subscribe',
		'to',
		'pewdiepie'
	]
]);

/**
 * Ответ:
 * {"ok":false,"error":{"code":4,"msg":"some error","wtf":["subscribe","to","pewdiepie"]}}
 */
```

## setSecureKey
Установить ключ для доступа к API. Если не задан, ключ не проверяется. Проверяется значение `$_REQUEST['key']`.

Если ключ в запросе не прошел проверку, вернется ошибка с кодом `API::API_ERROR_ACCESS_DENIED`.

```php
$api->setSecureKey('mysupersecretkey');
$api->setSecureKey(''); // проверка отключена
```

## onData
Этот метод нужно вызвать в самом конце файла, после добавления всех нужных методов.

```php
$api = new API();

$api->addMethod(...);
$api->addMethod(...);
$api->addMethod(...);

$api->onData();
```