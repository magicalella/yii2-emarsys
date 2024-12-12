# yii2-magorest
Magorest component for Yii 2 framework

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Run

```
composer require "magicalella/yii2-magorest" "*"
```

or add

```
"magicalella/yii2-magorest": "*"
```

to the require section of your `composer.json` file.

Usage
-----

1. Add component to your config file
```php
'components' => [
    // ...
    'magorest' => [
        'class' => 'magicalella\magorest\Magorest',
        'user' => 'xxxxxx',
        'password' => 'xxxxxx',
        'endpoint' => 'xxxxxx'
    ],
]
```

2. Add new contact to MAGO
```php
$magorest = Yii::$app->magorest;
$result = $magorest->post('getdata/set-customers',$data)
);
```


