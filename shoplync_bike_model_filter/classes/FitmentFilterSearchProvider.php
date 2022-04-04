<?php
/**
* @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
* @copyright 2007-2022 Shoplync Inc
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @category  PrestaShop module
* @package   Bike Model Filter
*      International Registered Trademark & Property of Shopcreator
* @version   1.0.0
* @link      http://www.shoplync.com/
*/
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use Symfony\Component\Translation\TranslatorInterface;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrderFactory;

include_once dirname (_PS_MODULE_DIR_).'/modules/shoplync_bike_model_filter/classes/helper.php';

class FitmentFilterSearchProvider implements ProductSearchProviderInterface {
 

    public $filter_params;
 
    private $module;
    private $translator;
    private $sortOrderFactory;
    public function __construct(/*Shoplync_bike_model_filter $module_name, */TranslatorInterface $translator, $params = [])
    {        
        /*if($module_name != null)
            $this->module = $module_name;*/
        
        $this->filter_params = $params;
        $this->translator = $translator;
        $this->sortOrderFactory = new SortOrderFactory($this->translator);
    }


    /**
     * @param ProductSearchContext $context
     * @param ProductSearchQuery $query
     *
     * @return ProductSearchResult
     */
    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query) 
    {        
        $products = [];
        $count = 0;
        $lang_id = $context->getIdLang();
        
        if(!empty($this->filter_params))
        {
            $vehicle_id = ($this->filter_params['model_id'] != null  && $this->filter_params['year_id'] != null) ? Shoplync_bike_model_filter::GetVehicleID($this->filter_params['model_id'], $this->filter_params['year_id']) : null;
            Context::getContext()->cookie->__set('vehicle_id', $vehicle_id);
            
            
            //brand/type + make/model/year
            if($this->filter_params['brand_id'] != null && $this->filter_params['type_id'] != null && $this->filter_params['make_id'] != null  && $this->filter_params['model_id'] != null  && $this->filter_params['year_id'] != null)
            {
                if(is_numeric($vehicle_id) && is_numeric($this->filter_params['brand_id']) && is_numeric($this->filter_params['type_id']))
                {
                    $matching_products = Shoplync_bike_model_filter::getProdutsFromTypeID($this->filter_params['type_id'], false, true, $query, $lang_id, $this->filter_params['universal'], $this->filter_params['fluids'], $this->filter_params['brand_id'], $vehicle_id);
                    $products = Product::getProductsProperties($lang_id, $matching_products);
                }   
            }
            // brand + make/model/year
            else if($this->filter_params['brand_id'] != null && $this->filter_params['make_id'] != null  && $this->filter_params['model_id'] != null  && $this->filter_params['year_id'] != null)
            {
                if(is_numeric($vehicle_id) && is_numeric($this->filter_params['brand_id']))
                {
                    //inject order by and per page results
                    $matching_products = Shoplync_bike_model_filter::getProductsFromVehicleID($vehicle_id, false, true, $query, $lang_id, $this->filter_params['universal'], $this->filter_params['fluids'], $this->filter_params['brand_id']);
                    $products = Product::getProductsProperties($lang_id, $matching_products);
                }   
            }
            // type  + make/model/year
            else if($this->filter_params['type_id'] != null && $this->filter_params['make_id'] != null  && $this->filter_params['model_id'] != null  && $this->filter_params['year_id'] != null)
            {
                if(is_numeric($vehicle_id) && is_numeric($this->filter_params['type_id']))
                {
                    $matching_products = Shoplync_bike_model_filter::getProdutsFromTypeID($this->filter_params['type_id'], false, true, $query, $lang_id, $this->filter_params['universal'], $this->filter_params['fluids'], 0, $vehicle_id);
                    $products = Product::getProductsProperties($lang_id, $matching_products);
                }   
            }
            // make/model/year
            else if($this->filter_params['make_id'] != null && $this->filter_params['model_id'] != null && $this->filter_params['year_id'] != null)
            {
                if(is_numeric($vehicle_id))
                {
                    $matching_products = Shoplync_bike_model_filter::getProductsFromVehicleID($vehicle_id, false, true, $query, $lang_id, $this->filter_params['universal'], $this->filter_params['fluids']);
                    $products = Product::getProductsProperties($lang_id, $matching_products);
                }   
            }
            // type + brand
            else if($this->filter_params['brand_id'] != null && $this->filter_params['type_id'] != null)
            {
                if(is_numeric($this->filter_params['brand_id']) && is_numeric($this->filter_params['type_id']))
                {
                    $matching_products = Shoplync_bike_model_filter::getProdutsFromTypeID($this->filter_params['type_id'], false, true, $query, $lang_id, $this->filter_params['universal'], $this->filter_params['fluids'], $this->filter_params['brand_id']);
                    $products = Product::getProductsProperties($lang_id, $matching_products);
                }   
            }
            $count = count($products);
        }
        
        //if search string is provided search the products array
        if (($string = $query->getSearchString()) && !empty($products)) {
            $queryString = Tools::replaceAccentedChars(urldecode($string));
            dbg::m('search-string-found: '.$queryString);
                        
            $found_ids = self::productFindLight(array_column($products, 'id_product'), $queryString, $query, $context);
            
            $products_tmp = [];
            
            foreach($products as $product)
            {
                if(in_array($product['id_product'], $found_ids))
                    array_push($products_tmp, $product);
            }
            //remove matching products that do not meet the search string terms
            $products = $products_tmp;
            $count = count($products);
        }
        
        $result = new ProductSearchResult();
            $result
                ->setProducts($products)
                ->setTotalProductsCount($count);
            $result->setAvailableSortOrders(
                $this->sortOrderFactory->getDefaultSortOrders()
            );    
        dbg::m('Returned products '.count($products));
        
        return $result;
    }
    /*
    * A fork of the Tools::Find function which searches the db for products which match a word, 
    * instead this will only return products matching searched words that exits inside our srcArray.
    *
    * @param $srcArray - The pre-filtered list of id_products we want to match the db result to.
    * @param $expr - The expression from which we will extract search words from.
    *
    * @param ProductSearchQuery $query
    * @param ProductSearchContext $context
    */
    public static function productFindLight($srcArray, $expr = '', ProductSearchQuery $query = null, ProductSearchContext $context = null)
    {
        if(empty($srcArray) || $srcArray == null || $query == null)
            return [];
        
        if($expr == '')
            return $srcArray;
        
        if (!$context)
            $context = Context::getContext();
        
        $id_lang = $context->getIdLang();
        $page_number = $query->getPage();
        $page_size = $query->getResultsPerPage();
        $order_by = $query->getSortOrder()->toLegacyOrderBy();
        $order_by = !Validate::isOrderBy($order_by) || $order_by == null ? 'name' : $order_by;
        
        $order_way = $query->getSortOrder()->toLegacyOrderWay();
        $order_way = !Validate::isOrderWay($order_way) || $order_way == null ? 'ASC' : $order_way;
        
        //--
        $scoreArray = [];
        $fuzzyLoop = 0;
        $wordCnt = 0;
        $eligibleProducts2Full = [];
        $expressions = explode(';', $expr);
        $fuzzyMaxLoop = (int) Configuration::get('PS_SEARCH_FUZZY_MAX_LOOP');
        $psFuzzySearch = (int) Configuration::get('PS_SEARCH_FUZZY');
        $psSearchMinWordLength = (int) Configuration::get('PS_SEARCH_MINWORDLEN');
        foreach ($expressions as $expression) {
            $eligibleProducts2 = null;
            $words = Search::extractKeyWords($expression, $id_lang, false, Language::getIsoById( (int)$id_lang ));
            foreach ($words as $key => $word) {
                if (empty($word) || strlen($word) < $psSearchMinWordLength) {
                    unset($words[$key]);
                    continue;
                }

                $sql_param_search = Search::getSearchParamFromWord($word);
                $sql = 'SELECT DISTINCT si.id_product ' .
                    'FROM ' . _DB_PREFIX_ . 'search_word sw ' .
                    'LEFT JOIN ' . _DB_PREFIX_ . 'search_index si ON sw.id_word = si.id_word ' .
                    'LEFT JOIN ' . _DB_PREFIX_ . 'product_shop product_shop ON (product_shop.`id_product` = si.`id_product`) ' .
                    'WHERE sw.id_lang = ' . (int) $id_lang . ' ' .
                    'AND sw.id_shop = ' . $context->getIdShop() . ' ' .
                    'AND product_shop.`active` = 1 ' .
                    'AND product_shop.`visibility` IN ("both", "search") ' .
                    'AND product_shop.indexed = 1 ' .
                    'AND si.id_product IN ('. implode(',', $srcArray) . ')' .
                    'AND sw.word LIKE ';

                while (!($result = Db::getInstance()->executeS($sql . "'" . $sql_param_search . "';", true, false))) {
                    if (!$psFuzzySearch
                        || $fuzzyLoop++ > $fuzzyMaxLoop
                        || !($sql_param_search = Search::findClosestWeightestWord($context, $word))
                    ) {
                        break;
                    }
                }

                if (!$result) {
                    unset($words[$key]);
                    continue;
                }

                $productIds = array_column($result, 'id_product');
                if ($eligibleProducts2 === null) {
                    $eligibleProducts2 = $productIds;
                } else {
                    $eligibleProducts2 = array_intersect($eligibleProducts2, $productIds);
                }

                $scoreArray[] = 'sw.word LIKE \'' . $sql_param_search . '\'';
            }
            $wordCnt += count($words);
            if ($eligibleProducts2) {
                $eligibleProducts2Full = array_merge($eligibleProducts2Full, $eligibleProducts2);
            }
        }
        //All Products ID's that exist in the src array
        $eligibleProducts2Full = array_unique($eligibleProducts2Full);
        
        return $eligibleProducts2Full;
        
    }
}