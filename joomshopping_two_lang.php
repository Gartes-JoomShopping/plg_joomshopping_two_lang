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



# !!! Включактся в настройках плагина
//	error_reporting(E_ALL & ~E_NOTICE);


use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Profiler\Profiler;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\String\StringHelper;


defined( '_JEXEC' ) or die( 'Restricted access' );


jimport('joomla.plugin.plugin');


/*JPlugin::loadLanguage( 'plg_search_joomshopping' );*/

class plgSearchJoomshopping_two_lang extends CMSPlugin {

    /**
     * @var JDatabaseDriver|null
     * @since 3.9
     */
    public $db ;
    /**
     * @var string формат запроса json | html
     * @since 3.9
     */
    public $format;
	/**
	 * @var \JoomshoppingTwoLang\Helpers\helper
	 * @since version
	 */
	private $HelperString;
    /**
     * @var bool - Settings => лимит строк при поиске
     * @since version
     */
    public $limit;
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
    /**
     * @var mixed|multiLangField
     * @since version
     */
    private $lang;
    /**
     * @var JDatabaseQuery|string
     * @since version
     */
    public $query;
    /**
     * @var User
     * @since version
     */
    private $user;
    /**
     * @var array|null - Результаты найденных товаров
     * @since 3.9
     */
    public $resArrRows;
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
     * @var bool|Profiler
     * @since 3.9
     */
    protected $profiler = false;
    /**
     * @var array Результаты профилирования
     * @since 3.9
     */
    private $profilerBuffer = [] ;

    /**
     * @var string Дамп Sql запроса для аомата json
     * @since 3.9
     */
    private $QueryDump;
    /**
     * @var array  Хранение найденых товаров
     * @since 3.9
     */
    public $product;
    /**
     * @var array список категорий
     * @since 3.9
     */
    public $categorys;
    /**
     * @var array список производителей
     * @since 3.9
     */
    public $manufacturers = [];
    /**
     * @var string Ссылка на все результаты поиска
     * @since 3.9
     */
    protected $allResultLink;
    /**
     * @var int|null ID категории для поиска
     * @since 3.9
     */
    public $category_id;
    /**
     * @var array Производители из GET
     * @since 3.9
     */
    public $manufacturer_ids;

    /**
     * plgSearchJoomshopping_two_lang constructor.
     * @param $subject
     * @param array $config
     * @since 3.9
     */
    public function __construct(&$subject, $config = array())
    {


        


        $this->_name = $config['name'];
        $this->_type = $config['type'];

        require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php');
        require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');

        JLoader::registerNamespace( 'GNZ11' , JPATH_LIBRARIES . '/GNZ11' , $reset = false , $prepend = false , $type = 'psr4' );
        JLoader::registerNamespace('JoomshoppingTwoLang\Helpers',JPATH_PLUGINS.'/search/joomshopping_two_lang/Helpers',$reset=false,$prepend=false,$type='psr4');

        $this->db   =   Factory::getDBO();
        $this->app  =   Factory::getApplication();
        $this->user =   Factory::getUser();
        $this->lang =   JSFactory::getLang();
        $this->HelperString = \JoomshoppingTwoLang\Helpers\HelperString::instance();

        parent::__construct($subject, $config ) ;

        if ( !defined('TWO_LANG_DEBUG') ) define( 'TWO_LANG_DEBUG' , $this->params->get( 'debug_on' , 0 ) );

        # Инициализация отображение яровня ошибок во время работы скрипта
        if( TWO_LANG_DEBUG ) {
            # Инициализация отображение яровня ошибок во время работы скрипта
            $this->_init_error_reporting(); #END IF

            if( $this->params->get( 'profile_on' , 0 ) ){
                $this->profiler = Profiler::getInstance('plg_search_joomshopping_two_lang');
                $this->profiler->mark('plg_search_joomshopping_two_lang - __construct ');
            }  #END IF
        }

        $self = $this ;

    }
    public static $self ;
    /**
     * @var INT Уровень отображения ошибок до инициализации _init_error_reporting()
     * @since 3.9
     */
    private $errorlevel ;

