<?php
/*
 *  @package   TinyMod
 *  @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos, Copyright (c)2025 Markus Bruhn
 *  @license   GNU General Public License version 3, or later
 */

namespace Dionysopoulos\Plugin\System\TinyMod\Filter;

defined('_JEXEC') || die;

use JsonException;

abstract class Json
{
	/**
	 * Text filter for the JSON field
	 *
	 * @param   mixed  $text  The incoming text from the user
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	public static function filter($text): string
	{
		if (!is_string($text)) {
			return '';
		}

		if (empty(trim($text ?? ''))) {
			return '';
		}

		try {
			$parsed = @json_decode($text);
		} catch (JsonException $e) {
			return $text;
		}

		if (empty($parsed)) {
			return $text;
		}

		return json_encode($parsed, JSON_PRETTY_PRINT);
	}
}
