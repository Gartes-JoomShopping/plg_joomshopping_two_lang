<?php
	
	namespace JoomshoppingTwoLang\Helpers;
	
	/**
	 * @package     ${NAMESPACE}
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 * @since       3.9
	 */
	class HelperString
	{
		private $app;
		public static $instance;
		private static $libPath ;
		/**
		 * helper constructor.
		 *
		 * @throws \Exception
		 * @since 3.9
		 */
		private function __construct ( $options = array() )
		{
			self::$libPath = JPATH_PLUGINS . '/search/joomshopping_two_lang/Libraries';
			require_once ( self::$libPath.'/php-lang-correct/ReflectionTypeHint.php');
			require_once ( self::$libPath.'/php-lang-correct/Text/LangCorrect.php');
			require_once ( self::$libPath.'/php-lang-correct/UTF8.php');
			
			
			
			$this->app = \JFactory::getApplication();
			
			return $this;
		}#END FN
		
		/**
		 * @param array $options
		 *
		 * @return helper
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function instance ( $options = array() )
		{
			if( self::$instance === null )
			{
				self::$instance = new self( $options );
			}
			return self::$instance;
		}#END FN
		
		public function getCorrect( $string ){
			$corrector = new \Text_LangCorrect();
			return $corrector->parse($string) ;
//			return $corrector->parse('смtyf ytправиkmyjq hfcrладки цццюдшауучфьзду.ru') ;
//			echo iconv('utf-8', 'windows-1251', $corrector->parse('смtyf ytправиkmyjq hfcrладки цццюдшауучфьзду.ru'));
		
		}
		
		private $arrRU = array(
			"й","ц","у","к","е","н","г","ш","щ","з","х","ъ",
			"ф","ы","в","а","п","р","о","л","д","ж","э",
			"я","ч","с","м","и","т","ь","б","ю"
		);
		private $arrEN = array(
			"q","w","e","r","t","y","u","i","o","p","[","]",
			"a","s","d","f","g","h","j","k","l",";","'",
			"z","x","c","v","b","n","m",",","."
		);
		
		/**
		 * Переключить в английскую раскладку
		 * @param $string
		 *
		 * @return string|string[]|null
		 *
		 * @since version
		 */
		function correctStringEN ($string)
		{
			return str_replace($this->arrRU, $this->arrEN, $string);
		}
		
		/**
		 * Переключить в Русскую раскладку
		 * @param $string
		 *
		 * @return string|string[]|null
		 *
		 * @since version
		 */
		function correctStringRU ($string)
		{
			return   str_replace($this->arrEN, $this->arrRU , $string);
		}
		
		
	}