    /**
     * Инициализация отображение яровня ошибок во время работы скрипта
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 29.08.2020 07:27
     *
     */
    private function _init_error_reporting(){
        # запоминаем уровень отображение ошибок
        $this->errorlevel = error_reporting();
        if( !$this->params->get( 'error_reporting_on' , 0 ) ) return ;   #END IF
        error_reporting(E_ALL);
        ini_set('display_startup_errors', 1);
        ini_set('display_errors', '1');
    }

    /**
     * Востантвление предедущего уровня ошибок
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 29.08.2020 07:29
     *
     */
    private function _restoreErrorLevel(){
        if( !$this->params->get( 'error_reporting_on' , 0 ) ) return ;   #END IF
        error_reporting( $this->errorlevel );
    }

    /**
     * Создать ссылку
     * @param array $params массив с параметрами запроса e.c.( 'option'=>'com_search' ,  'view'=>'search'  )
     * @param bool  $isSef  true если нужна SEF ссылка
     * @return string
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 29.08.2020 17:19
     *
     */
    protected function createLink(array $params, bool $isSef = false ): string
    {
        return \GNZ11\Joomla\Uri\Uri::createLink( $params,  $isSef );

    }

    /**
     * Проверка что ищим в допустимом компоненте
     * @return string[]
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 29.08.2020 06:58
     *
     */
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
    protected function setSetting(){

        $this->format = $this->app->input->get('format' , false , 'STRING') ;
        $this->category_ids = $this->app->input->get('category_ids' , false , 'ARRAY') ;
        $this->manufacturer_ids = $this->app->input->get('manufacturer_id' , [] , 'ARRAY') ;

        # Если это поиск Ajax при вводе поискового запроса
        if( $this->format )
        {
            # Количество товаров в списке предлагаемых товаров для Ajax поиска
            $this->limit = $this->params->def( 'search_limit', 5 );
        }#END IF

        $this->search_description = $this->params->def( 'search_description', 1 );
        $this->search_description_short = $this->params->def( 'search_description_short', 1 );

        # создать имена столбцов
        $this->prod_name = 'prod.'.$this->db->quoteName($this->lang->get('name'));
        $this->short_description = 'prod.'.$this->db->quoteName($this->lang->get('short_description'));
        $this->description = 'prod.'.$this->db->quoteName($this->lang->get('description'));
        $this->prod_keywords = 'prod.'.$this->db->quoteName($this->lang->get('meta_keyword'));


    }

