<?php
/*
** iMaud Studio / Legh Kaurava / Добавлены параметры плагина для более гибкого управления поиском и его результатами.
** ToDo: добавить изображения в результат поиска чтобы можно было показывать их в модулях.

ищем Astrophysics XIS6040 (или Astrophysics XIS-6040 или Astrophysics XIS 6040 и т.п., то есть разделенные буквы цифры). В поиске при этом имеем:

1. Сначала идут товары, в которых содержатся все 3 слова - Astrophysics + XIS + 6040, и именно в такой последовательности.

2. Затем товары, в которых также содержаться все 3 слова, но в любой другой последовательности.

3. Затем идут товары, в которых содержаться только буквенные слова, т.е. в данном случае Astrophysics + XIS, именно в такой последовательности.

4. Затем Astrophysics + XIS, но в любой последовательности.

5. Затем товары, в которых есть любое из буквенных слов, по порядку, т.е. сначала те в которых есть Astrophysic, потом те, в которых есть XIS

*/
//	error_reporting(E_ALL & ~E_NOTICE);

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Profiler\Profiler;
use Joomla\CMS\User\User;

error_reporting(E_ALL);
ini_set('display_startup_errors', 1);
ini_set('display_errors', '1');


defined( '_JEXEC' ) or die( 'Restricted access' );


jimport('joomla.plugin.plugin');

/*JPlugin::loadLanguage( 'plg_search_joomshopping' );*/

class plgSearchJoomshopping_two_lang extends CMSPlugin {

    private $db ;
	
	/**
	 * @var \JoomshoppingTwoLang\Helpers\helper
	 * @since version
	 */
	private $HelperString;
    /**
     * @var bool - Settings => лимит строк при поиске
     * @since version
     */
    private $limit;
    /**
     * @var bool - Settings => Поиск в полном описании
     * @since version
     */
    private $search_description;
    /**
     * @var bool - Settings => Поиск в коротком описании
     * @since version
     */
    private $search_description_short;
    /**
     * @var string Имя столбца названия продукта
     * @since version
     */
    private $prod_name;
    /**
     * @var string Имя столбца Ключевых слов продукта
     * @since version
     */
    private $prod_keywords;
    /**
     * @var string Имя столбца краткого описания продукта
     * @since version
     */
    private $short_description;
    /**
     * @var string Имя столбца полного описания продукта
     * @since version
     */
    private $description;
    /**
     * @var Object параметры плагина
     * @since version
     */
    public $params;
    protected $profiler;
    /**
     * @var mixed|multiLangField
     * @since version
     */
    private $lang;
    /**
     * @var JDatabaseQuery|string
     * @since version
     */
    private $query;
    /**
     * @var User
     * @since version
     */
    private $user;
    /**
     * @var array|null - Результаты найденных товаров
     * @since 3.9
     */
    private $resArrRows;
    /**
     * @var \Joomla\CMS\Application\CMSApplication
     * @since 3.9
     */
    private $app;
    /**
     * аббревиатура - для поска
     * @var string|false
     * @since 3.9
     */
    private $reduction = false ;

    /**
     * plgSearchJoomshopping_two_lang constructor.
     * @param $subject
     * @param array $config
     * @since 3.9
     */
    public function __construct(&$subject, $config = array())
    {
        $this->db =  \Joomla\CMS\Factory::getDBO();
        $this->app = \Joomla\CMS\Factory::getApplication();
        $this->lang = JSFactory::getLang();
        $this->user =  \Joomla\CMS\Factory::getUser();
        parent::__construct($subject, $config ) ;
    }

    function onContentSearchAreas(){
        static $areas = array(
            'joomshopping' => 'Товары'
        );
        return $areas;
    }

    /**
     * Установить настройки плагина для поиска
     *
     * @since version
     */
    protected function getSetting(){
        $this->limit = $this->params->def( 'search_limit', 50 );
        $this->search_description = $this->params->def( 'search_description', 1 );
        $this->search_description_short = $this->params->def( 'search_description_short', 1 );
    }

