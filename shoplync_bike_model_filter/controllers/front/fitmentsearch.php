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

use PrestaShop\PrestaShop\Core\Product\Search\Pagination;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

include_once dirname (__FILE__).'/FitmentFilterController.php';
include_once dirname (_PS_MODULE_DIR_).'/modules/shoplync_bike_model_filter/classes/helper.php';
/**
 * Class itself
 */
class shoplync_bike_model_filterfitmentsearchModuleFrontController extends FitmentFilterProductListingFrontController
{   

    /**
     * For allow SSL URL
     */
    public $ssl = true;

    /**
     * String Internal controller name
     */
    //public $php_self = 'module-shoplync_bike_model_filter-fitmentsearch';
    //public $php_self = 'fitmentsearch';
    
    /**
     * Sets default medias for this controller
     */
    public function setMedia()
    {
        /**
         * Set media
         */
        parent::setMedia();
        
        $this->addCSS(_PS_MODULE_DIR_.'/modules/shoplync_bike_model_filter/views/css/front.css');
        $this->addJS(_PS_MODULE_DIR_.'/modules/shoplync_bike_model_filter/views/js/front.js');
       
    }

    public function getCanonicalURL()
    {
        $params = [];
        return $this->context->link->getModuleLink('shoplync_bike_model_filter', 'fitmentsearch', $params, true);
    }    
    public function getCanonicalURL2($params = [])
    {
        return $this->context->link->getModuleLink('shoplync_bike_model_filter', 'fitmentsearch', $params, true);
    }
    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        return $breadcrumb;
    }

    /**
     * Initializes controller
     *
     * @see FrontController::init()
     * @throws PrestaShopException
     */
    public function init()
    {
        $this->page_name = 'fitmentsearch';

        $this->display_column_left = false;
        $this->display_column_right = false;

        if (!isset($this->module) || !is_object($this->module)) {
            $this->module = Module::getInstanceByName('shoplync_bike_model_filter');
        }

        parent::init();

        dbg::m('inside init');
        $this->generateContent();
    }

    /**
     * Initializes page content variables
     */
    public function initContent()
    {
        parent::initContent();
    }

    public function generateContent()
    {
        /*
        only allow to either set 
        brand + make/model/year
        type + brand
        type  + make/model/year
        make/model/year
        brand/type + make/model/year
        */

        $this->search_string = Tools::getValue('s');
        if (!$this->search_string) {
            $this->search_string = Tools::getValue('search_query');
        }

        $this->search_tag = Tools::getValue('tag');

        $this->context->smarty->assign(
            [
                'search_string' => $this->search_string,
                'search_tag' => $this->search_tag,
                'subcategories' => [],
            ]
        );

        dbg::m('posted: '.print_r($_POST, true));
        dbg::m('getted: '.print_r($_GET, true));

        $brand_id = Tools::isSubmit('selectBrand') ? Tools::getValue('selectBrand') : null;
        $type_id = Tools::isSubmit('selectType') ? Tools::getValue('selectType') : null;
        $make_id = Tools::isSubmit('selectMake') ? Tools::getValue('selectMake') : null;
        $model_id = Tools::isSubmit('selectModel') ? Tools::getValue('selectModel') : null;
        $year_id = Tools::isSubmit('selectYear') ? Tools::getValue('selectYear') : null;
        
        $universal = Tools::isSubmit('universalFitment');
        $fluids = Tools::isSubmit('universalFluids');
        
        $this->posted_filter_params = [
            'brand_id' => $brand_id,
            'type_id' => $type_id,
            'make_id' => $make_id,
            'model_id' => $model_id,
            'year_id' => $year_id,
            'universal' => $universal,
            'fluids' => $fluids,
        ];
        
        $this->context->smarty->assign('tpl_dir', _PS_THEME_DIR_);
        $this->context->smarty->assign("psversion", (float)_PS_VERSION_);

        $this->template = 'module:shoplync_bike_model_filter/views/templates/front/search.tpl';
        if($make_id != null && $model_id != null && $year_id != null)
        {
            //get results
                dbg::m('inside fitmentsearch, after validation');
                
                if(!$this->context->customer->isLogged())
                {
                    //save search data in cookie i.e: vehicle_id
                    //to be used later by fitmentprompt inside products 
                    //&& prompts under the filter in fitment search
                }
        }
        
        //set cookie according to what they filtered
        $this->context->cookie->__set('make_id', $make_id);
        $this->context->cookie->__set('model_id', $model_id);
        $this->context->cookie->__set('year_id', $year_id);
        $this->context->cookie->write();
        
        
        if (Tools::getIsset('from-xhr')) {
            dbg::m('from xhr');
            //$this->ajax = true;
            $this->doProductSearch('');
        } else {
            $this->ajax = false;
            $tpl_vars = $this->getProductSearchVariables();
            $this->context->smarty->assign('listing', $tpl_vars);

            if(!Tools::getIsset('order') && !Tools::getIsset('orderby'))
                 Media::addJsDef([ 'new_pretty_url' => $this->updateQueryString() ]);
        }
        dbg::m('after setting tpl vars');
    }
    /**
     * Save form data.
     */
    public function postProcess()
    {
        return parent::postProcess(); 
    }
    
    protected function updateQueryString(array $extraParams = null)
    {
        if ($extraParams === null) {
            $extraParams = array();
        }
        
        dbg::m('Our Params: '.print_r($this->posted_filter_params, true));
        dbg::m('Extra Params: '.print_r($extraParams, true));
        
        if (array_key_exists('q', $extraParams)) {
            return parent::updateQueryString($extraParams);
        }
        
        //include search string if available
        if(Tools::isSubmit('s'))
            $extraParams['s'] = Tools::getValue('s');
        
        
        $pretty_slug = self::getPrettyFilterSlug($this->posted_filter_params);
        if($pretty_slug != null && is_array($pretty_slug))
        {
            $params = array_merge($pretty_slug['params'], $extraParams);
            
            $args = http_build_query($params);
            $pretty_url = $this->getCanonicalURL().$pretty_slug['url'].(!empty($args) ? '?'.$args : '');
            dbg::m('Pretty URL: '.$pretty_url);
            
            return $pretty_url;
        }
                
        //Used to translate internal var names to website parameters
        $url_key = [
            'brand_id' => 'selectBrand',
            'type_id' => 'selectType',
            'make_id' => 'selectMake',
            'model_id' => 'selectModel',
            'year_id' => 'selectYear',
            'universal' => 'universalFitment',
            'fluids' => 'universalFluids',
        ];
        //only add params that are not empty if any
        if(!empty($this->posted_filter_params))
        {
            foreach($this->posted_filter_params as $key => $value)
            {
                if(!empty($value) && isset($value))
                    $extraParams[(array_key_exists($key,$url_key) ? $url_key[$key] : $key)] = $value;
            }
        }

        $link = $this->context->link->getModuleLink('shoplync_bike_model_filter', 'fitmentsearch', $extraParams, true);
        dbg::m('Link URL: '.$link);

        return $link;
    }
    public static function getPrettyFilterSlug($params = [], $id_lang = null)
    {
        if(empty($params))
            return null;
        
        if($id_lang == null)
            $id_lang = Configuration::get('PS_LANG_DEFAULT');
        
        $brand_id = !empty($params['brand_id']) && isset($params['brand_id']) ? $params['brand_id'] : null;
        $type_id = !empty($params['type_id']) && isset($params['type_id']) ? $params['type_id'] : null;
        $make_id = !empty($params['make_id']) && isset($params['make_id']) ? $params['make_id'] : null;
        $model_id = !empty($params['model_id']) && isset($params['model_id']) ? $params['model_id'] : null;
        $year_id = !empty($params['year_id']) && isset($params['year_id']) ? $params['year_id'] : null;
        
        $manufacturer = $brand_id != null ? new Manufacturer($brand_id, $id_lang) : null;
        
        $type = null;
        if($type_id != null)
        {
            $type = shoplync_bike_model_filter::getTypeInfo($type_id, false);
            $type = array_pop($type);
        }
        $vehicle_id = ($model_id != null && $year_id) ? shoplync_bike_model_filter::GetVehicleID($model_id, $year_id) : null;
        $vehicle = null;
        if($vehicle_id != null)
        {
            $vehicle = shoplync_bike_model_filter::GetVehicleInfo($vehicle_id, false);
            $vehicle = array_pop($vehicle);
        }
        $type_name = is_array($type) && !empty($type) ? $type['name'] : null;
        $mfg_name = $manufacturer != null ? shoplync_bike_model_filter::GeneratePrettyUrl($manufacturer->name) : null;
        $vehicle_name = is_array($vehicle) && !empty($vehicle) ? shoplync_bike_model_filter::GeneratePrettyUrl($vehicle['model_name']) : null;
        
        $additional_params = [];
        if(isset($params['universal']) && $params['universal'])
            $additional_params['universalFitment'] = true;
        
        if(isset($params['fluids']) && $params['fluids'])
            $additional_params['universalFluids'] = true;
        
        // example.ca/arrow/street/2022-suzuki-gsxr1000/11-22-125-6810-68/
        //'fitment/{brandName}/{typeName}/{yearMakeModelName}/{selectBrand}-{selectType}-{selectMake}-{selectModel}-{selectYear}/{rewrite}',
        if($brand_id != null && $type_id != null && $make_id != null  && $model_id != null  && $year_id != null 
            && $mfg_name != null && $type_name != null && $vehicle_name != null)
        {
            $ending_values = implode('-',array( $brand_id, $type_id, $make_id, $model_id, $year_id ));
            return [ 'url' => strtolower( $mfg_name.'/'.$type_name.'/'.$vehicle_name.'/'.$ending_values.'/' ), 'params' => $additional_params];
        }
        // example.ca/arrow/2022-suzuki-gsxr1000/12-125-6810-68/
        //'rule' => 'fitment/{brandName}/{yearMakeModelName}/{selectBrand}-{selectMake}-{selectModel}-{selectYear}/{rewrite}',
        else if($brand_id != null && $make_id != null  && $model_id != null  && $year_id != null 
            && $mfg_name != null && $vehicle_name != null)
        {
            $ending_values = implode('-',array( $brand_id, $make_id, $model_id, $year_id ));
            return [ 'url' => strtolower( $mfg_name.'/'.$vehicle_name.'/'.$ending_values.'/' ), 'params' => $additional_params];
        }
        // example.ca/2022-suzuki-gsxr1000/125-6810-68/
        //'rule' => 'fitment/{yearMakeModelName}/{selectMake}-{selectModel}-{selectYear}/{rewrite}',
        else if($make_id != null  && $model_id != null  && $year_id != null && $vehicle_name != null)
        {
            $ending_values = implode('-',array( $make_id, $model_id, $year_id ));
            return [ 'url' => strtolower( $vehicle_name.'/'.$ending_values.'/' ), 'params' => $additional_params];
        }
        // example.ca/arrow/street/125-6810/
        //'rule' => 'fitment/{brandName}/{typeName}/{selectBrand}-{selectType}/{rewrite}',
        else if($brand_id != null  && $type_id != null && $mfg_name != null && $type_name != null)
        {
            $ending_values = implode('-',array( $brand_id, $type_id ));
            return [ 'url' => strtolower( $mfg_name.'/'.$type_name.'/'.$ending_values.'/' ), 'params' => $additional_params];
        }
        return null;
    }
    
}