<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Behavior', 'Translate');

/**
 * Translatable Behavior, extension of the CakePHP core behavior with further
 * convenience methods
 *
 * @package		i18n
 * @subpackage	i18n.models.behaviors
 */
class TranslatableBehavior extends TranslateBehavior {

/**
 * Prepare data for being displayed in a form
 * Format data per language for each translatable field and remove the related results from the data.
 *
 * Example:
 *		Array(
 *			[Content] => Array (
 *				[id] => 4cc49b4a-daac-495e-91c3-76f6975a6876
 *				[locale] => eng
 *				[content] => English text
 *			)
 *			[contents] => Array(
 *				[0] => Array(
 *					[id] => 1
 *					[locale] => fre
 *					[model] => Content
 *					[foreign_key] => 4cc49b4a-daac-495e-91c3-76f6975a6876
 *					[field] => content
 *					[content] => French text
 *				)
 *				[1] => Array(
 *					[id] => 2
 *					[locale] => eng
 *					[model] => Content
 *					[foreign_key] => 4cc49b4a-daac-495e-91c3-76f6975a6876
 *					[field] => content
 *					[content] => English text
 *				)
 *			)
 * will be converted in
 *		Array(
 *			[Content] => Array(
 *				[id] => 4cc49b4a-daac-495e-91c3-76f6975a6876
 *				[locale] => eng
 *				[content] => Array(
 *					[fre] => French text
 *					[eng] => English text
 *
 * @param Model $Model
 * @param array $data Date to prepare, optional [default: $Model->data]
 * @return array Modified data
 */
	public function prepareI18nData($Model, $data = null) {
		if (is_null($data)) {
			$data = $Model->data;
		}
		$i18nFields = array_keys($this->settings[$Model->alias]);
		foreach($i18nFields as $i18nField) {
			if (!is_numeric($i18nField)) {
				$data[$Model->alias][$i18nField] = Set::combine($data[$this->settings[$Model->alias][$i18nField]], '{n}.locale', '{n}.content');
				unset($data[$this->settings[$Model->alias][$i18nField]]);
			}
		}
		return $data;
	}

}