    public $ignoreParams = [] ;

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
    function onContentSearch( $text,   $ordering = '', $areas = null , $ignoreParams = [] )
    {


        
        
        $text = $this->app->input->get('searchword' , '' , 'RAW') ;
        if ($ignoreParams)
        {
            $this->ignoreParams = $ignoreParams ;
        }#END IF



        if ($text == '') return array();
        $this->ordering = $ordering ;


        # Проверка что ищим в допустимом компоненте
        if (is_array($areas)) {
            # array_intersect — Вычисляет схождение массивов
            if (  !array_intersect($areas, array_keys( $this->onContentSearchAreas() )  )  ) {
                return array();
            }
        }


        if( $this->profiler  ) $this->profiler->mark('Before setSetting Line ' . __LINE__ ); #END IF

        # Установить настройки плагина в глобальные переменные класса
        $this->setSetting() ;




        # Получить тело основного запроса
        $this->_getQueryBody();

        # Замена двойных пробелов одинарными
        $text = preg_replace('/\s+/', ' ', $text);
        # Убираем пробелы по краям
        $text = StringHelper::trim( $text );
        # Make a string lowercase
        $text = StringHelper::strtolower( $text );

        # Переключить в Русскую раскладку
        $text_inRu = $this->HelperString->correctStringRU( $text ) ;
        # Переключить в английскую раскладку
        $text_inEn = $this->HelperString->correctStringEN( $text ) ;


        $StopSymbolsPattern  = '/[\[\]\{\}`]/';
        # Проверяем на запрещенные символы при переключении раскладки
        preg_match_all( $StopSymbolsPattern , $text_inEn , $out_inEn , PREG_OFFSET_CAPTURE ) ;
        $text_inEn = ( !count($out_inEn[0] )  ? $text_inEn : null ) ;

        # Переворот расскладки клавиатуры
        $addTextString = $this->textRuEnValidation($text_inRu, $text, $text_inEn);#END IF

        # если $text - это число - будем в начале искать в артикулах товара
        if( self::checkIs_numeric($text) )
        {
            /**
             * Установка уловий WHERE и поиск в КОДАХ товара
             * @returns  $this->resArrRows
             */
            $this->resArrRows = $this->getWhereProductEan($text, $text_inRu, $text_inEn);
        }
        else
        {

            /**
             * Условия WHERE для поиска в названиях товара
             * @returns  $this->resArrRows
             */
            $this->getWhereProductName(  $text , $addTextString );
        }

        $categorys = array();
        $manufacturers = array();

        $manufacturers_id = $this->app->input->get('manufacturer_id', array(), 'array');


        #ARR categorys & manufacturers
        /**
         * Создать ссылки перейти в категорию &&
         */


        $this->app->set('joomshopping_product_search', $this->resArrRows );





        $this->app->set('joomshopping_two_lang' , $this ) ;
        if( $this->resArrRows )
        {

            /**
             * 1. Получить элемент запроса в данном случае это SELECT
             * @var array $select
             */
            $select = $this->query->select->getElements() ;
            $where = $this->query->where->getElements() ;
            # Очищием елемент запроса SELECT
            $this->query->clear('select');

            

            


            # Очищием елемент запроса ORDER BY

            $this->query->clear('limit');

            if ($this->format != 'json')
            {
                # Очищием елемент запроса ORDER BY
                $this->query->clear('order');
                $this->query->order('section');
            }#END IF

            $newSelect = [
                $this->db->quoteName('cat.category_id' , 'section_id') ,
                $this->db->quoteName('cat.name_ru-RU' , 'section') ,
            ] ;

            if (isset( $select[15] ))
            {
                $newSelect[] =  $select[15];
            }#END IF
            $this->query->select( $newSelect ) ;


//            $this->category_ids = $this->app->input->get('category_id' , false , 'STRING') ;
            # очищаем where от условия ID category для того что бы получить все категории
            if ( is_array( $this->category_ids )  )
            {
                unset( $where[4] );
                $this->query->clear('where');
                $this->query->where( $where ) ;
            }#END IF







//            echo'<pre>';print_r( $this->query->dump() );echo'</pre>'.__FILE__.' '.__LINE__;
//            die(__FILE__ .' '. __LINE__ );



            $limit = $this->format == 'json' ? null : null ;
            $this->db->setQuery($this->query  , 0, $limit );

            $cats = $this->db->loadObjectList();





            $categorySelected = $this->app->input->get( 'category_id', [] , 'ARRAY' ) ;
            $this->categorys = [] ;
            foreach ($cats as $category)
            {
                $i = $category->section_id ;
                if ( !isset( $this->categorys[$i] ) )
                {
                    $this->categorys[$i] = new stdClass() ;
                }#END IF
                $this->categorys[$i]->id =  $category->section_id ;
                $this->categorys[$i]->name =  $category->section ;
                $this->categorys[$i]->active =  in_array($category->section_id , $categorySelected) ;






                # для запроса json - ( экономим время и просто возырвщаем ссылку )
                $this->categorys[$i]->href =  'index.php?option=com_jshopping&controller=category&task=view&category_id='.$category->section_id  ;
                # SEF ссылка - создадим после установка элемента в BODY
                if ($this->format != 'json')
                {
                    $this->categorys[$i]->href = SEFLink('index.php?option=com_jshopping&controller=category&task=view&category_id=' . $category->section_id, 1);
                }
                $this->categorys[$i]->count ++ ;
            }#END FOREACH


            /*echo'<pre>';print_r( $this->categorys );echo'</pre>'.__FILE__.' '.__LINE__;
            die(__FILE__ .' '. __LINE__ );*/
            
            if( $this->profiler )  $this->profiler->mark('onContentSearch (BEFORE FOREACH $this->resArrRows)');

            $_GET_DATA = $this->app->input->getArray([
                'searchword'=> 'RAW' ,
                'category_id'=> 'INT' ,
                'searchphrase'=> 'RAW' ,
                'limit'=> 'INT' ,
                'start'=> 'INT' ,
                'ordering'=> 'STRING' ,
                'view'=> 'STRING' ,
                'option'=> 'STRING' ,
                'Itemid'=> 'INT' ,
                'limitstart'=> 'INT' ,

            ]);
             




            foreach($this->resArrRows as $key => &$row) {


                # Ссылка для товара
                $link = 'index.php?option=com_jshopping&controller=product&task=view&category_id='.$row->catslug.'&product_id='.$row->slug ;
                # для запроса json - ( экономим время и просто возырвщаем ссылку )
                # SEF ссылка - создадим после установка элемента в BODY
                if( $this->format == 'json' )
                {
                    $row->link = $link ;
                }else{
                    $row->href = $this->SEFLink($link , 1);
                }#END IF
            }
            # ПОИСК:Создание данных о производителе
            if( $this->format != 'json' ){
                $this->manufacturers = $this->getManufacturers($this->resArrRows );
            }
        }#END IF



        $this->app->set('joomshopping_manufacturers_search', $this->manufacturers );
        $this->app->set('joomshopping_categorys_search', $this->categorys);

        if( $this->profiler )  $this->profiler->mark('onContentSearch (Before return )');
        if( TWO_LANG_DEBUG ) {
            # DUMP BD joomshopping_two_lang
            if( $this->profiler ){
                $this->profilerBuffer = $this->profiler->getBuffer();  #END IF
            }

            
            if(   $this->format != 'json' )
            {
                echo $this->QueryDump;
                if( $this->profiler )
                {
                    echo'<pre>';print_r( $this->profilerBuffer );echo'</pre>'.__FILE__.' '.__LINE__;
                }#END IF
                #Востантвление предедущего уровня ошибок
                $this->_restoreErrorLevel();
            }#END IF

        };

        return $this->resArrRows ;
    }

