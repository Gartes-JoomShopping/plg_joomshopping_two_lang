<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

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

    $this->limit = $this->params->def( 'search_limit', 50 );
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

    $db->setQuery( $query, 0, $this->limit );
    $rows = $db->loadObjectList();

    if ($rows){
        foreach($rows as $key => $row) {
            $rows[$key]->href = SEFLink('index.php?option=com_jshopping&controller=product&task=view&category_id='.$row->catslug.'&product_id='.$row->slug, 1);
        }
    }
    return $rows;
}