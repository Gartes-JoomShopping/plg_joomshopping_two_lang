<?php

/***********************************************************************************************************************
 * ╔═══╗ ╔══╗ ╔═══╗ ╔════╗ ╔═══╗ ╔══╗  ╔╗╔╗╔╗ ╔═══╗ ╔══╗   ╔══╗  ╔═══╗ ╔╗╔╗ ╔═══╗ ╔╗   ╔══╗ ╔═══╗ ╔╗  ╔╗ ╔═══╗ ╔╗ ╔╗ ╔════╗
 * ║╔══╝ ║╔╗║ ║╔═╗║ ╚═╗╔═╝ ║╔══╝ ║╔═╝  ║║║║║║ ║╔══╝ ║╔╗║   ║╔╗╚╗ ║╔══╝ ║║║║ ║╔══╝ ║║   ║╔╗║ ║╔═╗║ ║║  ║║ ║╔══╝ ║╚═╝║ ╚═╗╔═╝
 * ║║╔═╗ ║╚╝║ ║╚═╝║   ║║   ║╚══╗ ║╚═╗  ║║║║║║ ║╚══╗ ║╚╝╚╗  ║║╚╗║ ║╚══╗ ║║║║ ║╚══╗ ║║   ║║║║ ║╚═╝║ ║╚╗╔╝║ ║╚══╗ ║╔╗ ║   ║║
 * ║║╚╗║ ║╔╗║ ║╔╗╔╝   ║║   ║╔══╝ ╚═╗║  ║║║║║║ ║╔══╝ ║╔═╗║  ║║─║║ ║╔══╝ ║╚╝║ ║╔══╝ ║║   ║║║║ ║╔══╝ ║╔╗╔╗║ ║╔══╝ ║║╚╗║   ║║
 * ║╚═╝║ ║║║║ ║║║║    ║║   ║╚══╗ ╔═╝║  ║╚╝╚╝║ ║╚══╗ ║╚═╝║  ║╚═╝║ ║╚══╗ ╚╗╔╝ ║╚══╗ ║╚═╗ ║╚╝║ ║║    ║║╚╝║║ ║╚══╗ ║║ ║║   ║║
 * ╚═══╝ ╚╝╚╝ ╚╝╚╝    ╚╝   ╚═══╝ ╚══╝  ╚═╝╚═╝ ╚═══╝ ╚═══╝  ╚═══╝ ╚═══╝  ╚╝  ╚═══╝ ╚══╝ ╚══╝ ╚╝    ╚╝  ╚╝ ╚═══╝ ╚╝ ╚╝   ╚╝
 *----------------------------------------------------------------------------------------------------------------------
 * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
 * @date 26.08.2020 12:05
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 **********************************************************************************************************************/

namespace JoomshoppingTwoLang\Helpers;
defined('_JEXEC') or die; // No direct access to this file

use Exception;
use GNZ11\Document\Text;
use JDatabaseDriver;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Profiler\Profiler;
use Joomla\String\StringHelper;


/**
 * Class Helper
 * @package JoomshoppingTwoLang\Helpers
 * @since 3.9
 * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
 * @date 26.08.2020 12:05
 *
 */
class Helper
{

    /**
     * @var CMSApplication|null
     * @since 3.9
     */
    private $app;
    /**
     * @var JDatabaseDriver|null
     * @since 3.9
     */
    private $db;
    /**
     * Array to hold the object instances
     *
     * @var Helper
     * @since  1.6
     */
    public static $instance;
    /**
     * @var string имя плагина
     * @since 3.9
     */
    private static $_name ;
    /**
     * @var string группа плагина
     * @since 3.9
     */
    private static $_type;
    /**
     * @var array|mixed
     * @since 3.9
     */
    public $dictionary;
    /**
     * @var mixed|null
     * @since 3.9
     */
    public $searchword;
    /**
     * настрийки плагина
     * @var array|object
     * @since 3.9
     */
    protected $params;
    protected $profiler;
    /**
     * @var \JoomshoppingTwoLang\Helpers\helper
     * @since 3.9
     */
    private $HelperString;
    /**
     * @var array Ключи (слова) истории поиска
     * @since 3.9
     */
    private $keysHistory;