    /**
     * @param $text
     * @param string $phrase
     * @param string $ordering
     * @param null $areas
     *
     * @return array
     *
     * @throws Exception
     * @since version
     */
    function onContentSearch( $text, $phrase = '', $ordering = '', $areas = null )
    {
        if ($text == '') return array();
        $this->ordering = $ordering ;




        


        # Проверка что ищим в допустимом компоненте
        if (is_array($areas)) {
            if (  !array_intersect($areas, array_keys( $this->onContentSearchAreas() )  )  ) {
                return array();
            }
        }


        # Установить настройки плагина в глобвльные переменные классв
        $this->getSetting() ;

        JLoader::registerNamespace( 'GNZ11' , JPATH_LIBRARIES . '/GNZ11' , $reset = false , $prepend = false , $type = 'psr4' );
        JLoader::registerNamespace('JoomshoppingTwoLang\Helpers',JPATH_PLUGINS.'/search/joomshopping_two_lang/Helpers',$reset=false,$prepend=false,$type='psr4');

        if ( !defined('TWO_LANG_DEBUG') ) define( 'TWO_LANG_DEBUG' , $this->params->get( 'debug_on' , 0 ) );
        if( TWO_LANG_DEBUG ) {
            $this->profiler = Profiler::getInstance('plg_search_joomshopping_two_lang'); #END IF

            $this->profiler->mark('onContentSearch - ');
            $this->profiler->mark('onContentSearch - Start' . PHP_EOL. 'SETTINGS : ' . PHP_EOL  . implode( PHP_EOL  , [
                'limit : '.$this->limit
                ] ) );
        }


        $this->HelperString = \JoomshoppingTwoLang\Helpers\HelperString::instance();

        require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php');
        require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');


        # создать имена столбцов
        $this->prod_name = 'prod.'.$this->db->quoteName($this->lang->get('name'));
        $this->short_description = 'prod.'.$this->db->quoteName($this->lang->get('short_description'));
        $this->description = 'prod.'.$this->db->quoteName($this->lang->get('description'));
        $this->prod_keywords = 'prod.'.$this->db->quoteName($this->lang->get('meta_keyword'));

        # Получить тело основного запроса
        $this->_getQueryBody();





        # Замена двойных пробелов одинарными
        $text = preg_replace('/\s+/', ' ', $text);
        # Убираем пробелы по краям
        $text = \Joomla\String\StringHelper::trim( $text );
        # Make a string lowercase
        $text = \Joomla\String\StringHelper::strtolower( $text );

        # Переключить в Русскую раскладку
        $text_inRu = $this->HelperString->correctStringRU( $text ) ;
        # Переключить в английскую раскладку
        $text_inEn = $this->HelperString->correctStringEN( $text ) ;

        $StopSymbolsPattern  = '/[\[\]\{\}`]/';
        # Проверяем на запрещенные символы при переключении раскладки
        preg_match_all( $StopSymbolsPattern , $text_inEn , $out_inEn , PREG_OFFSET_CAPTURE ) ;
        $text_inEn = ( !count($out_inEn[0] )  ? $text_inEn : null ) ;

        $whereArr = [] ;

        # если $text - это число - будем в начале искать в артикулах товара
        if( self::checkIs_numeric($text) )
        {
            # Установка уловий WHERE и поиск в КОДАХ товара
            $resArrRows = $this->getWhereProductEan($text, $text_inRu, $text_inEn);
        }
        else
        {
            # Условия WHERE для поиска в названиях товара
            $resArrRows = $this->getWhereProductName(  $text , $text_inRu, $text_inEn);
        }

        $categorys = array();
        $manufacturers = array();
        $manufacturers_id = JFactory::getApplication()->input->get('manufacturer_id', array(), 'array');

        #ARR categorys & manufacturers
        if( $this->resArrRows )
        {
            foreach($this->resArrRows as $key => &$row) {
                $row->href = SEFLink('index.php?option=com_jshopping&controller=product&task=view&category_id='.$row->catslug.'&product_id='.$row->slug, 1);
//                $row->href = JRoute::_('index.php?option=com_jshopping&controller=product&task=view&category_id='.$row->catslug.'&product_id='.$row->slug, 1);

                if( !in_array($row->section_id , $categorys )  )
                {
                    $categorys[$row->section_id] = (object) array(
                        'id' => $row->section_id ,
                        'name' => $row->section,
                        'href' => SEFLink('index.php?option=com_jshopping&controller=category&task=view&category_id='.$row->section_id, 1)
                    );
                }#END IF

                $manufacturers[$row->manufacturer_id] = (object) array(
                    'id' => $row->manufacturer_id,
                    'name' => $row->manufacturer_name,
                    'active' => in_array($row->manufacturer_id, $manufacturers_id)
                );

            }
        }#END IF
        \Joomla\CMS\Factory::getApplication()->set('joomshopping_categorys_search', $categorys);
        \Joomla\CMS\Factory::getApplication()->set('joomshopping_manufacturers_search', $manufacturers);

        if( TWO_LANG_DEBUG )  $this->profiler->mark('onContentSearch (Before return )');



        if( TWO_LANG_DEBUG ) {
            # DUMP BD joomshopping_two_lang
            $loadObjectListTime = $this->profiler->getBuffer();
            echo'<pre>';print_r( $loadObjectListTime );echo'</pre>'.__FILE__.' '.__LINE__;



        };

        return $resArrRows ;


        ###########################################################################################################33333
        ###########################################################################################################33333
        ###########################################################################################################33333
        $text = preg_replace('/[^\s\da-zа-яё\-,\.~`]/u', '', $text);


		$text = preg_replace('/(\d+)/u', '${1}', $text);
        $text = preg_replace('/\s+/u', ' ', \Joomla\String\StringHelper::trim($text));

		$full_text = $text;


		$wordsWithoutNumbers = array();
		$words = explode(' ', $text);
		foreach ($words as $key=>$value) {
			$words[$key] = $this->db->escape($value, true);
			if (preg_replace('/\d/u', '', $value)) {
				$wordsWithoutNumbers[] = $words[$key];
			}
		}


		if (!$wordsWithoutNumbers) {
			$wordsWithoutNumbers = $words;
		}
		if (!$wordsWithoutNumbers) {
            return array();
		}

		$revalent_0 = $this->prod_keywords . ' rlike ' . $this->db->quote('[[:<:]]' . implode('[[:>:]][[:<:]]', $words) . '[[:>:]]', false);

		$revalent_1 = array();
        $revalent_3 = array();
        $revalent_4 = array();

		foreach ($words as $key=>$value) {
			$revalent_1[] = $this->prod_keywords . ' like ' . $this->db->quote('%' . $value . '%', false);
		}

		$revalent_1 = implode(' and ', $revalent_1);
		$revalent_2 = $this->prod_keywords . ' like ' . $this->db->quote('%' . implode('%', $wordsWithoutNumbers) . '%', false);
        foreach ($wordsWithoutNumbers as $key=>$value) {
			$revalent_3[] = $this->prod_keywords . ' like ' . $this->db->quote('%' . $value . '%', false);
		}
		$revalent_3 = implode(' and ', $revalent_3);
        foreach ($wordsWithoutNumbers as $key=>$value) {
			$revalent_4[] = $this->prod_keywords . ' like ' . $this->db->quote('%' . $value . '%', false);
		}
		$revalent_4 = implode(' or ', $revalent_4);
		$revalent = 'if((' . $revalent_0 . '), 0, if((' . $revalent_1 . '), 1, if((' . $revalent_2 . '), 2, if((' . $revalent_3 . '), 3, if((' . $revalent_4 . '), 4, 5)))))';
        $this->HelperString = \JoomshoppingTwoLang\Helpers\HelperString::instance();

        $full_text = $this->HelperString->getCorrect( $full_text ) ;

        $whereArr = [] ;
        $whereArr[] = 'prod.`name_ru-RU` = ' . $this->db->quote( $full_text )  ;
        $whereArr[] = 'prod.`name_ru-RU` LIKE ' . $this->db->quote( '%'.$full_text.'%' )  ;
        $whereArr[] = 'product_ean = ' . $this->db->quote($full_text) ;
        $whereArr[] = 'product_ean LIKE ' . $this->db->quote( '%'.$full_text.'%' ) ;

        $where = '('. implode(' or ', $whereArr) . ')';
        $query->where($where);

        $this->db->setQuery( $this->query , 0, $this->limit );
        $resArrRows = $this->db->loadObjectList( 'slug' );

        echo'<pre>';print_r( $resArrRows );echo'</pre>'.__FILE__.' '.__LINE__;
        die(__FILE__ .' '. __LINE__ );



        $this->db->setQuery( $this->query, 0, $this->limit );
        $rows = $this->db->loadObjectList('slug');

		$categorys = array();
        $manufacturers = array();
        $manufacturers_id = JFactory::getApplication()->input->get('manufacturer_id', array(), 'array');
        if ($rows){
			$query = 'SELECT ' . $this->db->quoteName($this->lang->get('name')) . ' as name, manufacturer_id FROM ' . $this->db->quoteName('#__jshopping_manufacturers');
			$this->db->setQuery($query);
			$allManufacturers = $this->db->loadObjectList('manufacturer_id');
            foreach($rows as $key => $row) {
				if ($row->manufacturer_id && isset($allManufacturers[$row->manufacturer_id])) {
					$manufacturers[$row->manufacturer_id] = (object) array(
						'id' => $row->manufacturer_id,
						'name' => $allManufacturers[$row->manufacturer_id]->name,
						'active' => in_array($row->manufacturer_id, $manufacturers_id)
					);
				}
				if ($manufacturers_id && !in_array($row->manufacturer_id, $manufacturers_id)) {
					unset($rows[$key]);
					continue;
				}
                $rows[$key]->href = SEFLink('index.php?option=com_jshopping&controller=product&task=view&category_id='.$row->catslug.'&product_id='.$row->slug, 1);
				$categorys[$row->catslug] = $row->catslug;
            }

			$query = 'SELECT '
                . $this->db->quoteName($this->lang->get('name')) . ' as name, '
                .' category_id FROM ' . $this->db->quoteName('#__jshopping_categories');
			$this->db->setQuery($query);
			$allCategorys = $this->db->loadObjectList('category_id');
			foreach ($categorys as $category_id) {
				if (isset($allCategorys[$category_id])) {
					$categorys[$category_id] = (object) array(
						'id' => $category_id,
						'name' => $allCategorys[$category_id]->name,
						'href' => SEFLink('index.php?option=com_jshopping&controller=category&task=view&category_id='.$category_id, 1)
					);
				} else {
					unset($categorys[$category_id]);
				}
			}
        }

		\Joomla\CMS\Factory::getApplication()->set('joomshopping_categorys_search', $categorys);
        \Joomla\CMS\Factory::getApplication()->set('joomshopping_manufacturers_search', $manufacturers);

        return $rows;
    }



