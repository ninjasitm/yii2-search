<?php

namespace lab1\models\log;

use Yii;

/**
 * This is the model class for collection "lab1-provisioning-log".
 *
 * @property \MongoId|string $_id
 * @property mixed $message
 * @property mixed $level
 * @property mixed $internal_category
 * @property mixed $category
 * @property mixed $timestamp
 * @property mixed $action
 * @property mixed $db_name
 * @property mixed $table_name
 * @property mixed $user
 * @property mixed $user_id
 * @property mixed $ip_addr
 * @property mixed $host
 */
class Entry extends \nitm\models\log\Entry
{
	public static $collectionName = 'lab1-log';
	public static $namespace = "\lab1\models\log";
}