    /**
     * Helper constructor.
     * @param $params array|object
     * @throws Exception
     * @since 3.9
     */
    public function __construct($_name , $_type , $params)
    {
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
        self::$_name = $_name ;
        self::$_type = $_type ;
        $this->params = $params ;
        if( TWO_LANG_DEBUG ) {
            $this->profiler = Profiler::getInstance('plg_search_joomshopping_two_lang:Helper');
            $this->profiler->mark('- __construct ');
        }

        \JLoader::registerNamespace( 'GNZ11' , JPATH_LIBRARIES . '/GNZ11' , $reset = false , $prepend = false , $type = 'psr4' );
        \JLoader::registerNamespace('JoomshoppingTwoLang\Helpers',JPATH_PLUGINS.'/search/joomshopping_two_lang/Helpers',$reset=false,$prepend=false,$type='psr4');
        $this->HelperString = HelperString::instance();

        return $this;
    }

    /**
     * @param array $options
     *
     * @return Helper
     * @throws Exception
     * @since 3.9
     */
    public static function instance( $_name=null , $_type=null , $options = array())
    {
        if( self::$instance === null )
        {
            self::$instance = new self($_name , $_type , $options);
        }
        return self::$instance;
    }

    /**
     * добавить слово в словарь
     * @param array $WORDS_ARR [ 'word' , 'transcription' ]
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 01.09.2020 04:24
     *
     */
    protected function addWordsToDictionary(array $WORDS_ARR): void
    {
        $Query = $this->db->getQuery(true);
        $jdata = new \Joomla\CMS\Date\Date();
        $now = $jdata->toSql();

        $columns = ['word', 'transcription', 'date_added' ,'hits' , 'redirect'];

        foreach ($WORDS_ARR as $i => $item)
        {
            $values =
                $this->db->quote($item['word']) . ","
                . $this->db->quote($item['transcription']) . ","
                . $this->db->quote($now). ","
                . ( isset ( $item['hits'] ) ? $this->db->quote($item['hits']) : $this->db->quote(0) ). ","
                . ( isset ( $item['redirect'] ) ? $this->db->quote($item['redirect']) : $this->db->quote('') )
            ;
           /* echo'<pre>';print_r( $WORDS_ARR );echo'</pre>'.__FILE__.' '.__LINE__;
            echo'<pre>';print_r( $values );echo'</pre>'.__FILE__.' '.__LINE__;
            die(__FILE__ .' '. __LINE__ );*/

            $Query->values($values);
        }//foreach

        $this->RetSysData['statistic']['added'] = $i;

        $Query->insert($this->db->quoteName('#__plg_joomshopping_two_lang'))
            ->columns($this->db->quoteName($columns));
        $this->db->setQuery($Query);
        // echo $Query->dump();
        try
        {
            $this->db->execute();

        } catch (Exception $e)
        {
            echo $Query->dump();
            echo 'Выброшено исключение: ', $e->getMessage(), "\n";
            echo '<pre>'; print_r($e); echo '</pre>' . __FILE__ . ' ' . __LINE__;
            die(__FILE__ . ' ' . __LINE__);
        }
    }



    public function LoadHistoryTemplate(){
        $ARR = $this->app->input->getArray(['DB'=>['__history'=>'ARRAY']]);
        // Массив истории и ключи в нижний регистр
        $_LocalStorage = \GNZ11\Document\Arrays::arrayChangeKeyCaseUnicode( $ARR['DB']['__history']['_LocalStorage']  , CASE_LOWER );
        $this->keysHistory =  array_keys($_LocalStorage)  ;

        $Query = $this->db->getQuery(true);
        $select = [
            $this->db->quoteName('word'),
            $this->db->quoteName('redirect'),
            $this->db->quoteName('hits'),
        ];
        $Query->select($select)
            ->from( $this->db->quoteName('#__plg_joomshopping_two_lang'));
        $Query->where( $this->db->quoteName('word') ." IN ('". implode( "','" , $this->keysHistory ) ."')" );
        $this->db->setQuery($Query);

        $this->__History = $this->db->loadAssocList('word');



        $res['History']['html'] = self::loadTemplate('history');
        $res['symbols'] = self::loadTemplate('symbols');
        return $res ;
    }

