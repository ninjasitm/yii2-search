<<<<<<< HEAD
NITM Yii2 Module
============
Widgets created for Ninjas in the Machine

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist nitm/yii2-nitm-module "*"
```

or add

```
"nitm/yii2-nitm-module": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by adding the following to your modules section :

```php
<?= 
	'nitm' => [
		'class' => "nitm\module\Module"
	]; 
?>```

Additionally you can enable the required routes by adding the following to your urlManager section:

```php
<?=

	/**
	 * NITM reply widget routes
	 */
	'reply/<action:\w+>' => 'nitm/reply/<action>',
	'reply/<action:\w+>/<unique:\w+>' => 'nitm/reply/<action>',
	'reply/<action:\w+>/<param:\w+>/<unique:\w+>' => 'nitm/reply/<action>',

	/**
	 * NITM coniguration engine routes
	 */
	'configuration' => 'nitm/configuration',
	'configuration/<action:\w+>' => 'nitm/configuration/<action>',
	'configuration/load/<engine:\w+>' => 'nitm/configuration/',
	'configuration/load/<engine:\w+>/<container:\w+>' => 'nitm/configuration/',
?>```
=======
yii2-nitm-module
=================