    /**
     * Создать регулярное авражение из строки запроса
     * @param $text
     *
     *
     * @since version
     */
    public function getRLikeText( $text , $word = false ){
        $search = [' ' , '-' ];
        $text = str_replace($search ,'' , $text) ;
        $textArr = $arr = \GNZ11\Document\Text::mbStringToArray( $text   ) ;
        $textString = implode('.?' , $textArr ) ;
        if( $word )
        {
            return  '[[:<:]]'.$textString.'[[:>:]]' ;
        }#END IF
        return '.*'.$textString.'.*' ;
    }

    /**
     * Получить тело основного запроса
     * @throws Exception
     * @since version
     */
    private function _getQueryBody()
    {


        $this->query = $this->db->getQuery(true);
        $l_name = $this->lang->get('name') ;
        $l_keyword = $this->lang->get('meta_keyword') ;
        # Создание условия Select


        $select = "prod.product_id AS slug, 
            product_ean , 
			pr_cat.category_id AS catslug, "
            . $this->db->quoteName( 'prod.' . $l_name )." as title, "
            . $this->db->quoteName( 'prod.' . $l_keyword )." as meta_keyword,
			'2' AS browsernav ,
			prod.product_date_added AS created,
			prod.product_manufacturer_id AS manufacturer_id,"
			. $this->db->quoteName( 'm.' . $l_name )." as manufacturer_name , "."
			
			image AS myimg,
			product_price AS myprice,
			currency_id AS mycurrency,
			cat.category_id AS section_id  
			" . PHP_EOL ;
//cat." . $this->db->quoteName($l_name)." AS section
        $this->query->select($select);

        $select = [
            $this->db->quoteName('cat.'.$l_name , 'section' ) . PHP_EOL ,

        ];
        $this->query->select($select);


        $this->query->from("#__jshopping_products AS prod");
        $this->query->join('LEFT', $this->db->quoteName('#__jshopping_products_to_categories') . " AS pr_cat ON pr_cat.product_id = prod.product_id");
        $this->query->join('LEFT', $this->db->quoteName('#__jshopping_manufacturers') . " AS m ON m.manufacturer_id = prod.product_manufacturer_id");
        $this->query->join('LEFT', $this->db->quoteName('#__jshopping_categories') . " AS cat ON pr_cat.category_id = cat.category_id");
        $this->query->where("prod.product_publish = '1'");
        $this->query->where("cat.category_publish = '1'");
        $this->query->group("prod.product_id");
//        echo 'Query Dump :'.__FILE__ .' Line:'.__LINE__  .$this->query->dump() ;
//        die(__FILE__ .' '. __LINE__ );
        # Способ выборки описания :
        # 0 - Добавить краткое описание к полному
        # 1 - Только краткое описани
        # 2 - Только полное  описани
        switch ($this->search_description)
        {
            case 1:
                $this->query->select($this->short_description . " as text" . PHP_EOL);
                break;

            case 2:
                $this->query->select($this->description . " as text" . PHP_EOL);
                break;
            default:
                $this->query->select("CONCAT(" . $this->short_description . ",' '," . $this->description . ") as text" . PHP_EOL);

        }

        # Установка  сортировки
        switch ( $this->ordering ) {
            case 'alpha':
                $order = "prod." . $this->db->quoteName($this->lang->get('name'))." ASC";
                break;
            case 'newest':
                $order = "prod.product_date_added DESC";
                break;
            case 'oldest':
                $order = "prod.product_date_added ASC";
                break;
            case 'popular':
                $order = " prod.hits DESC ";
                $this->query->order($order);
                break;
            case 'category':
                $order = "cat." . $this->db->quoteName($this->lang->get('name')) . " ASC, prod." . $this->db->quoteName($this->lang->get('name')) . " ASC";
                break;
            default:
                $order = "prod.product_id DESC";
        }

        # Исключение категорий
        $this->query = $this->getWhereExcludeCategorys( $this->query );

        # Если установлено Искать в категории устанавливаем в условие
        if ($category_id = \Joomla\CMS\Factory::getApplication()->input->getInt('category_id')) {
            $this->query->where("cat.category_id = " . $category_id);
        }
    }