    /**
     * Добавить Hit к слову
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 31.08.2020 18:28
     *
     */
    public function AddHitDictionary(){

        $searchword = $this->app->input->get('searchword' , false , 'STRING') ;
        $DB = $this->app->input->getArray([ 'DB' => 'ARRAY' ]) ;
        # Ищим слово в словаре
        $word = $this->SearchInDictionary( true , true ) ;

        # Если слова нет
        if (!count($word))
        {
            # очистить строку
            $word = self::_cleanWord($searchword);

            # Переключить в Русскую и в En раскладку
            list($wordRu, $wordEn) = $this->_switchLanguage($word);

            if ( $word == $wordRu )
            {
                $arr = [
                    'word' => $wordRu ,
                    'transcription' => $wordEn ,
                    'hits' => 1 ,
                ];
            }else if($word == $wordEn ){
                $arr = [
                    'word' => $wordEn ,
                    'transcription' => $wordRu ,
                    'hits' => 1 ,
                ];
            }else{
                $arr = [
                    'word' => $word ,
                    'transcription' => $wordEn ,
                    'hits' => 1 ,
                ];
            }#END IF

            $arr['redirect'] = ( isset($DB['DB']['Redirect']) ? $DB['DB']['Redirect'] : null );

            $WORDS_ARR[] = $arr ;

            $this->addWordsToDictionary($WORDS_ARR);
            return $WORDS_ARR ;
        }#END IF


        $Query = $this->db->getQuery( true ) ;
        $Query->update( $this->db->quoteName('#__plg_joomshopping_two_lang') )
            ->set( $this->db->quoteName('hits') . '=' . $this->db->quoteName('hits') .'+1'  )
            ->where( $this->db->quoteName('word') . '=' .$this->db->quote( $word[0]['word'] ) ) ;
        $this->db->setQuery($Query) ;

        $this->profiler ? $this->profiler->mark('- Before DB AddHitDictionary ') : false ;
        $res['Dictionary']['AddHitDictionary'] = $this->db->execute() ;
        $this->profiler ? $this->profiler->mark('- After DB AddHitDictionary ') : false ;



        if (TWO_LANG_DEBUG) {
            # разбор системных комманд
            if ( $this->params->get('show_system_commands', false) ) {
               $system_commands = explode( ',' , $this->params->get('system_commands', [])) ;
               # Проверить слово на соответствие системной команде
                if ($this->checkSystemCommand($searchword)) {
                    $command = Text::camelCase($searchword, []);
                    return $this->{$command}();
                }#END IF
            }
            if ($this->profiler) {
                $res['debug']['profiler'] = $this->profiler->getBuffer()  ;
            }#END IF
            if ($this->params->get('query_db_debug' , false ) ) {
                $res['debug']['QueryDump'] =  $Query->dump() ;
            }#END IF
        }#END IF
        return $res ;
    }

