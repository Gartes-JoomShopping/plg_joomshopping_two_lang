<?php
/***********************************************************************************************************************
 * ╔═══╗ ╔══╗ ╔═══╗ ╔════╗ ╔═══╗ ╔══╗  ╔╗╔╗╔╗ ╔═══╗ ╔══╗   ╔══╗  ╔═══╗ ╔╗╔╗ ╔═══╗ ╔╗   ╔══╗ ╔═══╗ ╔╗  ╔╗ ╔═══╗ ╔╗ ╔╗ ╔════╗
 * ║╔══╝ ║╔╗║ ║╔═╗║ ╚═╗╔═╝ ║╔══╝ ║╔═╝  ║║║║║║ ║╔══╝ ║╔╗║   ║╔╗╚╗ ║╔══╝ ║║║║ ║╔══╝ ║║   ║╔╗║ ║╔═╗║ ║║  ║║ ║╔══╝ ║╚═╝║ ╚═╗╔═╝
 * ║║╔═╗ ║╚╝║ ║╚═╝║   ║║   ║╚══╗ ║╚═╗  ║║║║║║ ║╚══╗ ║╚╝╚╗  ║║╚╗║ ║╚══╗ ║║║║ ║╚══╗ ║║   ║║║║ ║╚═╝║ ║╚╗╔╝║ ║╚══╗ ║╔╗ ║   ║║
 * ║║╚╗║ ║╔╗║ ║╔╗╔╝   ║║   ║╔══╝ ╚═╗║  ║║║║║║ ║╔══╝ ║╔═╗║  ║║─║║ ║╔══╝ ║╚╝║ ║╔══╝ ║║   ║║║║ ║╔══╝ ║╔╗╔╗║ ║╔══╝ ║║╚╗║   ║║
 * ║╚═╝║ ║║║║ ║║║║    ║║   ║╚══╗ ╔═╝║  ║╚╝╚╝║ ║╚══╗ ║╚═╝║  ║╚═╝║ ║╚══╗ ╚╗╔╝ ║╚══╗ ║╚═╗ ║╚╝║ ║║    ║║╚╝║║ ║╚══╗ ║║ ║║   ║║
 * ╚═══╝ ╚╝╚╝ ╚╝╚╝    ╚╝   ╚═══╝ ╚══╝  ╚═╝╚═╝ ╚═══╝ ╚═══╝  ╚═══╝ ╚═══╝  ╚╝  ╚═══╝ ╚══╝ ╚══╝ ╚╝    ╚╝  ╚╝ ╚═══╝ ╚╝ ╚╝   ╚╝
 *----------------------------------------------------------------------------------------------------------------------
 * @author Gartes | sad.net79@gmail.com | Skype : agroparknew | Telegram : @gartes
 * @date 01.09.2020 08:13
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 **********************************************************************************************************************/
defined('_JEXEC') or die; // No direct access to this file
$helper = \JoomshoppingTwoLang\Helpers\Helper::instance();


 
//echo'<pre>';print_r( $helper->keysHistory );echo'</pre>'.__FILE__.' '.__LINE__;






?>
<div _ngcontent-c9="" class="search-suggest history">
    <ul _ngcontent-c9="" class="suggest-list">
        <li _ngcontent-c9="" class="search-suggest__heading js-rz-search-suggest-clean-history"> История поиска
            <button _ngcontent-c9="" class="button button_type_link search-suggest__heading-action" type="button">
                Очистить список
            </button>
        </li>
        <?php
        /**
         * @var array $helper->keysHistory
         */
        foreach ( $helper->keysHistory as $i =>   $word)
        {
            $redirect = $helper->__History[$word]['redirect']; ?>
            <li _ngcontent-c9="" <?=(!empty($redirect) ? 'redirect="1"' : null )?> class="search-suggest__item"
                data-name="<?= $word ?>" data-index="<?= $i  ?>">
                <a _ngcontent-c9="" class="search-suggest__item-content search-suggest__item-text"
                   href="<?= $redirect ?>">
                    <svg _ngcontent-c9="" class="search-suggest__item-icon" height="24" width="24">
                        <use _ngcontent-c9="" xlink:href="#icon-magnifier" xmlns:xlink="http://www.w3.org/1999/xlink"></use>
                    </svg>
                    <span _ngcontent-c9="" class="search-suggest__item-text_type_nowrap">
                        <?=$word?>
                    </span>
                </a>
                <button _ngcontent-c9="" class="search-suggest__item-remove js-rz-suggest-delete"
                        data-rz-gtm-event="removeSuggestHistoryItem" type="button">
                    <svg _ngcontent-c9="" height="12" width="12">
                        <use _ngcontent-c9="" xlink:href="#icon-close-modal" xmlns:xlink="http://www.w3.org/1999/xlink"></use>
                    </svg>
                </button>
            </li>
            <?
        }#END FOREACH
        ?>

        <template id="search-history-template">
            
        </template>

    </ul>
</div>





































