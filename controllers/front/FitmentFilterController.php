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
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;

include_once dirname (_PS_MODULE_DIR_).'/modules/shoplync_bike_model_filter/classes/FitmentFilterSearchProvider.php';
include_once dirname (_PS_MODULE_DIR_).'/modules/shoplync_bike_model_filter/classes/helper.php';

abstract class FitmentFilterProductListingFrontController extends ProductListingFrontController {
 
    /** @var int vehicle ID */ 
    public $posted_filter_params = [];

    protected $search_string;
    protected $search_tag;

  
    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setQueryType('search')
            ->setSortOrder(new SortOrder('product', 'position', 'desc'))
            ->setSearchString($this->search_string)
            ->setSearchTag($this->search_tag);

        return $query;
    }
    protected function getDefaultProductSearchProvider()
    {
        return new FitmentFilterSearchProvider(
            //(isset($this->module) ? $this->module : null),
            $this->getTranslator(),
            $this->posted_filter_params
        );
    }
    public function getListingLabel()
    {
        return $this->getTranslator()->trans('Matching Products', array(), 'Shop.Theme.Catalog');
    }
    private function getProductSearchProviderFromModules($query)
    {
        return null;
    }
    protected function getProductSearchVariables()
    {
        $context = $this->getProductSearchContext();
        $query = $this->getProductSearchQuery();
        
        $provider = $this->getProductSearchProviderFromModules($query);
        if (null === $provider) {
            $provider = $this->getDefaultProductSearchProvider();
        }
        
        $resultsPerPage = (int) Tools::getValue('resultsPerPage');
        if ($resultsPerPage <= 0 || $resultsPerPage > 36) {
            $resultsPerPage = Configuration::get('PS_PRODUCTS_PER_PAGE');
        }
        $query
            ->setResultsPerPage($resultsPerPage)
            ->setPage(max((int) Tools::getValue('page'), 1));
            
        if (Tools::getValue('order')) {
            $encodedSortOrder = Tools::getValue('order');
        } else {
            $encodedSortOrder = Tools::getValue('orderby', null);
        }
        
        if ($encodedSortOrder) {
            try {
                $selectedSortOrder = SortOrder::newFromString($encodedSortOrder);
            } catch (Exception $e) {
                $selectedSortOrder = new SortOrder('product', 'name', 'asc');
            }
            $query->setSortOrder($selectedSortOrder);
        }
        
        $encodedFacets = Tools::getValue('q');
        $query->setEncodedFacets($encodedFacets);
        
        $result = $provider->runQuery(
            $context,
            $query
        );
        
        if (!$result->getCurrentSortOrder()) {
            $result->setCurrentSortOrder($query->getSortOrder());
        }
        $products = $this->prepareMultipleProductsForTemplate(
            $result->getProducts()
        );
        
        if ($provider instanceof FacetsRendererInterface) {
            $rendered_facets = $provider->renderFacets(
                $context,
                $result
            );
            $rendered_active_filters = $provider->renderActiveFilters(
                $context,
                $result
            );
        } else {
            $rendered_facets = $this->renderFacets(
                $result
            );
            $rendered_active_filters = $this->renderActiveFilters(
                $result
            );
        }
        $pagination = $this->getTemplateVarPagination(
            $query,
            $result
        );
        $sort_orders = $this->getTemplateVarSortOrders(
            $result->getAvailableSortOrders(),
            $query->getSortOrder()->toString()
        );
        $sort_selected = false;
        if (!empty($sort_orders)) {
            foreach ($sort_orders as $order) {
                if (isset($order['current']) && true === $order['current']) {
                    $sort_selected = $order['label'];
                    break;
                }
            }
        }
        $currentUrlParams = array(
            'q' => $result->getEncodedFacets(),
        );
        if ((Tools::getIsset('order') || Tools::getIsset('orderby')) && $result->getCurrentSortOrder() != null)
            $currentUrlParams['order'] = $result->getCurrentSortOrder()->toString();
        
        //Sets all the other variables used by the tpl filtes
        $this->assignGeneralPurposeVariables();
        $url = $this->updateQueryString($currentUrlParams);

        $searchVariables = array(
            'label' => $this->getListingLabel(),
            'products' => $products,
            'sort_orders' => $sort_orders,
            'sort_selected' => $sort_selected,
            'pagination' => $pagination,
            'rendered_facets' => $rendered_facets,
            'rendered_active_filters' => $rendered_active_filters,
            'js_enabled' => $this->ajax,
            'current_url' => $url,
        );
        Hook::exec('actionProductSearchComplete', $searchVariables);
        if (version_compare(_PS_VERSION_, '1.7.1.0', '>=')) {
            Hook::exec('actionProductSearchAfter', $searchVariables);
        }
        
        return $searchVariables;
    }
}