    /**
     * Поиск в словаре
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 31.08.2020 20:43
     *
     */
    public function SearchInDictionary( $strict = false , $onlyResult = false ){

        $this->searchword = $this->app->input->get('searchword' , false , 'STRING' );
        $this->searchword = self::_cleanWord($this->searchword);
        preg_match("/^[a-zа-яё\d]{1}/iu", $this->searchword , $matches );
        if ( empty( $matches ) ) {
            $res['Dictionary']['html'] = self::loadTemplate('empty_result');
            $res['Dictionary']['queryResult'] = [];
            echo new \Joomla\CMS\Response\JsonResponse( $res );
            die();
        }


        
        # Переключить в Русскую и в En раскладку
        list($wordRu, $wordEn) = $this->_switchLanguage($this->searchword);

        $Query = $this->db->getQuery( true ) ;

        $arrSelect = [
            $this->db->quoteName('word'),
            $this->db->quoteName('redirect'),
        ];
        $Query->select($arrSelect);
        $Query->from( $this->db->quoteName('#__plg_joomshopping_two_lang') ) ;


        $arrWhere = [];
        if ($this->searchword == $wordRu)
        {
            $arrWhere[] = $this->db->quoteName('word') . ' LIKE ' . $this->db->quote($wordRu . (!$strict ? '%' : null));
            $arrWhere[] = $this->db->quoteName('transcription') . ' LIKE '. $this->db->quote($wordEn . (!$strict ? '%' : null));

        }
        else if( $this->searchword == $wordEn )
        {
            $arrWhere[] = $this->db->quoteName('word') . ' LIKE ' . $this->db->quote($wordEn . (!$strict ? '%' : null));
            $arrWhere[] = $this->db->quoteName('transcription') . ' LIKE '
                . $this->db->quote($this->searchword . (!$strict ? '%' : null));
            $this->searchword = $wordRu;
        }else{
            $arrWhere[] = $this->db->quoteName('word') . ' LIKE ' . $this->db->quote( $this->searchword . (!$strict ? '%' : null));
            $arrWhere[] = $this->db->quoteName('transcription') . ' LIKE '
                . $this->db->quote($wordEn . ( !$strict ? '%' : null));
            $this->searchword = $wordRu;
        }#END IF

        $Query->where( '( ' . implode( ' OR ' , $arrWhere ) . ' )' ) ;

        if (!TWO_LANG_DEBUG && !$this->params->get('show_system_commands', false) ) {
            $Query->where( $this->db->quoteName( 'word' )  .'='. $this->db->quote( 0  ) ) ;
        }

        $this->db->setQuery($Query);

        $this->profiler ? $this->profiler->mark('- Before DB SearchInDictionary ') : false ;
        $this->dictionary = $this->db->loadAssocList() ;
        $this->profiler ? $this->profiler->mark('- After DB SearchInDictionary ') : false ;

        if ($onlyResult)
        {
            return $this->dictionary ;
        }#END IF

        if (TWO_LANG_DEBUG) {

            # Если работать с системными командами
            if ($this->params->get('show_system_commands', false)) {
                $systemCommands = $this->params->get('system_commands', []);
                $systemCommands = explode(',', $systemCommands);
            }#END IF

            # Если профилирование включено
            if ($this->profiler) {
                $res['debug']['profiler'] = $this->profiler->getBuffer()  ;
            }#END IF

            if ($this->params->get('query_db_debug' , false ) ) {
                $res['debug']['QueryDump'] = $Query->dump() ;
            }#END IF
        }


        if (!empty($this->dictionary)) {



            $res['Dictionary']['html'] = self::loadTemplate('dictionary');
            $res['Dictionary']['queryResult'] = $this->dictionary;
        }#END IF

        echo new \Joomla\CMS\Response\JsonResponse( $res );
        die();
    }

    /**
     * Проверить слово на соответствие системной команде
     * @param $searchword
     * @return bool
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 31.08.2020 20:34
     *
     */
    public function checkSystemCommand($searchword)
    {
        $system_commands = explode(',', $this->params->get('system_commands', []));
        if (in_array($searchword, $system_commands)) return true; #END IF
        return false;
    }



