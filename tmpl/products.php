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
 * @date 26.08.2020 23:13
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 **********************************************************************************************************************/
defined('_JEXEC') or die; // No direct access to this file

$pImg = '/components/com_jshopping/files/img_products/' ;
$arrInp = [

        'category_id' => 'INT' ,
    'searchphrase' => 'WORD' ,
    'ordering' => 'WORD' ,
    'limit' => 'INT' ,
    'searchword' => 'STRING' ,
    'areas' => 'ARRAY' ,
    'task' => 'STRING' ,
];
$arr = $this->app->input->getArray( $arrInp );
/**
 * @var STRING $ordering
 * @var INT $category_id
 * @var INT $limit
 * @var STRING $searchword
 * @var STRING $areas
 * @var array $arrSearchResult
 * @var STRING $task
 */
extract($arr) ;


?>

    <div _ngcontent-c9="" class="search-suggest">
        <?= $this->loadTemplate( 'symbols' ) ?>




<!--        Все рузельтаты поиска -->
        <ul _ngcontent-c9="" class="suggest-list">
            <li _ngcontent-c9="" class="search-suggest__item">
                <a _ngcontent-c9=""
                   class="suggest-goods search-suggest__item-content search-suggest__show-all"
                   href="<?= $this->allResultLink ?>">
                    Все результаты поиска&nbsp;→ </a></li>
        </ul>
<!--        Предлагаемые товары -->
        <ul _ngcontent-c9="" class="suggest-list search-suggest-product"><?php
            foreach ($this->product as $product)
            {
                $product->title = \GNZ11\Document\Text::replaceQuotesWithSmart($product->title);
                $suggestPrice = 'Уточняйте цену';
                $priceClass = 'checkPrice';
                if( $product->myprice && $product->myprice > 0 )
                {
                    $suggestPrice = number_format((int)$product->myprice, 0, '', ' ') . ' &#8381;';
                    $priceClass = '';
                }#END IF





                ?>

                <li _ngcontent-c9="" redirect="1" class="search-suggest__item" data-prod_id="<?= $product->slug ?>"
                    data-name="<?= $product->title ?>">
                    <a _ngcontent-c9="" class="suggest-goods" href="" title="<?= $product->title ?>">
            <span _ngcontent-c9="" class="suggest-goods__image">
                <img _ngcontent-c9="" src="<?= $pImg . $product->myimg ?>"
                     alt="<?= $product->title ?>">
            </span>
                        <span _ngcontent-c9="" class="suggest-goods__info">
                <span _ngcontent-c9="" class="suggest-goods__title">
                    <?= $product->title ?>
                </span>
                <span _ngcontent-c9="" class="suggest-goods__price <?= $priceClass ?>"><?= $suggestPrice ?></span>
                            <!---->
            </span>
                        <input type="button" class="button_buy" title="Купить <?= $product->title ?>" value="Купить">
                        <div class="p-squ" title="Код: <?= $product->product_ean ?>">Код: <?= $product->product_ean ?></div>
                    </a>
                </li>

                <?php
            } ?>



        </ul>
        <ul _ngcontent-c9="" class="suggest-list">
            <?php
            echo $this->loadTemplate('go_to_category' ) ;
            # Поиск в категории
            echo $this->loadTemplate('categoryes' ) ;




            ?>
        </ul>
    </div>
<?php





