	/**
	 * Исключение категорий
	 * @param $query
	 *
	 * @return int|string
	 *
	 * @since version
	 */
	public function getWhereExcludeCategorys ( $query )
	{
		$exclude_categorys = $this->params->get( 'exclude_categorys', '' );
		if( $exclude_categorys !== '' )
		{
			$exclude_categorys = explode( ',', $exclude_categorys );
			foreach ($exclude_categorys as $key => $id)
			{
				$id = (int)trim( $id );
				if( $id > 0 )
				{
					$exclude_categorys[ $key ] = $id;
				} else
				{
					unset( $exclude_categorys[ $key ] );
				}
			}
		}

		if( $exclude_categorys )
		{
			$this->query->where( "cat.category_id NOT IN (" . implode( ',', $exclude_categorys ) . ')' );
		}
		return $this->query ;
	}

	public static function checkIs_numeric( $strTest ){
        # Удаляем ведущие ноли - только для поля артикул
        $textLtrim = ltrim($strTest, '0');
        # Удалить все пробелы мз строки
        $textTestNum = preg_replace('/\s+/', '', $textLtrim);
        return ( is_numeric($textTestNum) ? $textTestNum : false ) ;
    }

    /**
     * Установка уловий WHERE и поиск в КОДАХ товара
     *
     * @param string $text
     * @param array $whereArr
     * @param string $text_inRu переключение в рускую расскладку
     * @param false|string $text_inEn переключение в английскую раскладку
     *
     * @return array|mixed|void
     * @since version
     */
    private function getWhereProductEan(string $text,   string $text_inRu,  $text_inEn = false )
    {

        $textTestNum = self::checkIs_numeric( $text ) ;

        # Если - не искать текст в SQU
        if( !$this->params->get('no_find_text_in_squ' , true ) && !$textTestNum  ) return  ; #END IF

        $text = $textTestNum;


        # Артикулах товара - полное совподение
//        $whereArr[] = 'LOWER (product_ean) = ' . $this->db->quote($text) . PHP_EOL;

//        **********555
//        $whereArr[] = 'prod.`product_ean` = ' . $text . PHP_EOL;
        $whereArr[] = 'prod.`product_id` = ' . $text . PHP_EOL;

        # Артикулах товара - совподение подстроки
        if( !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) LIKE ' . $this->db->quote('%' . $text.'%') . PHP_EOL;
        }else {
//            $whereArr[] = 'product_ean LIKE ' . $this->db->quote(/*'%' .*/ $text/*.'%'*/) . PHP_EOL;
        }#END IF