    /**
     *
     * @param $row
     * @return array
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 09.09.2020 18:38
     *
     */
    public function getManufacturers( $resArrRows     ): array
    {
        $manufacturers_id = $this->app->input->get('manufacturer_id', array(), 'array');
        $_GET_DATA = $this->app->input->getArray([
            'searchword'=> 'RAW' ,
            'category_id'=> 'INT' ,
            'searchphrase'=> 'RAW' ,
            'limit'=> 'INT' ,
            'start'=> 'INT' ,
            'ordering'=> 'STRING' ,
            'view'=> 'STRING' ,
            'option'=> 'STRING' ,
            'Itemid'=> 'INT' ,
            'limitstart'=> 'INT' ,

        ]);


        if ($this->profiler) $this->profiler->mark('onContentSearch ( Before Uri Create Manufacturer link)');

        foreach($resArrRows as $key => &$row) {
            # если в GET передвнны производители из фильтра -
            $manufacturer_ids = $this->app->input->get('manufacturer_id', [], 'ARRAY');
            if (!in_array($row->manufacturer_id, $manufacturer_ids))
            {
                array_push($manufacturer_ids, $row->manufacturer_id);
            }#END IF


            $_GET_DATA['manufacturer_id'] = $manufacturer_ids;
            $uri = \Joomla\CMS\Uri\Uri::getInstance();
            # Созадем строку с параметрами
            # e.g. : ordering=popular&limit=2&searchword=Профи МОНО&option=com_search&view=search
            $Query = $uri::buildQuery($_GET_DATA);
            # Устанавливаем параметры в Uri
            $uri->setQuery($Query);
            # не SEF Ссылка
            # e.g. : index.php?ordering=popular&limit=2&searchword=Профи МОНО&option=com_search&view=search
            $link = 'index.php' . $uri->toString(array('query', 'fragment'));

            # SEF Ссылка
            # Если последним параметром передается TRUE - то ссылка абсолютная
            $sefLink = \Joomla\CMS\Router\Route::_($link, false, 0, true);


            $manufacturers[$row->manufacturer_id] = (object)array(
                'id' => $row->manufacturer_id,
                'name' => $row->manufacturer_name,
                'active' => in_array($row->manufacturer_id, $manufacturers_id),
                'link' => $sefLink,
            );
        }

        if ($this->profiler) $this->profiler->mark('onContentSearch ( After Uri Create Manufacturer link)');

        return $manufacturers ;

    }