    private $RetSysData = [];
    /**
     * Системная команда - Создать словарь
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 31.08.2020 20:45
     *
     */
    public function createDictionary(): array
    {
        $DB = $this->app->input->getArray( [ 'DB' => 'ARRAY' ] );
        $offset = $DB['DB']['offset'];
        $step = $DB['DB']['step'] ? $DB['DB']['step']:25 ;
        $this->RetSysData['countingAllProducts'] = $DB['DB']['countingAllProducts']>0?$DB['DB']['countingAllProducts']:self::countingAllProducts();

        $this->RetSysData['offset'] = $offset ;
        $this->RetSysData['command'] = '*create dictionary' ;

        $Query = $this->db->getQuery(true);
        $select = [
            $this->db->quoteName('product_id'),
            $this->db->quoteName('name_ru-RU'),
            $this->db->quoteName('meta_keyword_ru-RU'),
        ];
        $Query->select($select);
        $Query->from($this->db->quoteName('#__jshopping_products'));
//        $Query->where()
        $this->db->setQuery($Query , $offset , $step );




        try
        {
            $data = $this->db->loadObjectList('product_id');
            $this->RetSysData['data'] = $data ;
        }
        catch (Exception $e)
        {
            // Executed only in PHP 5, will not be reached in PHP 7
            echo 'Выброшено исключение: ',  $e->getMessage(), "\n";
            echo'<pre>';print_r( $e );echo'</pre>'.__FILE__.' '.__LINE__;
            die(__FILE__ .' '. __LINE__ );
        }

        $this->db->setQuery('SELECT '.$this->db->quoteName('word').' FROM '.$this->db->quoteName('#__plg_joomshopping_two_lang').' WHERE `systems` = 0 ;');
        $WORDS_DB = $this->db->loadColumn();

        $WORDS_ARR = [] ;
        $WORDS_ARR_temp = [] ;
        $fieldArr = [
            'name_ru-RU',
            'meta_keyword_ru-RU' ,
        ];
        foreach ( $data as $ipr => $prod ) {

            foreach ($fieldArr as  $field ) {

                $words = explode( ' ' , $prod->{$field} ) ;

                foreach ( $words as $iw => $word) {

                    $word = self::_cleanWord($word);
                    if ( mb_strlen( $word , 'utf-8' ) < 6 ) continue ;  #END IF

                    list($wordRu, $wordEn) = $this->_switchLanguage($word);
                    if ( $wordRu != $word)  continue ; #END IF

                    if (!in_array( $wordRu  , $WORDS_ARR_temp ) && !in_array( $wordRu  , $WORDS_DB )  ){
                        $WORDS_ARR_temp[] = $wordRu ;
                        $WORDS_ARR[] = [
                            'word' => $wordRu ,
                            'transcription' => $wordEn ,
                        ];

                    }#END IF

                }#END FOREACH
            }#END FOREACH
        }#END FOREACH

        # количество слов
        $this->RetSysData['statistic']['words'] = $iw ;
        $this->RetSysData['statistic']['added'] = 0 ;
        if (!count($WORDS_ARR))  return $this->RetSysData ; #END IF


        $this->addWordsToDictionary($WORDS_ARR);

        return $this->RetSysData ;
    }



    /**
     * Системная комманда - Очистить словарь
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 01.09.2020 00:19
     *
     */
    public function clearDictionary(){
        $Query = $this->db->getQuery(true);
        $conditions = array($this->db->quoteName('systems') . ' =  ' .$this->db->quote(0) );
        $Query->where($conditions);

        $Query->delete( $this->db->quoteName('#__plg_joomshopping_two_lang'));
        $this->db->setQuery($Query)->execute();

        $this->RetSysData['statistic']['AffectedRows'] = $this->db->getAffectedRows();
        $this->RetSysData['command'] = $this->app->input->get('searchword' , '*clear dictionary' );
        return $this->RetSysData ;


    }

    /**
     * очистить строку
     * @param string $word
     * @return string|string[]
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 01.09.2020 04:33
     */
    private static function _cleanWord(string $word)
    {
        # Убираем пробелы по краям
        $word = StringHelper::trim( $word );
        # Make a string lowercase
        $word = StringHelper::strtolower( $word );

        $code_match = array(/*'-',*/
            '"', '!', '@', '#', '$', '%', '^', '&', '*', /*'(',*/ /*')',*/ '_', '+', '{', '}', '|', ':', '"', '<', '>', '?', '[', ']', ';', "'", ',', '.', '/', '', '~', '`', '=');
        $word = str_replace($code_match, '', $word);
        $word = str_replace('ё', 'е', $word);
        return $word;
    }


    /**
     * Подсчет все товаров в таблице #__jshopping_products
     * @return mixed|null
     * @throws Exception
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 31.08.2020 21:04
     *
     */
    public static function countingAllProducts(){
        $self = self::instance();
        $self->db->setQuery( 'SELECT COUNT(*) FROM '.$self->db->quoteName('#__jshopping_products').';' );
        return $self->db->loadResult();
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
    public static function loadTemplate ( $layout = 'default' )
    {

        $path = \Joomla\CMS\Plugin\PluginHelper::getLayoutPath(  self::$_type , self::$_name , $layout );
        // Render the layout
        ob_start();

        include $path;
        return ob_get_clean();
    }

    /**
     * Переключить в Русскую и в En раскладку
     * @param string $word
     * @return array
     * @since 3.9
     * @auhtor Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
     * @date 01.09.2020 04:44
     */
    private function _switchLanguage(string $word): array
    {
        # Переключить в Русскую раскладку
        $wordRu = $this->HelperString->correctStringRU($word);
        # Переключить в английскую раскладку
        $wordEn = $this->HelperString->correctStringEN($word);
        return array($wordRu, $wordEn);
    }


}














