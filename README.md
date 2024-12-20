# yii2-emarsys
Emarsys component for Yii 2 framework

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Run

```
composer require "magicalella/yii2-emarsys" "*"
```

or add

```
"magicalella/yii2-emarsys": "*"
```

to the require section of your `composer.json` file.

Usage
-----

1. Add component to your config file
```php
'components' => [
    // ...
    'emarsys' => [
        'class' => 'magicalella\emarsys\Emarsys',
        'client_id' => 'xxxxxx',
        'client_secret' => 'xxxxxx',
        'endpoint_autentication' => 'xxxxxx',
        'endpoint_api' => 'xxxxxx'
    ],
]
```

2. Add new contact to EMARSYS
```php
$emarsys = Yii::$app->emarsys;
$result = $emarsys->post('getdata/set-customers',$data)
);
```