        if( $text != $text_inRu && !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) = ' . $this->db->quote($text_inRu) . PHP_EOL;
        }#END IF
        if( $text != $text_inEn && !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) = ' . $this->db->quote($text_inEn) . PHP_EOL;
        }#END IF

        if( $text != $text_inRu && !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) LIKE ' . $this->db->quote('%' . $text_inRu . '%') . PHP_EOL;
        }#END IF
        if( $text_inEn && $text != $text_inEn && !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) LIKE ' . $this->db->quote('%' . $text_inEn . '%') . PHP_EOL;
        }#END IF

        /*if( $text !== $textLtrim && !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) LIKE ' . $this->db->quote('%' . $textLtrim . '%') . PHP_EOL;
        }#END IF*/


        # Артикулах товара - поиск по регулярному выражению
//        $whereArr[] = 'LOWER (product_ean) RLIKE "' . $this->getRLikeText($text) . '"' . PHP_EOL;
        if( $text != $text_inRu && !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) RLIKE "' . $this->getRLikeText($text_inRu) . '"' . PHP_EOL;
        }#END IF
        if( $text != $text_inEn && !is_numeric($textTestNum) )
        {
            $whereArr[] = 'LOWER (product_ean) RLIKE "' . $this->getRLikeText($text_inEn) . '"' . PHP_EOL;
        }#END IF

        /*$where = PHP_EOL . '('. implode(' or ', $whereArr) . ')';
        $this->query->where( $where );
        $this->db->setQuery( $this->query , 0, $this->limit );*/

        $this->resArrRows = $this->getResult($whereArr , 'ProductEan');
        return  $this->resArrRows ;
    }

    /**
     * Установка уловий WHERE и поиск в НАЗВАНИЯХ и KEYWORDS товара
     * @param string $text
     * @param string $text_inRu
     * @param string $text_inEn
     * @return array|false
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 17.08.2020 17:58
     *
     */
    private function getWhereProductName(  string $text , string $text_inRu, string $text_inEn)
    {
        ###  Соотношение букв и цифр к пробелам
        $this->checkReduction($text);

        /*if( !$this->reduction &&  )
        {

        }#END IF*/






        $whereArr = [] ;
        $cloneText = $text ;

        $_q_name_ru = $this->db->quoteName('prod.name_ru-RU');
        $_q_keyword = $this->db->quoteName('prod.meta_keyword_ru-RU');
        
        
        # Поиск в поле название товара
        if( $text_inRu != $text )
        {
            $addTextString = $text_inRu ;
        }else{
            $addTextString = $text_inEn ;
        }#END IF


        $_fArr = [' ' , '-'];
        $text = str_replace( $_fArr , '>' , $text );
        $addTextString = str_replace( $_fArr , '>' , $addTextString );

        # Cодержание оператора AGAINST
        $_againstText = $this->db->quote($text.' '.$addTextString.' '.$this->reduction );

        # Cодержание оператора MATCH
        $_matchText =   $_q_name_ru . ',' . $_q_keyword  ;

        $this->query->select("MATCH (" . $_matchText  . ") AGAINST (".$_againstText." IN BOOLEAN MODE ) AS score " . PHP_EOL);

        $whereArr[] = "MATCH (" . $_matchText. ") AGAINST (".$_againstText." IN BOOLEAN MODE ) " . PHP_EOL;

        $this->query->order($this->db->quoteName('score') . ' DESC ');


        $this->resArrRows = $this->getResult($whereArr , 'FULLTEXT' );
        if( $this->resArrRows )
        {
            return $this->resArrRows ;
        }#END IF

        /** #############################################################################################
         * Если ответ не получен ищем по подстроке
         */
        $text = $cloneText ;
        # Получить тело основного запроса
        $this->_getQueryBody();


        if( $text_inRu != $text )
        {
            $addTextString = $text_inRu ;
        }else{
            $addTextString = $text_inEn ;
        }#END IF


        $whereArr = [] ;
        # Название товара - совподение подстроки
        $whereArr[] = 'LOWER (prod.`name_ru-RU`) LIKE ' . $this->db->quote('%' . $text . '%') . PHP_EOL;
        $whereArr[] = 'LOWER (prod.`name_ru-RU`) LIKE ' . $this->db->quote('%' . $addTextString . '%') . PHP_EOL;


        $this->resArrRows =  $this->getResult( $whereArr ,'LIKE' );
        if( $this->resArrRows )
        {
            return  $this->resArrRows  ;
        }#END IF


        /** #############################################################################################
         * Если ответ не поучен ищем по регулярному выражению
         */
        $whereArr = [] ;
        $this->_getQueryBody();

        $textRLike = $this->getRLikeText($text);
        if( $text_inRu != $text )
        {
            $addTextString = $this->getRLikeText($text_inRu) ;
        }else{
            $addTextString = $this->getRLikeText($text_inEn) ;
        }#END IF
        $whereArr[] = 'LOWER ( '.$_q_name_ru.' ) RLIKE "' . $textRLike . '"' . PHP_EOL;
        $whereArr[] = 'LOWER ( '.$_q_name_ru.' ) RLIKE "' . $addTextString . '"' . PHP_EOL;
        $whereArr[] = 'LOWER ( '.$_q_keyword.' ) RLIKE "' . $textRLike . '"' . PHP_EOL;
        $whereArr[] = 'LOWER ( '.$_q_keyword.' ) RLIKE "' . $addTextString . '"' . PHP_EOL;

        $this->resArrRows =  $this->getResult( $whereArr ,'RLIKE' );
        if( $this->resArrRows )
        {
            # Если есть сокращенная стока для кейвордс
            if( $this->reduction  )
            {
                # Если количество найденных товаров не превышает лимит на автоматическое добавление keyword
                if( count( $this->resArrRows ) <= $this->params->get('max_count_product_auto_add_keyword' , 20 ) )
                {
                    # Добавить новый автоматический keyword товарам
                    $this->product_auto_add_keyword() ;
                }#END IF
            }#END IF



            return $this->resArrRows ;
        }#END IF

        return false ;

        ##########################################################################################
    }

    /**
     * Добавить новый автоматический keyword товарам
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 18.08.2020 05:31
     *
     */
    private function product_auto_add_keyword(){
        foreach ( $this->resArrRows as $product_id => &$resArrRow)
        {
            $resArrRow->meta_keyword = $resArrRow->meta_keyword.','.$this->reduction ;
            $object = new stdClass();
            $object->product_id = $product_id;
            $object->{'meta_keyword_ru-RU'} =  $resArrRow->meta_keyword ;
            $result = $this->db->updateObject('#__jshopping_products', $object, 'product_id');
            
            echo'<pre>';print_r( $result );echo'</pre>'.__FILE__.' '.__LINE__;
            
        }#END FOREACH





        $object->title = 'My Custom Record';
        $object->description = 'A custom record being updated in the database.';

        // Update their details in the users table using id as the primary key.
        $result = \Joomla\CMS\Factory::getDbo()->updateObject('#__custom_table', $object, 'id');
    }


    /**
     * Расчет соотошение символов к пробелам и другим знакам
     * Если длина строки меньше 10 знаков а процент более 10 создать Reduction для поиска
     * @param string $text
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 18.08.2020 03:26
     *
     */
    protected function checkReduction(string $content): void
    {
        # Длина входящей строки
        $contentLen = strlen($content);
        # Строка кроме букв и чисел
        $cleanString = preg_replace('/[a-zA-Zа-яА-Я\d]/', '', $content);
        # Длина кроме букв и чисел
        $cleanStringLen = strlen($cleanString);
        # Строка букв и чисел
        $cleanLetters = preg_replace('/[^a-zA-Zа-яА-Я\d]/', '', $content);
        # Длина букв и чисел
        $cleanLettersLen = strlen($cleanLetters);

        # Соотношение - процент пробелов к символам
        $pricent = $cleanStringLen / $cleanLettersLen * 100;
        # Если строка короче 10 символов а процент количества не букв > 10
        if( $contentLen < 10 && $pricent > 10 )
        {
            # аббревиатура - для поска
            $this->reduction = $cleanLetters;
        }#END IF

        # Проверка что в запросе одно слово - и больше 5 букв
        $arrSpace = explode(' ' , $content );
        if( count($arrSpace) > 0 && count($arrSpace) < 2 && strlen($arrSpace[0]) > 5 )
        {
//            $this->reduction = $arrSpace[0] ;
        }#END IF




    }

    /**
     * Условия WHERE для поиска в Keywords товара
     * @param string $text
     * @param string $text_inRu
     * @param string $text_inEn
     * @param JDatabaseDriver $db
     * @param object $query
     * @param array $whereArr
     *
     *
     * @since version
     */
    private function getWhereKeywords(string $text, string $text_inRu, string $text_inEn, JDatabaseDriver $db, object &$query, array &$whereArr)
    {
        #############################################################################################3
        if( $text_inRu != $text )
        {
            $addTextString = $text_inRu ;
        }else{
            $addTextString = $text_inEn ;
        }#END IF


        # Ключевые слова
        # Ключевые слова - полное совподение
        /*$whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) = ' . $this->db->quote($text) . PHP_EOL ;
        if( $text_inRu !== $text )
        {
            $whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) = ' . $this->db->quote($text_inRu) . PHP_EOL ;
        }#END IF
        if( $text_inEn && $text_inEn != $text )
        {
            $whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) = ' . $this->db->quote($text_inEn) . PHP_EOL ;
        }#END IF*/


        # Ключевые слова - соподение подстроки
        /*$whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) LIKE ' . $this->db->quote( '%'.$text.'%' ) . PHP_EOL ;
        if( $text_inRu !== $text )
        {
            $whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) LIKE ' . $this->db->quote( '%'.$text_inRu.'%' ) . PHP_EOL ;
        }#END IF
        if( $text_inEn && $text_inEn != $text )
        {
            $whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) LIKE ' . $this->db->quote( '%'.$text_inEn.'%' ) . PHP_EOL ;
        }#END IF*/

        //        $whereArr = [] ;

        # Ключевые слова - поиск по регулярному выражению
        /*$whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) RLIKE "' .  $this->getRLikeText($text) .'"' . PHP_EOL ;
        if( $text_inRu !== $text )
        {
            $whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) RLIKE "' . $this->getRLikeText($text_inRu) . '"' . PHP_EOL;
        }#END IF
        if( $text_inEn && $text_inEn != $text )
        {
            $whereArr[] = 'LOWER ( prod.`meta_keyword_ru-RU`) RLIKE "' . $this->getRLikeText($text_inEn) . '"' . PHP_EOL;
        }#END IF*/


//        echo'<pre>';print_r( $text );echo'</pre>'.__FILE__.' '.__LINE__;
//        $text = 'F T 2 5 R';
//        $text = '251';
        $_fArr = [' ' , '-'];
        $text = str_replace( $_fArr , '>' , $text );
        $addTextString = str_replace( $_fArr , '>' , $addTextString );

//        $text = 'ft +25';
//        $text = '+ft +25 +ft25 +ft25*';
//        $text = 'ft25*';
//        $text = 'FT-25R';
        $_againstText = ''.$text.'' ;
//        $_againstText .= '('.$text_inRu.')' ;
//        $_againstText .= '('.$text_inEn.')' ;
//        $_againstText = ( $text != $text_inRu  ? ' '.$text .' ' . $text_inRu . ' '  : ' '.$text .' '. $text_inEn );
//        echo'<pre>';print_r( $_againstText );echo'</pre>'.__FILE__.' '.__LINE__;
//        die(__FILE__ .' '. __LINE__ );

//        $_againstText = '+каспер +идентификации';
//        $_againstText = '+рация yaesu';
//        $_againstText = '(+рация +yaesu)(+рация +нфуыг)(+hfwbz +yaesu)';



        $_againstText = $this->db->quote($text .' '. $addTextString );
//        $_addAgainstText = $this->db->quote($addTextString);

        $_againstText = $this->db->quote($text .' '. $addTextString );
        # Cодержание оператора MATCH
        $_matchText =  $this->db->quoteName('prod.name_ru-RU') . ',' . $this->db->quoteName('prod.meta_keyword_ru-RU') ;


        $this->query->select("MATCH (" . $_matchText  . ") AGAINST (".$_againstText." IN BOOLEAN MODE ) AS score " . PHP_EOL);
        $whereArr[] = "MATCH (" . $_matchText. ") AGAINST (".$_againstText." IN BOOLEAN MODE ) " . PHP_EOL;
        $this->query->order($this->db->quoteName('score') . ' DESC ');
    }

    /**
     * Получить результат запроса для переданных условий
     * @param array $whereArr
     *
     * @param string $context
     * @return array|mixed
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 17.08.2020 17:46
     */
    protected function getResult(array $whereArr , string $context)
    {
        $where = PHP_EOL . '(' . implode(' or ', $whereArr) . ')';
        $this->query->where($where);
        $this->db->setQuery($this->query, 0, $this->limit);
        try
        {
            if( TWO_LANG_DEBUG )
                $this->profiler->mark('onContentSearch (Before loadObjectList '.$context.')');


            $resArrRows = $this->db->loadObjectList('slug');

            if( TWO_LANG_DEBUG )
            {
                $this->profiler->mark('onContentSearch (After loadObjectList '.$context.')');
                echo 'Query Dump :' . __FILE__ . ' Line:' . __LINE__ . $this->query->dump();
            }
            // throw new Exception('Code Exception '.__FILE__.':'.__LINE__) ;
        } catch (Exception $e)
        {
            // Executed only in PHP 5, will not be reached in PHP 7
            echo 'Выброшено исключение: ', $e->getMessage(), "\n";
            echo '<pre>';
            print_r($e);
            echo '</pre>' . __FILE__ . ' ' . __LINE__;
            die(__FILE__ . ' ' . __LINE__);
        }
        return $resArrRows;
    }




}