    /**
     * SET Sef Link
     *
     * @param string $link
     * @param int $useDefaultItemId - (0 - current itemid, 1 - shop page itemid, 2 -manufacturer itemid)
     * @param int $redirect
     * @since 3.9
     */
    function SEFLink( $link, $useDefaultItemId = 0, $redirect = 0, $ssl=null){
        /*$this->app = JFactory::getApplication();
        JPluginHelper::importPlugin('jshoppingproducts');
        $dispatcher =JDispatcher::getInstance();
        $dispatcher->trigger('onLoadJshopSefLink', array(&$link, &$useDefaultItemId, &$redirect, &$ssl));*/


        $defaultItemid = getDefaultItemid();
        if( $useDefaultItemId == 2 )
        {
            $Itemid = getShopManufacturerPageItemid();
            if( !$Itemid )
                $Itemid = $defaultItemid;
        }
        elseif( $useDefaultItemId == 1 )
        {
            $Itemid = $defaultItemid;
        }
        else
        {
            $Itemid = $this->app->input->getInt('Itemid');
            if( !$Itemid )
                $Itemid = $defaultItemid;
        }
        if (!preg_match('/Itemid=/', $link)){
            if (!preg_match('/\?/', $link)) $sp = "?"; else $sp = "&";
            $link .= $sp.'Itemid='.$Itemid;
        }

      $link = Route::_($link, (($redirect) ? (false) : (true)), $ssl = 1 );
      return $link;
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
        $select =  [
                $this->db->quoteName( 'prod.product_id' , 'slug' ) . PHP_EOL ,
                $this->db->quoteName( 'product_ean'   ) . PHP_EOL ,
                $this->db->quoteName( 'prod.' . $l_name , 'title' ) . PHP_EOL ,
                $this->db->quoteName( 'prod.' . $l_keyword  , 'meta_keyword' ) . PHP_EOL ,
                $this->db->quoteName( 'prod.product_date_added',  'created' ) . PHP_EOL ,
                $this->db->quoteName( 'prod.product_manufacturer_id',  'manufacturer_id' ) . PHP_EOL ,
                $this->db->quoteName( 'image',  'myimg' ) . PHP_EOL ,
                $this->db->quoteName( 'product_price',  'myprice' ) . PHP_EOL ,
                $this->db->quoteName( 'currency_id',  'mycurrency' ) . PHP_EOL ,

                $this->db->quoteName( 'pr_cat.category_id' , 'catslug'   ) . PHP_EOL ,
                 '2 AS browsernav' . PHP_EOL ,

                $this->db->quoteName(  'cat.category_id'  , 'section_id'   ). PHP_EOL ,
                $this->db->quoteName('cat.'.$l_name , 'section' ) . PHP_EOL ,

                $this->db->quoteName( 'm.' . $l_name ,  'manufacturer_name' ). PHP_EOL ,
        ] ;


        $this->query->select($select);
        $this->query->from("#__jshopping_products AS prod");
        $this->query->join('LEFT', $this->db->quoteName('#__jshopping_products_to_categories') . " AS pr_cat ON pr_cat.product_id = prod.product_id");
        $this->query->join('LEFT', $this->db->quoteName('#__jshopping_manufacturers') . " AS m ON m.manufacturer_id = prod.product_manufacturer_id");
        $this->query->join('LEFT', $this->db->quoteName('#__jshopping_categories') . " AS cat ON pr_cat.category_id = cat.category_id");
        $this->query->where( "prod.product_publish = '1'" . PHP_EOL );
        $this->query->where( "cat.category_publish = '1'" . PHP_EOL );
//        $this->query->group( "prod.product_id");




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
        $whereArr[] = 'prod.`product_id` LIKE ' . $this->db->quote($text.'%') . PHP_EOL;



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
    private function getWhereProductName(  string $text , string $addTextString )
    {
        ###  Соотношение букв и цифр к пробелам
        $this->checkReduction($text);

        $cloneText = $text ;

        $_q_name_ru = $this->db->quoteName('prod.name_ru-RU');
        $_q_keyword = $this->db->quoteName('prod.meta_keyword_ru-RU');


        # Получить количество слов в строке
        $CountWord = \GNZ11\Document\Text::getCountWord( $cloneText );

        if ( $CountWord == 1 )
        {
            # Строим запрос для FULLTEXT
            $this->buildQueryFullText($text, $addTextString );
            if( $this->resArrRows ) return $this->resArrRows ; #END IF
        }#END IF









        /** #############################################################################################
         * Если ответ не получен ищем по подстроке
         */
        $text = $cloneText ;
        $whereArr = [] ;
        # Пересоздать тело основного запроса
        $this->_getQueryBody();
        # Название товара - совподение подстроки
        $whereArr[] = 'LOWER (prod.`name_ru-RU`) LIKE ' . $this->db->quote('%' . $text . '%') . PHP_EOL;
        $whereArr[] = 'LOWER (prod.`name_ru-RU`) LIKE ' . $this->db->quote('%' . $addTextString . '%') . PHP_EOL;

        $this->resArrRows =  $this->getResult( $whereArr ,'LIKE' );
        if( $this->resArrRows )
        {
            return  $this->resArrRows  ;
        }else{
            # Пересоздать тело основного запроса
            $this->_getQueryBody();
            # Строим запрос для FULLTEXT
            $this->buildQueryFullText($text, $addTextString );
            if( $this->resArrRows ) return $this->resArrRows ;
        }#END IF


        /** #############################################################################################
         * Если ответ не поучен ищем по регулярному выражению
         */
        $whereArr = [] ;
        $this->_getQueryBody();

        $textRLike = $this->getRLikeText($text);
        $addTextString = $this->getRLikeText($addTextString) ;
        /*if( $text_inRu != $text )
        {
            $addTextString = $this->getRLikeText($text_inRu) ;
        }else{

        }#END IF*/
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
        $result = Factory::getDbo()->updateObject('#__custom_table', $object, 'id');
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
        $cleanString = preg_replace('/[a-zA-Zа-яА-Я\d]/u', '', $content);
        # Длина кроме букв и чисел
        $cleanStringLen = strlen($cleanString);
        # Строка букв и чисел
        $cleanLetters = preg_replace('/[^a-zA-Zа-яА-Я\d]/u', '', $content);

        
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
        $limit = $this->app->input->get('limit' , null , 'INT');

        $where = PHP_EOL . '(' . implode(' or ', $whereArr) . ')';
        $this->query->where($where);


        $this->saveQuery = clone $this->query ;

        # Если в GET Искать в категории
        if( $this->format != 'json' && $this->category_ids  )
        {
            if ( is_array( $this->category_ids ) )
            {
                $this->query->where("cat.category_id IN ( " . implode( ',', (array)$this->category_ids ) . " )" );
            }else{
                $this->query->where("cat.category_id = ".$this->category_ids );
            }#END IF

        }#END IF

        # если в GET передвнны производители из фильтра -
        if (count( $this->manufacturer_ids ) && !in_array( 'manufacturer_id' , $this->ignoreParams) )
        {
            $this->query->where( PHP_EOL. "m.manufacturer_id IN ( " . implode( ',', $this->manufacturer_ids ).' ) ' )   ;
        }#END IF

        $limit = ($this->format == 'json' ? 15 : $limit ) ;
        $this->db->setQuery($this->query, 0, $limit );



        /*echo'<pre>';print_r( $this->query->dump() );echo'</pre>'.__FILE__.' '.__LINE__;
        die(__FILE__ .' '. __LINE__ );*/



        try
        {
            $resArrRows = $this->db->loadObjectList('slug');
        } catch (Exception $e)
        {
            // Executed only in PHP 5, will not be reached in PHP 7
            echo 'Выброшено исключение: ', $e->getMessage(), "\n";
            echo '<pre>'; print_r($e); echo '</pre>' . __FILE__ . ' ' . __LINE__;
            die(__FILE__ . ' ' . __LINE__);
        }

        if( $this->profiler )  $this->profiler->mark('onContentSearch (After loadObjectList '.$context.')');  #END IF
        $this->QueryDump =  strip_tags( $this->query->dump() ) ;

        return $resArrRows;
    }

    /**
     * Точка входа Ajax
     *
     * @throws Exception
     * @since 3.9
     * @author Gartes
     * @creationDate 2020-04-30, 16:59
     * @see {url : https://docs.joomla.org/Using_Joomla_Ajax_Interface/ru }
     */
    public function onAjaxJoomshopping_two_lang ()
    {
        $arrInp = [
            'option' =>  'com_search' ,
            'view' =>  'search' ,
            'start' =>  0 ,
            'category_id' =>  'INT' ,
            'searchphrase' => 'WORD' ,
            'ordering' => 'WORD' ,
            'limit' => 'INT' ,
            'searchword' => 'STRING' ,
            'areas' => 'ARRAY' ,
//            'arrSearchResult' => 'STRING' ,
            'task' => 'STRING' ,
            'method' => 'STRING' ,
        ];

        $arr = $this->app->input->getArray( $arrInp );
        /**
         * @var STRING $ordering
         * @var INT $limit
         * @var STRING $searchword
         * @var STRING $areas
         * @var array $arrSearchResult
         * @var STRING $task
         * @var STRING $method
         */
        extract($arr) ;
        $this->searchword = $searchword ;

        $Helper = \JoomshoppingTwoLang\Helpers\Helper::instance( $this->_name , $this->_type , $this->params );

        /**
         * Методы для Helper
         */
        if ( !empty( $method ) ) {

            $res =  $Helper->{$method}() ;
            try
            {
                // Code that may throw an Exception or Error.
                echo new \Joomla\CMS\Response\JsonResponse( $res );
                // throw new Exception('Code Exception '.__FILE__.':'.__LINE__) ;
            }
            catch (Exception $e)
            {
                echo'<pre>';print_r( $res );echo'</pre>'.__FILE__.' '.__LINE__;
                
                // Executed only in PHP 5, will not be reached in PHP 7
                echo 'Выброшено исключение: ',  $e->getMessage(), "\n";
                echo'<pre>';print_r( $e );echo'</pre>'.__FILE__.' '.__LINE__;
                die(__FILE__ .' '. __LINE__ );
            }

            die();
        }#END IF





        # Проверить слово на соответствие системной команде
        if (TWO_LANG_DEBUG && $Helper->checkSystemCommand( $this->searchword ) ) {
            $command = \GNZ11\Document\Text::camelCase($searchword, []);

            return $Helper->{$command}();

            echo new \Joomla\CMS\Response\JsonResponse( [] );
            die();
        }

        # для операции - Получение ссылок для товаров и категорий
        if( $task == 'getSefLink' )
        {
            $retRequest = [] ;
            $arrSearchResult = $this->app->input->get('arrSearchResult' , false , 'RAW' ) ;
            $Registry = new Joomla\Registry\Registry();
            $products =  $Registry->loadString((string)$arrSearchResult)->get('products.list') ;
            $categorys =  $Registry->loadString((string)$arrSearchResult)->get('categorys') ;
            $show_all =  $Registry->loadString((string)$arrSearchResult)->get('show_all') ;

            # Ссылка - Все результаты поиска
            $retRequest['show_all'] = $this->getLinkResult($isSef = true , $addParamsArr=[] ) ;
            foreach ($categorys as $id=>&$category)
            {
                # Ссылка для перехода в категорию
                $category->href = $this->SEFLink( $category->href , 1) ;
                # Сосздать ссылку Поиск в категории
                $category->hrefSearchInCategory = $this->getLinkResult( true , ['category_id[]'=>$id ,'searchword'=>$this->searchword ] ) ;
            }#END FOREACH

            $retRequest['categorys'] = $categorys ;

            foreach ($products as &$product)
            {
                $product->link = $this->SEFLink( $product->link , 1) ;
            }#END FOREACH
            $retRequest['products'] = $products ;


            echo new \Joomla\CMS\Response\JsonResponse( $retRequest );
            die();
        }#END IF

        # Найти товары
        $this->product = $this->onContentSearch( $searchword ,    $ordering  , $areas );

        # Получить ссылку на все результаты поиска
        $this->allResultLink = $this->getLinkResult( );

        $res['products']['list'] = $this->product ;
        $res['products']['html'] = $this->loadTemplate('products' ) ;
        $res['categorys'] = $this->categorys;
        $res['manufacturers'] = $this->manufacturers;
        $res['show_all'] = $this->allResultLink ;
        // $this->app->set('joomshopping_categorys_search', $this->categorys);



        if( TWO_LANG_DEBUG ) {
            $res['debug']['profiler'] =  $this->profilerBuffer ;
            $res['debug']['QueryDump'] =  $this->QueryDump ;
        }

        echo new \Joomla\CMS\Response\JsonResponse( $res );
        die();


         /*

        JLoader::registerNamespace( 'GNZ11', JPATH_LIBRARIES . '/GNZ11', $reset = false, $prepend = false, $type = 'psr4' );


        $helper = \CountryFilter\Helpers\Helper::instance( $this->params );
        $task = $this->app->input->get( 'task', null, 'STRING' );

        try
        {
            // Code that may throw an Exception or Error.
            $results = $helper->$task();
        } catch (Exception $e)
        {
            $results = $e;
        }
        return $results;*/
    }

    /**
     * Получить ссылку на все результаты поиска
     * @param bool $isSef true - если нужна sef ссылка
     * @param array $addParamsArr
     * @return string
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 29.08.2020 01:08
     */
    protected function getLinkResult($isSef = false , $addParamsArr=[] ){
        $arrInp = [
            'start' =>  0 ,
            // 'category_id' =>  'INT' ,
            'searchphrase' => 'WORD' ,
            'ordering' => 'WORD' ,
            'limit' => 'INT' ,
            'searchword' => 'STRING' ,
//            'areas' => 'ARRAY' ,
        ];
        $params = $this->app->input->getArray($arrInp);

        # Параметры запроса
        $params['option'] = 'com_search';
        $params['view'] = 'search';
        $params = array_merge( $params , $addParamsArr ) ;
        return $this->createLink($params, $isSef);
    }


    /**
     * Загрузите файл макета плагина. Эти файлы могут быть переопределены с помощью стандартного Joomla! Шаблон
     *
     * Переопределение :
     *                  JPATH_THEMES . /html/plg_{TYPE}_{NAME}/{$layout}.php
     *                  JPATH_PLUGINS . /{TYPE}/{NAME}/tmpl/{$layout}.php
     *                  or default : JPATH_PLUGINS . /{TYPE}/{NAME}/tmpl/default.php
     *
     *
     * переопределяет. Load a plugin layout file. These files can be overridden with standard Joomla! template
     * overrides.
     *
     * @param string $layout The layout file to load
     * @param array  $params An array passed verbatim to the layout file as the `$params` variable
     *
     * @return  string  The rendered contents of the file
     *
     * @since   5.4.1
     */
    private function loadTemplate ( $layout = 'default' )
    {
        $path = \Joomla\CMS\Plugin\PluginHelper::getLayoutPath(  $this->_type , $this->_name , $layout );
        // Render the layout
        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Переворот расскладки клавиатуры
     * @param string $text_inRu
     * @param string $text
     * @param string $text_inEn
     * @return string
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 08.09.2020 01:49
     *
     */
    private function textRuEnValidation(string $text_inRu, string $text, string $text_inEn): string
    {
        # Поиск в поле название товара
        if ($text_inRu != $text)
        {
            $addTextString = $text_inRu;
        } else
        {
            $addTextString = $text_inEn;
        }
        return $addTextString;#END IF
    }

    /**
     *
     * @param string $text
     * @param string $addTextString
     * @param array $_q_name_ru
     * @param array $_q_keyword
     * @param array $whereArr
     * @return array
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 08.09.2020 01:55
     *
     */
    private function buildQueryFullText(string $text, string $addTextString ): array
    {
        $whereArr = [] ;
        $_q_name_ru = $this->db->quoteName('prod.name_ru-RU');
        $_q_keyword = $this->db->quoteName('prod.meta_keyword_ru-RU');

        $_fArr = [ ' ' , '-' , '*' ];
        $_replaceArr = [ '>' , '>' , '' ];
        $text = str_replace( $_fArr , $_replaceArr , $text );
        $addTextString = str_replace( $_fArr , $_replaceArr , $addTextString );

        # Cодержание оператора AGAINST
        $_againstText = $this->db->quote(
            $text .
            ($addTextString?' ' . $addTextString:'') .
            ($this->reduction?' ' . $this->reduction:'')
        );



//        echo'<pre>';print_r( $_againstText );echo'</pre>'.__FILE__.' '.__LINE__;


        # Содержание оператора MATCH
        $_matchText = $_q_name_ru . ',' . $_q_keyword;
        $this->query->select("MATCH (" . $_matchText . ") AGAINST (" . $_againstText . " IN BOOLEAN MODE ) AS score " . PHP_EOL);
        $this->query->order($this->db->quoteName('score') . ' DESC ');

        $whereArr[] = "MATCH (" . $_matchText . ") AGAINST (" . $_againstText . " IN BOOLEAN MODE ) " . PHP_EOL;





        $this->resArrRows = $this->getResult($whereArr , 'FULLTEXT' );
        return $this->resArrRows;
    }




}






