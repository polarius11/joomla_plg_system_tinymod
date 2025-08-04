<?php
/*
 *  @package   TinyMod
 *  @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos, Copyright (c)2025 Markus Bruhn
 *  @license   GNU General Public License version 3, or later
 */

/**
 * @package     Dionysopoulos\Plugin\System\TinyMod\Extension
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Dionysopoulos\Plugin\System\TinyMod\Extension;

defined('_JEXEC') || die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use JsonException;

class TinyModPlugin extends CMSPlugin implements SubscriberInterface
{

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeRender' => 'onBeforeRender',
		];
	}

	/**
	 * Runs before Joomla renders the HTML document.
	 *
	 * @param   Event  $event
	 *
	 *
	 * @since version
	 */
	public function onBeforeRender(Event $event): void
	{
		// Only applies to front- and backend, when we are going to output an HTML document
		if (
			!(
				$this->getApplication()->isClient('site')
				|| $this->getApplication()->isClient('administrator')
			)
			|| !($this->getApplication()->getDocument() instanceof HtmlDocument)
		) {
			return;
		}

		// Make sure TinyMCE is loaded on the page
		$opts = $this->getApplication()->getDocument()->getScriptOptions('plg_editor_tinymce');

		if (empty($opts) || !is_array($opts)) {
			return;
		}


		// Debug: Ursprüngliche Optionen in Datei schreiben
		//file_put_contents(JPATH_ROOT . '/tmp/tinymce_original.json', json_encode($opts, JSON_PRETTY_PRINT));


		// Load the new options from the plugin's configuration
		$optionsJson = trim($this->params->get('tinymce_options', '') ?? '');

		if (empty($optionsJson)) {
			return;
		}

		try {
			$newOptions = @json_decode($optionsJson, true) ?: null;

			//$newOptions = @json_decode(file_get_contents(__DIR__ . "/newConfig.json"), true) ?: null;
		} catch (JsonException $e) {
			$newOptions = null;
		}

		if (empty($newOptions) || !is_array($newOptions)) {
			return;
		}

		$mergedOptions = $this->mergeJson($opts, ['tinyMCE' => ['default' => $newOptions]]);


		$this
			->getApplication()
			->getDocument()
			->addScriptOptions(
				'plg_editor_tinymce',
				$mergedOptions,
				false
			);

		$finalOptions = $this->getApplication()->getDocument()->getScriptOptions('plg_editor_tinymce');
	}

	function mergeJson($original, $override)
	{
		foreach ($override as $key => $value) {
			if (
				array_key_exists($key, $original)
				&& is_array($original[$key])
				&& is_array($value)
			) {
				$isAssocOriginal = $this->isAssoc($original[$key]);
				$isAssocOverride = $this->isAssoc($value);

				if ($isAssocOriginal && $isAssocOverride) {
					// 🔁 Rekursiv mergen, wenn beide assoziative Arrays sind (Objekte)
					$original[$key] = $this->mergeJson($original[$key], $value);
				} else {
					// 🔁 Bei numerischen Arrays (Listen) → komplett ersetzen
					$original[$key] = $value;
				}
			} else {
				// Neuer oder ersetzter Wert
				$original[$key] = $value;
			}
		}

		return $original;
	}

	/**
	 * Prüft, ob ein Array assoziativ ist (also JSON-Objekt vs. JSON-Array)
	 */
	function isAssoc(array $arr): bool
	{
		if ([] === $arr) return false;
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}
