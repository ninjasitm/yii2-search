<?php

namespace nitm\helpers;

class Hrml
{
	/**
	 * Get certain types of icons
	 * @param string $action
	 * @param string $attribute
	 * @param Object $model
	 * @param mixed $options
	 */
	public static function linkify($text)
	{
		return preg_replace("
			#((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#ie",
			"'<a href=\"$1\" target=\"_blank\">$3</a>$4'",
			$text
		);
	}
}
?>