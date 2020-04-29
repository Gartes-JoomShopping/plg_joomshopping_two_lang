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

defined( '_JEXEC' ) or die( 'Restricted access' );
error_reporting(E_ALL & ~E_NOTICE);

jimport('joomla.plugin.plugin');

/*JPlugin::loadLanguage( 'plg_search_joomshopping' );*/

class plgSearchJoomshopping_two_lang extends JPlugin {
	
	
	/**
	 * @var \JoomshoppingTwoLang\Helpers\helper
	 * @since version
	 */
	private $HelperString;
	
	function onContentSearchAreas(){
        static $areas = array(
            'joomshopping' => 'Товары'
        );
        return $areas;
    }


    function onContentSearch( $text, $phrase='', $ordering='', $areas=null )
    {
	    
    	
        require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php'); 
        require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');      
        
        $db =  JFactory::getDBO();
        $user =  JFactory::getUser();
        $lang = JSFactory::getLang();
        
        if (is_array($areas)) {
            if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
                return array();
            }
        }
        
        $limit = $this->params->def( 'search_limit', 50 );
        $search_description = $this->params->def( 'search_description', 1 );
        $search_description_short = $this->params->def( 'search_description_short', 1 );
        $select_desc = $this->params->def( 'select_desc', 0 );

		$text = \Joomla\String\StringHelper::strtolower($text);
	    $text = preg_replace('/[^\da-zа-яё,\.~`]/u', ' ', $text);
		$text = preg_replace('/(\d+)/u', ' ${1} ', $text);
		$text = preg_replace('/\s+/u', ' ', \Joomla\String\StringHelper::trim($text));
        if ($text == '') {
            return array();
        }
		$full_text = $text;
	
        
        
        
  
     
		$wordsWithoutNumbers = array();
		$words = explode(' ', $text);
		foreach ($words as $key=>$value) {
			$words[$key] = $db->escape($value, true);
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
		
		$prod_name = 'prod.'.$db->quoteName($lang->get('name'));
		$prod_keywords = 'prod.'.$db->quoteName($lang->get('meta_keyword'));

		$revalent_0 = $prod_keywords . ' rlike ' . $db->quote('[[:<:]]' . implode('[[:>:]][[:<:]]', $words) . '[[:>:]]', false);
		
		$revalent_1 = array();
		foreach ($words as $key=>$value) {
			$revalent_1[] = $prod_keywords . ' like ' . $db->quote('%' . $value . '%', false);
		}
		$revalent_1 = implode(' and ', $revalent_1);
		$revalent_2 = $prod_keywords . ' like ' . $db->quote('%' . implode('%', $wordsWithoutNumbers) . '%', false);
		$revalent_3 = array();
		foreach ($wordsWithoutNumbers as $key=>$value) {
			$revalent_3[] = $prod_keywords . ' like ' . $db->quote('%' . $value . '%', false);
		}
		$revalent_3 = implode(' and ', $revalent_3);
		
		$revalent_4 = array();
		foreach ($wordsWithoutNumbers as $key=>$value) {
			$revalent_4[] = $prod_keywords . ' like ' . $db->quote('%' . $value . '%', false);
		}
		$revalent_4 = implode(' or ', $revalent_4);
		
		$revalent = 'if((' . $revalent_0 . '), 0, if((' . $revalent_1 . '), 1, if((' . $revalent_2 . '), 2, if((' . $revalent_3 . '), 3, if((' . $revalent_4 . '), 4, 5)))))';
	
	    JLoader::registerNamespace('JoomshoppingTwoLang\Helpers',JPATH_PLUGINS.'/search/joomshopping_two_lang/Helpers',$reset=false,$prepend=false,$type='psr4');
	    $this->HelperString = \JoomshoppingTwoLang\Helpers\HelperString::instance();
	    $full_text = $this->HelperString->getCorrect( $full_text ) ;
	    
	    
	    
		$where = array('product_ean = ' . $db->quote($full_text));
		foreach ($words as $key=>$value) {
			
			$valueCorrect = $this->HelperString->getCorrect( $value ) ;
			$where[] = $prod_name . ' LIKE ' . $db->quote('%' . $valueCorrect . '%', false);
			$where[] = $prod_keywords . ' LIKE ' . $db->quote('%' . $valueCorrect . '%', false);

			# Переключить в русскую расладку
			$valueRU = $this->HelperString->correctStringRU( $value ) ;
			$where[] = $prod_name . ' like ' . $db->quote('%' . $valueRU . '%', false);
			$where[] = $prod_keywords . ' like ' . $db->quote('%' . $valueRU . '%', false);
			
		}
	
		
		
		$where = '('. implode(' or ', $where) . ')';
		
		
		$query = $db->getQuery(true);
        $query->select("prod.product_id AS slug,
			pr_cat.category_id AS catslug,
			prod." . $db->quoteName($lang->get('name'))." as title,
			'2' AS browsernav,
			prod.product_date_added AS created,
			prod.product_manufacturer_id AS manufacturer_id,
			image AS myimg,
			product_price AS myprice,
			currency_id AS mycurrency,
			cat.category_id AS section_id ,
			cat." . $db->quoteName($lang->get('name'))." AS section");
			
     /*   echo 'Query Dump :'.__FILE__ .' Line:'.__LINE__  .$query->dump() ;
        
        die(__FILE__ .' '. __LINE__ );*/
        
		switch($select_desc) {
			case 0:
				$query->select("CONCAT(prod.".$db->quoteName($lang->get('short_description')).",' ',prod.".$db->quoteName($lang->get('description')).") as text");
			break;
			
			case 1:
				$query->select("prod.".$db->quoteName($lang->get('short_description'))." as text");
			break;

			case 2:
				$query->select("prod.".$db->quoteName($lang->get('description'))." as text");
			break;
		}

        switch ( $ordering ) {
            case 'alpha':
                $order = "prod." . $db->quoteName($lang->get('name'))." ASC";
                break;          
            case 'newest':
                $order = "prod.product_date_added DESC";
                break; 
            case 'oldest':
                $order = "prod.product_date_added ASC";
                break;     
            case 'popular':
                $order = "prod.hits DESC";
                break;                                    
            case 'category':
                $order = "cat." . $db->quoteName($lang->get('name')) . " ASC, prod." . $db->quoteName($lang->get('name')) . " ASC";
                break;
            default:
                $order = "prod.product_id DESC";
        }
		
		$query->select($revalent . ' as revalent');
		
		
				
		$query->from("#__jshopping_products AS prod");
        $query->join('LEFT', $db->quoteName('#__jshopping_products_to_categories')." AS pr_cat ON pr_cat.product_id = prod.product_id");
        $query->join('LEFT', $db->quoteName('#__jshopping_categories')." AS cat ON pr_cat.category_id = cat.category_id");
        $query->where($where);
        $query->where("prod.product_publish = '1'");
		$query->where("cat.category_publish = '1'");
	
		# Исключение категорий
	    $query = $this->getWhereExcludeCategorys( $query );
		
	    
	
	    if ($category_id = JFactory::getApplication()->input->getInt('category_id')) {
			$query->where("cat.category_id = " . $category_id);
		}
        $query->group("prod.product_id");
        $query->order('revalent asc, ' . $order);
	
	
//	    $start = microtime(true);
	
//	    echo 'Query Dump :'.__FILE__ .' Line:'.__LINE__  .$query->dump() ;
//	    die(__FILE__ .' '. __LINE__ );
	    
        $db->setQuery( $query, 0, $limit );
        $rows = $db->loadObjectList();
	
//	    $time = microtime(true) - $start;
//	    echo'<pre>';print_r( $time );echo'</pre>'.__FILE__.' '.__LINE__;
//	    die(__FILE__ .' '. __LINE__ );
     
		$manufacturers_id = JFactory::getApplication()->input->get('manufacturer_id', array(), 'array');
        
		$categorys = $manufacturers = array();
        if ($rows){
			$query = 'SELECT ' . $db->quoteName($lang->get('name')) . ' as name, manufacturer_id FROM ' . $db->quoteName('#__jshopping_manufacturers');
			$db->setQuery($query);
			$allManufacturers = $db->loadObjectList('manufacturer_id');
			
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
			
			$query = 'SELECT ' . $db->quoteName($lang->get('name')) . ' as name, category_id FROM ' . $db->quoteName('#__jshopping_categories');
			$db->setQuery($query);
			$allCategorys = $db->loadObjectList('category_id');
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
		
		JFactory::getApplication()->set('joomshopping_categorys_search', $categorys);
		JFactory::getApplication()->set('joomshopping_manufacturers_search', $manufacturers);
	   
		
				try
						{
							// Code that may throw an Exception or Error.
							// $this->oooooooooooooooo();
						}
						catch (Exception $e)
						{
						   // Executed only in PHP 5, will not be reached in PHP 7
						   echo 'Выброшено исключение: ',  $e->getMessage(), "\n";
						}
						catch (Throwable $e)
						{
							// Executed only in PHP 7, will not match in PHP 5
							echo 'Выброшено исключение: ',  $e->getMessage(), "\n";
							echo'<pre>';print_r( $e );echo'</pre>'.__FILE__.' '.__LINE__;
						}
		//echo'<pre>';print_r( count( $rows ) );echo'</pre>'.__FILE__.' '.__LINE__;
		// die(__FILE__ .' '. __LINE__ );
	    
	     
	    
        return $rows;
    }

    function onContentSearchOld( $text, $phrase='', $ordering='', $areas=null )
    {
        require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php'); 
        require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');      
        
        $db =  JFactory::getDBO();
        $user =  JFactory::getUser();
        $lang = JSFactory::getLang();
        
        if (is_array($areas)) {
            if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
                return array();
            }
        }
        
        $limit = $this->params->def( 'search_limit', 50 );
        $search_description = $this->params->def( 'search_description', 1 );
        $search_description_short = $this->params->def( 'search_description_short', 1 );
        $select_desc = $this->params->def( 'select_desc', 0 );

        $text = trim( $text );
        if ($text == '') {
            return array();
        }

        switch ( $ordering ) {
            case 'alpha':
                $order = "prod." . $db->quoteName($lang->get('name'))." ASC";
                break;          
            case 'newest':
                $order = "prod.product_date_added DESC";
                break; 
            case 'oldest':
                $order = "prod.product_date_added ASC";
                break;     
            case 'popular':
                $order = "prod.hits DESC";
                break;                                    
            case 'category':
                $order = "cat." . $db->quoteName($lang->get('name')) . " ASC, prod." . $db->quoteName($lang->get('name')) . " ASC";
                break;
            default:
                $order = "prod.product_id DESC";
        }
        
        switch ($phrase) {
            case 'exact':
                $text        = $db->Quote( '%'.$db->escape( $text, true ).'%', false );
                $wheres2     = array();
                $wheres2[]   = "prod." . $db->quoteName($lang->get('name'))." LIKE ".$text;
                if($search_description_short) $wheres2[] = "prod." . $db->quoteName($lang->get('short_description'))." LIKE ".$text;
                if($search_description) $wheres2[] = "prod." . $db->quoteName($lang->get('description'))." LIKE ".$text;
                $wheres2[]   = "prod.product_ean LIKE ".$text;
                $where       = '(' . implode( ') OR (', $wheres2 ) . ')';
            break;

            case 'all':
            case 'any':
            default:
                $words = explode( ' ', $text );
                $wheres = array();
                foreach ($words as $word) {
                    $word        = $db->Quote( '%'.$db->escape( $word, true ).'%', false );
                    $wheres2     = array();
                    $wheres2[]   = "prod.`".$lang->get('name')."` LIKE ".$word;
                    if($search_description_short) $wheres2[] = "prod.`".$lang->get('short_description')."` LIKE ".$word;
                    if($search_description) $wheres2[] = "prod.`".$lang->get('description')."` LIKE ".$word;
                    $wheres2[]   = "prod.product_ean LIKE ".$word;
                    $wheres[]    = implode( ' OR ', $wheres2 );
                }
                $where = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . ')';
            break;
        }
        
				$query = $db->getQuery(true);
        $query->select("prod.product_id AS slug, pr_cat.category_id AS catslug, prod." . $db->quoteName($lang->get('name'))." as title,
					'2' AS browsernav, prod.product_date_added AS created, cat." . $db->quoteName($lang->get('name'))." AS section");

				switch($select_desc) {
					case 0:
						$query->select("CONCAT(prod.".$db->quoteName($lang->get('short_description')).",' ',prod.".$db->quoteName($lang->get('description')).") as text");
					break;
					
					case 1:
						$query->select("prod.".$db->quoteName($lang->get('short_description'))." as text");
					break;

					case 2:
						$query->select("prod.".$db->quoteName($lang->get('description'))." as text");
					break;
				}
				
				$query->from("#__jshopping_products AS prod");
        $query->join('LEFT', $db->quoteName('#__jshopping_products_to_categories')." AS pr_cat ON pr_cat.product_id = prod.product_id");
        $query->join('LEFT', $db->quoteName('#__jshopping_categories')." AS cat ON pr_cat.category_id = cat.category_id");
        $query->where($where);
        $query->where("prod.product_publish = '1'");
				$query->where("cat.category_publish = '1'");
        $query->group("prod.product_id");
        $query->order($order);
        
        $db->setQuery( $query, 0, $limit );
        $rows = $db->loadObjectList();
        
        if ($rows){
            foreach($rows as $key => $row) {
                $rows[$key]->href = SEFLink('index.php?option=com_jshopping&controller=product&task=view&category_id='.$row->catslug.'&product_id='.$row->slug, 1);
            }
        }
        return $rows;
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
			$query->where( "cat.category_id NOT IN (" . implode( ',', $exclude_categorys ) . ')' );
		}
		return $query ;
	}
}



