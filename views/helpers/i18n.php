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

/**
 * i18n Helper
 *
 * i18n view helper allowing to easily generate common i18n related controls
 *
 * @package i18n
 * @subpackage i18n.views.helpers
 */
class i18nHelper extends AppHelper {
	
/**
 * Helpers
 *
 * @var array $helpers
 */
	public $helpers = array('Html', 'Form', 'Js');
	
/**
 * Base path for the flags images, with a trailing slash
 * 
 * @var string $basePath
 */
	public $basePath = '/i18n/img/flags/';
	
/**
 * Displays a list of flags
 * 
 * @param array $options Options with the following possible keys
 * 	- basePath: Base path for the flag images, with a trailing slash
 * 	- class: Class of the <ul> wrapper
 * 	- id Id of the wrapper
 * @return void
 */
	public function flagSwitcher($options = array()) {
		$_defaults = array(
			'basePath' => $this->basePath,
			'class' => 'languages',
			'id' => '');
		$options = array_merge($_defaults, $options);
		$langs = $this->availableLanguages();
		
		$out = '';
		if (!empty($langs)) {
			$out .= '<ul class="' . $options['class'] . '"' . ife(empty($options['id']), '', ' id="' . $options['id'] . '"') . '>';
			foreach($langs as $lang) {
				$class = $lang;
				if ($lang == Configure::read('Config.language')) {
					$class .= ' selected';
				}
				$url = array_merge($this->params['named'], $this->params['pass'], compact('lang'));
				$out .= 
				'<li class="' . $class . '">' .
					$this->Html->link($this->flagImage($lang, $options), $url, array('escape' => false)) .
				'</li>';
			}
			$out .= '</ul>';
		}
		
		return $out;
	}

/**
 * Returns the correct image from the language code
 * 
 * @param string $lang Long language code
 * @param array $options Options with the following possible keys
 * 	- basePath: Base path for the flag images, with a trailing slash
 * @return string Image markup
 */
	public function flagImage($lang, $options = array()) {
		static $L10n = null;
		if (is_null($L10n)) {
			App::import('Core', 'L10n');
			$L10n = new L10n();
		}
		$_defaults = array('basePath' => $this->basePath);
		$options = array_merge($_defaults, $options);
		return $this->Html->image($options['basePath'] . $L10n->map($lang) . '.png');
	}
	
/**
 * Returns all the available languages on the website
 * 
 * @param boolean $includeCurrent Whether or not the current language must be included in the result
 * @return array List of available language codes 
 */	
	public function availableLanguages($includeCurrent = true) {
		$languages = Configure::read('Config.languages');
		if (defined('DEFAULT_LANGUAGE')) {
			array_unshift($languages, DEFAULT_LANGUAGE);
		}

		if (!$includeCurrent && in_array(Configure::read('Config.language'), $languages)) {
			unset($languages[array_search(Configure::read('Config.language'), $languages)]);
		}
		return $languages;
	}

/**
 * Display multiple inputs for a given field (for each available language)
 * Each input will have a class "i18n langCode" (e.g: "lang fr" or "lang en")
 *
 * @TODO Add correct default div classes if none is passed
 * @param string $fieldName
 * @param array $options Options form the Form input method, optional [default: array()]
 * 	The following option values can contain the ":lang" placeholder which will be replaced with the lang code: label, legend
 * @param Helper $Helper Helper to use to display the field, optional [default: $this->Form]
 * @param string $method Method name to call for the helper, optional [default: input]
 * @param mixed $param1 Other params for the helper method (3rd param passed)
 * [...]
 * @return string Form inputs
 * @access public
 */
	public function input($fieldName, $options = array(), $Helper = null, $method = 'input') {
		$result = '';
		if (is_null($Helper)) {
			$Helper = $this->Form;
		}
		$params = array($fieldName, $options);
		if (func_num_args() > 4) {
			$funcParams = func_get_args();
			$params = array_merge($params, array_slice($funcParams, 4));
		}

		$value = $this->Form->value($fieldName);
		if (!empty($value) && !is_array($value)) {
			$result = call_user_func_array(array($Helper, $method), $params);
		} else {
			$error = $this->tagIsInvalid();
			$View = ClassRegistry::getObject('view');
			$entity = join('.', $View->entity());
			$langs = $this->availableLanguages();

			if ($error) {
				$this->Form->validationErrors = Set::remove($this->Form->validationErrors, $entity);
				foreach($langs as $lang) {
					$this->Form->validationErrors = Set::insert($this->Form->validationErrors, $entity . '.' . $lang, $error);
				}
			}

			foreach($langs as $lang) {
				$opt = $options;
				foreach(array('label', 'legend') as $key) {
					if (!empty($opt[$key])) {
						$opt[$key] = String::insert($opt[$key], compact('lang'));
					}
				}
				if (empty($opt['div'])) {
					$opt['div'] = ($Helper == $this->Form) ? 'input' : '';
				}
				$opt['div'] = trim($opt['div'] . ' lang ' . $lang);

				$params[0] = $entity . '.' . $lang;
				$params[1] = $opt;
				$result .= call_user_func_array(array($Helper, $method), $params);
			}
		}
		return $result;
	}

/**
 * Return code for links allowing to show / hide i18n fields in the form for each language
 * Javascript code is added to the JS buffer
 *
 * @param array $translations Mapping between lang code and link text, optional [default: language name]
 * @param array $options Options. Possible keys are:
 * 	- buffer: true to send Js code to the Js buffer, false otherwise [default: true]
 * 	- effectIn: effect for appearing
 * 	- effectOut: effect for disappearing
 * @return string HTML code
 */
	public function inputSwitcher($translations = array(), $options = array()) {
		static $L10n = null;

		$_defaults = array('buffer' => true, 'effectIn' => 'slideIn', 'effectOut' => 'slideOut');
		$options = array_merge($_defaults, $options);

		$langs = $this->availableLanguages();
		foreach($langs as $lang) {
			if (!array_key_exists($lang, $translations)) {
				if (is_null($L10n)) {
					App::import('Core', 'L10n');
					$L10n = new L10n();
				}
				if ($langData = $L10n->catalog($lang)) {
					$translations[$lang] = __($langData['language'], true);
				}
			}
		}

		$out = $script = '';
		if (!empty($translations)) {
			$id = String::uuid();
			$out .= '<ul class="input-switcher" id="' . $id .'">';
			$fadeOut = $this->Js->get('.lang')->effect($options['effectOut']);
			$removeClasses = '$("#' . $id . ' li").removeClass("active");';

			$first = true;
			foreach($translations as $lang => $name) {
				$id = String::uuid();
				$out .= '<li id="' . $id .'"><a href="#">' . $name . '</a></li>';
				$selector = '$("#' . $id . '")';
				$fadeIn = $this->Js->get('.' . $lang)->effect($options['effectIn']);
				$script .= $this->Js->get('#' . $id)->event('click', $fadeOut . $fadeIn . $removeClasses . $selector . '.addClass("active");');
				if ($first) {
					$first = false;
					$script .= $selector . '.click();';
				}
			}

			$out .= '</ul>';
		}

		$view = ClassRegistry::getObject('view');
		if (isset($view->loaded['js']) && $options['buffer'] === true) {
			$view->loaded['js']->buffer($script);
		} else {
			$out .= $this->Html->scriptBlock($script);
		}

		return $out;
	}

}
