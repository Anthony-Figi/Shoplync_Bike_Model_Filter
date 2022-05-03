<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

//Include the class of the new model 
include_once dirname (__FILE__).'/classes/VehicleMake.php';
include_once dirname (__FILE__).'/classes/VehicleModel.php';
include_once dirname (__FILE__).'/classes/VehicleYear.php';
include_once dirname (__FILE__).'/classes/VehicleType.php';
include_once dirname (__FILE__).'/classes/Vehicle.php';
include_once dirname (__FILE__).'/classes/WebserviceSpecificManagementVehicleUpload.php';
include_once dirname (__FILE__).'/classes/FitmentFilterSearchProvider.php';



if(!class_exists('dbg'))
{
    include_once dirname (__FILE__).'/classes/helper.php';
    class_alias(get_class($shoplync_dbg), 'dbg');
}

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;

class Shoplync_bike_model_filter extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'shoplync_bike_model_filter';
        $this->tab = 'search_filter';
        $this->version = '1.0.0';
        $this->author = 'Shoplync';
        $this->need_instance = 0;

        $this->controllers = array('mygarage', 'query', 'fitmentsearch');
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Bike Model Filter');
        $this->description = $this->l('A SMS Pro add-on module. Designed to allow user to filter products by bike model fitment. Allows users to save and add bike model fitments to their garage and set their prefered bike model.');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SHOPLYNC_FITMENT_COMBINATION_HIDE', true);
        Configuration::updateValue('SHOPLYNC_FITMENT_UNIVERSAL_MAKE', '');
        Configuration::updateValue('SHOPLYNC_FITMENT_UNIVERSAL_VEHICLE', '');
        Configuration::updateValue('SHOPLYNC_FITMENT_FLUIDS_VEHICLE', '');


        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayContentWrapperTop') &&
            $this->registerHook('displayNavFullWidth') &&
            $this->registerHook('displayCustomerAccount') && 
            $this->registerHook('displayProductAdditionalInfo') &&
            $this->registerHook('displayProductTab') && 
            $this->registerHook('displayProductTabContent') && 
            $this->registerHook('addWebserviceResources') &&
            $this->registerHook('productSearchProvider') &&
            $this->registerHook('moduleRoutes');
            
    }

    public function uninstall()
    {
        Configuration::deleteByName('SHOPLYNC_FITMENT_COMBINATION_HIDE');
        Configuration::deleteByName('SHOPLYNC_FITMENT_UNIVERSAL_MAKE');
        Configuration::deleteByName('SHOPLYNC_FITMENT_UNIVERSAL_VEHICLE');
        Configuration::deleteByName('SHOPLYNC_FITMENT_FLUIDS_VEHICLE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitShoplync_bike_model_filterModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShoplync_bike_model_filterModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l(''),
                //'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Hide For Combinations'),
                        'name' => 'SHOPLYNC_FITMENT_COMBINATION_HIDE',
                        'is_bool' => true,
                        'desc' => $this->l('Will hide fitment on products with unselected options. (For developers only, must be disabled via JavaScript on the front end)'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Select Universal Brand',
                        'name' => 'SHOPLYNC_FITMENT_UNIVERSAL_MAKE',
                        'class' => 'chosen',
                        'options' => array(
                            'optiongroup'=>array(
                                'label'=>'label',
                                'query'=>array(
                                    array(
                                        'label'=>'Select A Make',
                                        'options'=> self::GetMakes('id'),
                                    )
                                ),
                            ),
                            'options'=>array(
                                 'query'=>'options',
                                 'id'=>'id',
                                 'name'=>'name'
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Select Vehicle (Fluids)',
                        'name' => 'SHOPLYNC_FITMENT_FLUIDS_VEHICLE',
                        'class' => 'chosen',
                        'options' => array(
                            'optiongroup'=>array(
                                'label'=>'label',
                                'query'=>array(
                                    array(
                                        'label'=>'Select A Vehicle',
                                        'options'=> self::GetModels(),
                                    )
                                ),
                            ),
                            'options'=>array(
                                 'query'=>'options',
                                 'id'=>'model_id',
                                 'name'=>'name'
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Select Vehicle (Universal)',
                        'name' => 'SHOPLYNC_FITMENT_UNIVERSAL_VEHICLE',
                        'class' => 'chosen',
                        'options' => array(
                            'optiongroup'=>array(
                                'label'=>'label',
                                'query'=>array(
                                    array(
                                        'label'=>'Select A Vehicle',
                                        'options'=> self::GetModels(),
                                    )
                                ),
                            ),
                            'options'=>array(
                                 'query'=>'options',
                                 'id'=>'model_id',
                                 'name'=>'name'
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SHOPLYNC_FITMENT_COMBINATION_HIDE' => Configuration::get('SHOPLYNC_FITMENT_COMBINATION_HIDE', true),
            'SHOPLYNC_FITMENT_UNIVERSAL_MAKE' => Configuration::get('SHOPLYNC_FITMENT_UNIVERSAL_MAKE', ''),
            'SHOPLYNC_FITMENT_UNIVERSAL_VEHICLE' => Configuration::get('SHOPLYNC_FITMENT_UNIVERSAL_VEHICLE', ''),
            'SHOPLYNC_FITMENT_FLUIDS_VEHICLE' => Configuration::get('SHOPLYNC_FITMENT_FLUIDS_VEHICLE', ''),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        
        /* Set Make To Not Visisble */
        $make_id = Configuration::get('SHOPLYNC_FITMENT_UNIVERSAL_MAKE', NULL);
        if($make_id !== null)
        {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'make` SET is_visible = ';
            //set all make table to visible
            Db::getInstance()->execute($sql.'TRUE');
            //update this make to be hidden
            Db::getInstance()->execute($sql.'FALSE WHERE make_id = '.$make_id);            
        }

    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'views/js/front.js');
        $this->context->controller->addCSS($this->_path.'views/css/front.css');
        //
        //$this->context->link->getAdminLink('shoplync_bike_model_filter')
        Media::addJsDef([
            'adminajax_link' => $this->context->link->getModuleLink('shoplync_bike_model_filter', 'query', array(), true),
            'ps_customer_id' => $this->context->customer->id,
            'ps_product_id' => '',
            'ps_attribute_id' => ''
        ]);
    }
   
    /*
     * Helper function that will generate a HTML5 <select> element from an array list of array objects.
     *
     * $list array() - The provided data set of array objects that will be used to generate select list
     * $default_value string - The default value that will be selected by the <select> element
     * $class_Name string - Any additional class names to be added to the <select> element
     * $id_Name string - The id to assign to the element
     * $enabled boolean - Whether the <select> element will be enabled/disabled
     * $onChange string - JavaScript function
     * $optionsOnly boolean - Whether this function will only return <option> elements
     * $enableBold boolean - Whether the options will have bolded text or not
     *
     * return string - Raw HTML5 as a string (Not escaped)
    */
    public static function GenerateList($list, $default_value = '-', $class_Name = '', $id_Name = '', $enabled = true, $onChange = '', $optionsOnly = false, $enableBold = false)
    {
        if($optionsOnly)
            $dropdown = array('<option selected value="">'.$default_value.'</option>');
        else
            $dropdown = array('<select '.(empty($id_Name) ? '' : 'id="'.$id_Name.'"').' class="form-control form-control-select '.$class_Name.'" name="'.(empty($id_Name) ? '' : $id_Name).'" size="1" '.($enabled ? '' : 'disabled').' '.(empty($onChange) ? '' : 'onchange="'.$onChange.'"').'>', '<option selected value="">'.$default_value.'</option>');
        
        if(!empty($list) && is_array($list))
        {
            foreach($list as &$option)
            {
                if(is_array($option) && !empty($option))
                {
                    $val = array_values($option);
                    array_push($dropdown, '<option '.(strpos($val[1], '&nbsp;') === false && $enableBold ? 'class="font-weight-bold"' : '').' value="'.$val[0].'">'.$val[1].'</option>');
                }
                else
                    array_push($dropdown, '<option value="'.$option.'">'.$option.'</option>');
            }
        }
        if(!$optionsOnly)
            array_push($dropdown, '</select>');
        
        return implode($dropdown);
    }
    
    /*
     * Helper function that queries database for all vehicle types and calls GenerateList()
     * to generate a HTML5 <select> element.
     *
     * return string - Raw HTML5 as a string (Not escaped)
     *
    */
    public static function GenerateTypeList()
    {
        $items = Array();
        
        $sql = 'SELECT id_type, name, parent_id_type, depth FROM `' . _DB_PREFIX_ . 'type` WHERE is_visible IS TRUE';//get all child cateogies
        $result = Db::getInstance()->executeS($sql);
        
        if(!empty($result))
        {
            foreach($result as $row) 
            {
                $items[] = $row;
            }

            $hierarchy = Array();

            foreach($items as $item) {
                $parentID = empty($item['parent_id_type']) ? 0 : $item['parent_id_type'];

                if(!isset($hierarchy[$parentID])) {
                    $hierarchy[$parentID] = Array();
                }
                $item['name'] = str_repeat('&nbsp;&nbsp;', $item['depth']).$item['name'];//emulate hierarchy
                $hierarchy[$parentID][] = $item;
            }        
            
            $items = Array(); //reset items array

            foreach($hierarchy[0] as $item)
            {
                array_push($items, $item);
                $children = self::CheckForChildren($item['id_type'], $hierarchy);
                if(!empty($children))
                {
                    array_push($items, ...$children);
                }
            }
            
            //dbg::m('Types:'.print_r($items, true));

            if(is_array($items) && !empty($items))
                return self::GenerateList($items, "-", '', 'selectType', true, 'typeChanged(event, this)', false, true);
            
        }
        
        return self::GenerateList([], "Type", '', 'selectType', false, 'typeChanged(event, this)', false, true);
    }
    
    /*
     * Helper function callede by GenerateTypeList() to check if an $id_type is the parent of
     * any type elemtns contained in the $items array dataset
     *
     * $id_type int - The parent id, that will be used as search term
     * $items array[] - The full list of db type elements
     *
     * return array() - An array list of found children of $id_type
     *
    */
    public static function CheckForChildren($id_type, $items)
    {
        if(is_array($items) && !empty($items) && is_numeric($id_type))
        {
            if(array_key_exists($id_type, $items))
            {
                $returnList = Array();
                foreach($items[$id_type] as $child)
                {
                    array_push($returnList, $child);
                    $foundChildren = self::CheckForChildren($child['id_type'], $items);
                    if(!empty($foundChildren))
                    {
                        array_push($returnList, ...$foundChildren);
                    }
                }
                return $returnList;
            }
            else
                return [];
        }
    }
    /*
     * Queries the database for all the vehicle makes
     *
     * $alias_id string - Ability to set the alias name for the make_id column
     * $visibleOnly boolean - Whether to filter out the 'hidden' types
     *
     * return array - The database result
    */
    protected static function GetMakes($alias_id = null, $visibleOnly = false)
    {
        $sql = 'SELECT make_id'.(!empty($alias_id) ? ' AS '.$alias_id : '').', name FROM `' . _DB_PREFIX_ . 'make`'.($visibleOnly ? ' WHERE is_visible = TRUE' : '');
        $result = Db::getInstance()->executeS($sql);
        
        if(empty($result))
            return [];
        
        return $result;
    }
    /*
     * Queries the database for all the vehicle models, can be filtered out by make/type_id
     *
     * $make_id int - Whether to filter by models that match the make_id
     * $type_id int -  Whether to filter by models that match the type_id
     *
     * return array - The database result
    */
    protected static function GetModels($make_id = null, $type_id = 0)
    {
        $condition = '';
        
        if(is_numeric($type_id) && $type_id < 0)
            $type_id = 0;//turn any negative number to default 0
     
        
        if($type_id !== 0)
            $condition .= 'type_id = '.($type_id == null ? 'NULL' : $type_id);
        
        if($make_id !== null && is_numeric($make_id) && $make_id > 0)
            $condition .= (!empty($condition) ? ' AND ' : '').'make_id = '.$make_id;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'model`'.(empty($condition) ? '' : ' WHERE'.$condition);
        $result = Db::getInstance()->executeS($sql);
        
        if(empty($result))
            return [];
        
        return $result;
    }
    /*
     * Helper function that queries database for all vehicle makes and calls GenerateList()
     * to generate a HTML5 <select> element.
     *
     * return string - Raw HTML5 as a string (Not escaped)
     *
    */
    public static function GenerateMakeList()
    {        
        $result = self::GetMakes(null, true);
        if(is_array($result) && !empty($result))
            return self::GenerateList($result, "-", '', 'selectMake', true, 'makeChanged(event, this)');
        
        return self::GenerateList([], "Make", '', 'selectMake', true, 'makeChanged(event, this)');
    }
    /*
     * Helper function that queries database for all vehicle models and calls GenerateList()
     * to generate a HTML5 <select> element.
     *
     * return string - Raw HTML5 as a string (Not escaped)
     *
    */
    public static function GenerateModelList()
    {        
        return self::GenerateList([], "-", "", "selectModel", false, 'modelChanged(event, this)');
    }
    /*
     * Helper function that queries database for all vehicle years and calls GenerateList()
     * to generate a HTML5 <select> element.
     *
     * return string - Raw HTML5 as a string (Not escaped)
     *
    */
    public static function GenerateYearList()
    {
        return self::GenerateList([], "-", "", "selectYear", false, 'yearChanged(event, this)');
    }
    /*
     * Helper function that queries database for all product manufacturers and calls GenerateList()
     * to generate a HTML5 <select> element.
     *
     * return string - Raw HTML5 as a string (Not escaped)
     *
    */
    public static function GenerateBrandList()
    {
        $sql = 'SELECT id_manufacturer, name FROM `' . _DB_PREFIX_ . 'manufacturer` WHERE active = 1  ORDER BY name ASC';
        $result = Db::getInstance()->executeS($sql);
        if(is_array($result) && !empty($result))
            return self::GenerateList($result, "-", '', 'selectBrand', true);
        
        return self::GenerateList([], "Make", '', 'selectBrand', true);
    }
    
    /*
     * Retrieves a specific customers, garage and all their vehicle models
     *
     * $cust_id int - The id of the customer whose garage should be retrieved
     * $detailed boolean -  Whether to also include vehicle_id and make_name
     * $use_vehicle_id boolean -  Whether to return vehichle/garage id as the first column, (used as the <option> value by GenerateList()) 
     *
     * return array - The database result
    */
    public static function GetCustomerGarage($cust_id = null, $detailed = false, $use_vehicle_id = false)
    {
        if(!isset($cust_id))
            return [];
        /*

            SELECT g.garage_id, g.vehicle_name, CONCAT(y.year, ' ', mk.name, ' ', m.name) AS model_name, g.image_path, v.vehicle_id, mk.name AS make_name, !ISNULL(pv.prefered_vehicle_id) AS is_prefered 
            FROM ps_ca_garage AS g 
            LEFT JOIN ps_ca_vehicle AS v ON g.vehicle_id = v.vehicle_id 
            LEFT JOIN ps_ca_model AS m ON v.model_id = m.model_id 
            LEFT JOIN ps_ca_make AS mk ON m.make_id = mk.make_id 
            LEFT JOIN ps_ca_year AS y ON v.id_year = y.id_year 
            LEFT JOIN ps_ca_prefered_vehicle AS pv ON g.garage_id = pv.garage_id
            WHERE g.customer_id = 27089;
        */
        $sql = 'SELECT '.($use_vehicle_id ? 'v.vehicle_id,' : 'g.garage_id,').' g.vehicle_name, CONCAT(y.year, " ", mk.name, " ", m.name) AS model_name, g.image_path, '.($detailed ? 'v.vehicle_id, mk.name AS make_name, ' : '').'!ISNULL(pv.prefered_vehicle_id) AS is_prefered FROM `' . _DB_PREFIX_ . 'garage` AS g'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'vehicle` AS v ON g.vehicle_id = v.vehicle_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'model` AS m ON v.model_id = m.model_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'make` AS mk ON m.make_id = mk.make_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'year` AS y ON v.id_year = y.id_year'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'prefered_vehicle` AS pv ON g.garage_id = pv.garage_id'
            .' WHERE g.customer_id = '.$cust_id;
        
        $result = Db::getInstance()->executeS($sql);
   
        return $result;
    }
    
    /*
     * Retrieves detailed information about a specific customers garage vehicle
     *
     * $cust_id int - The id of the customer whose garage should be retrieved
     * $garage_id int - The id of the customer garage vehicle to retrieve
     * $detailed boolean -  Whether to also include if a vehicle is set as prefered or not
     *
     * return array - The database result
    */
    public static function GetCustomerGarageVehicle($cust_id = null, $garage_id = null, $detailed = false)
    {
        if($cust_id == null || $garage_id == null || !is_numeric($cust_id) || !is_numeric($garage_id))
            return [];
        
        
        $sql = 'SELECT g.garage_id, g.vehicle_id, g.customer_id, g.vehicle_name, g.image_path '.($detailed ? '!ISNULL(pv.prefered_vehicle_id) AS is_prefered' : '').'FROM `' . _DB_PREFIX_ . 'garage` AS g'.($detailed ? ' LEFT JOIN `' . _DB_PREFIX_ . 'prefered_vehicle` AS pv ON g.garage_id = pv.garage_id' : '')
        .' WHERE g.customer_id = '.$cust_id.' AND g.garage_id = '.$garage_id;
        
        $result = Db::getInstance()->executeS($sql);

        return $result;
    }
    /*
     * Retrieves vehicle model name based off the id stored in the cookie, if set
     *
     *
     * return string - The vehicle model name
    */
    public static function GetModelNameFromCookie()
    {
        $modelInfo = [];
        if(Context::getContext()->cookie->__isset('vehicle_id'))
        {
            $modelInfo = self::GetVehicleInfo((int) filter_var(Context::getContext()->cookie->__get('vehicle_id')));
            dbg::m('returned :'.print_r($modelInfo, true));
            if(!empty($modelInfo))
                $modelInfo = array_pop($modelInfo);
            
            if(array_key_exists('model_name',$modelInfo))
                $modelInfo = $modelInfo['model_name'];
        }
        
        return $modelInfo;
    }
    
    /* Will only show the filter on the listed pages */
    protected $enabled_pages = array('search', 'index');
    
    /**
    * Call content wrapper top hook
    */   
    public function hookDisplayContentWrapperTop()
    {
        $modelInfo = self::GetModelNameFromCookie();
        if( in_array('fitmentsearch', [strtolower($this->context->controller->php_self ?? 'unknown'), strtolower($this->context->controller->page_name ?? 'unknown')]) )
        {
            $inline_js = '<script type="text/javascript">'
                .'if (typeof window.new_pretty_url !== \'undefined\') { history.pushState(null, null, window.new_pretty_url); }'
                .'</script>';

            $filterPagePrompt = '';
            if(!empty($modelInfo))
            {
                $filterPagePrompt = '<div id="currentSelected" class="promptBox bikeModelFilter img-bg py-2">'
                .'<h1 class="text-center">'.(!empty($modelInfo) ? 'Currently Selected: <strong>'.$modelInfo.'</strong>' : '').'</h1>'
                .'<button class="filterButton d-block mx-0-auto" type="button" onclick="clearFilterCookie();">Clear Selection</button>'
                .'</div>';
            }

            return $filterPagePrompt.$inline_js;
        }
        
        if( in_array(strtolower(($this->context->controller->php_self ?? $this->context->controller->page_name) ?? 'unknown'), $this->enabled_pages) )
        {
            $garageChooser = '';
            $selectedModel = '';
            if($this->context->customer->isLogged())
            {
                $productDetailsList = self::GetCustomerGarage($this->context->customer->id, false, true);
                
                dbg::m('cust garage: '.print_r($productDetailsList, true));
                
                if(!empty($productDetailsList))
                {
                    $garageChooser = '<div class="text-center my-1" id="filterFromGarage"><hr><h4>Select From Garage</h4>'
                        .self::GenerateList($productDetailsList, "-", 'my-1', 'selectGarageBike', true, 'setFilterFromVehicleID(this)')
                        .'</div>';
                }
            }
            
            if($this->context->cookie->__isset('vehicle_id') && $this->context->cookie->__isset('model_id'))
            {
                //Media::addJsDef([ 'new_pretty_url' => $this->updateQueryString() ]);
                $selectedModel .= '<script type="text/javascript">window.addEventListener(\'DOMContentLoaded\', function() {' 
                    .' document.addEventListener(\'filterAvailableEvent\', function(e) {'
                    .' if(document.getElementsByTagName(\'body\')[0].id != \'index\'){ document.getElementById(\'modelFilterButton\').click(); } '
                    .' });'
                    .'setFilterFromVehicleID(null,'.$this->context->cookie->__get('vehicle_id').');'
                    .' var c_vehicle_id = '.$this->context->cookie->__get('vehicle_id').'; '
                    //.' var c_make_id = '.$this->context->cookie->__get('make_id').'; '
                    //.' var c_model_id = '.$this->context->cookie->__get('model_id').'; '
                   // .' var c_year_id = '.$this->context->cookie->__get('year_id').'; '
                    .' }); </script>';

                if(!empty($modelInfo))
                {
                    $selectedModel .= '<div id="currentSelected" class="text-center my-1">'
                    .'<em class="mx-1">'.(!empty($modelInfo) ? 'Currently Selected: <strong>'.$modelInfo.'</strong>' : 'Clear The Current Selection').'</em>'
                    .'<button class="filterButton" type="button" onclick="clearFilterCookie();">Clear</button>'
                    .'</div>';   
                }
            }
            
            //<i class="fa fa-motorcycle px-2"></i>   
            $modelFilter = '<div class="promptBox bikeModelFilter img-bg">'
            .'<form id="filter-form" action="'.$this->context->link->getModuleLink('shoplync_bike_model_filter', 'fitmentsearch', array(), true).'" method="GET">'
            .'<div class="text-center" id="filterOptions">'
            .'<h2 class="text-center">Filter By Bike Model</h2>'
            .'<span class="control-label">Brands</span>'.self::GenerateBrandList()
            .'<span class="control-label">Type</span>'.self::GenerateTypeList()
            .'<span class="control-label">Make</span>'.self::GenerateMakeList()
            .'<span class="control-label">Model</span>'.self::GenerateModelList()
            .'<span class="control-label">Year</span>'.self::GenerateYearList()
            .'<button class="filterButton" id="modelFilterButton" type="submit" disabled>Filter</button>'
            .'<button class="filterButton" id="clearButton" type="button" onclick="resetFilter();">Reset</button></div>'
            .'<div class="text-center" id="filterCheckboxes"><input type="checkbox" id="universalFitment" name="universalFitment" value="true"><label for="universalFitment">Include Universal</label>'
            .'<input type="checkbox" id="universalFluids" name="universalFluids" value="true"><label for="universalFluids">Include Fluids</label>'
            .'</div>'
            .$garageChooser
            .$selectedModel
            .(Tools::isSubmit('s') ? '<input type="hidden" name="s" value="'. Tools::getValue('s').'">' : '').'</form></div>';
            
            
            return $modelFilter;
        }
    }
    /**
     * Call customer account hook
     */
    public function hookDisplayCustomerAccount()
    {
        $this->smarty->assign("psversion", (float)_PS_VERSION_);
        return $this->display(__FILE__, 'views/templates/front/my-account.tpl');
    }
    
    
    public function hookDisplayNavFullWidth($params)
    {
        /* Place your code here. */
    }
    
    /*
     * Helper function that retrieves vehicle type information, based on $type_id
     *
     * $type_id int - The corresponding type_id to retrieve from db
     * $extended boolean - Whether to retrieve all type information or only the name
     *
     * return array() - db result
    */
    public static function getTypeInfo($type_id, $extended = true)
    {
        if(!isset($type_id) || !is_numeric($type_id))
            return [];
        
        /*
            SELECT name, 
            FROM ps_ca_type
            WHERE g.customer_id = 27089;
        */
        $sql = 'SELECT '.($extended ? '*' : 'name')
            .' FROM `' . _DB_PREFIX_ . 'type`'
            .' WHERE  id_type = '.$type_id.' LIMIT 1';
        
        $result = Db::getInstance()->executeS($sql);
   
        return $result;
    }
    
    /*
     * Helper function that retrieves vehicle information, based on $vehicle_id
     *
     * $vehicle_id int - The corresponding vehicle_id to retrieve from db
     * $extended boolean - If extended is set to false will only return vehicle name
     *
     * return array() - the database result
    */
    public static function GetVehicleInfo($vehicle_id, $extended = true)
    {
        if(!isset($vehicle_id) || !is_numeric($vehicle_id))
            return [];
        /*
            SELECT g.garage_id, g.vehicle_name, CONCAT(y.year, ' ', mk.name, ' ', m.name) AS model_name, g.image_path, v.vehicle_id, mk.name AS make_name, !ISNULL(pv.prefered_vehicle_id) AS is_prefered 
            FROM s_ca_vehicle AS v
            LEFT JOIN ps_ca_model AS m ON v.model_id = m.model_id 
            LEFT JOIN ps_ca_make AS mk ON m.make_id = mk.make_id 
            LEFT JOIN ps_ca_year AS y ON v.id_year = y.id_year 
            WHERE g.customer_id = 27089;
        */
        $sql = 'SELECT CONCAT(y.year, " ", mk.name, " ", m.name) AS model_name'.($extended ? ', v.vehicle_id, mk.name AS make_name, y.year, mk.make_id, m.model_id, y.id_year' : '')
            .' FROM `' . _DB_PREFIX_ . 'vehicle` AS v'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'model` AS m ON v.model_id = m.model_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'make` AS mk ON m.make_id = mk.make_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'year` AS y ON v.id_year = y.id_year'
            .' WHERE v.vehicle_id = '.$vehicle_id.' LIMIT 1';
        
        $result = Db::getInstance()->executeS($sql);
   
        return $result;
    }
    /*
     * Helper function that retrieves the vehicle_id from model/year and make
     *
     * $model_id int - The corresponding model_id to retrieve from db
     * $year_id int - The corresponding year_id to retrieve from db
     * $make_id int - The corresponding make id to pull vehicle id from (Optional)
     *
     * return int - vehicle id
    */
    public static function GetVehicleID($model_id, $year_id, $make_id = null)
    {
        if(is_numeric($model_id) && is_numeric($year_id))
        {
            //SELECT vehicle_id FROM ps_ca_vehicle WHERE model_id = 435 AND id_year = 63;
            $sql = 'SELECT v.vehicle_id FROM `' . _DB_PREFIX_ . 'vehicle` AS v'.($make_id != null ? ' LEFT JOIN `' . _DB_PREFIX_ . 'model` AS m ON v.model_id = m.model_id': '').' WHERE v.model_id = '.$model_id.' AND v.id_year = '.$year_id.($make_id != null ? ' AND m.make_id = '.$make_id : '');
            $result = Db::getInstance()->executeS($sql);

            if(is_array($result) && !empty($result))
                return $result[0]['vehicle_id'];
        }
        return null;
    }
    /*
     * Helper function that retrieves the universal vehicle_id set in the admin panel otherwise default value
     *
     * $default_value int - The default value to return if the universal vehicle is not set
     *
     * return int - universal vehicle id or default_value
    */
    public static function getUniversalVehicleID($default_value = 0)
    {
        return self::firstVehicleFromModelID(Configuration::get('SHOPLYNC_FITMENT_UNIVERSAL_VEHICLE', NULL), $default_value);    
    }    
    /*
     * Helper function that retrieves the fluids vehicle_id set in the admin panel otherwise default value
     *
     * $default_value - The default value to return if the fluids vehicle is not set
     *
     * return int - fluids vehicle id or default_value
    */
    public static function getFluidsVehicleID($default_value = 0)
    {
        return self::firstVehicleFromModelID(Configuration::get('SHOPLYNC_FITMENT_FLUIDS_VEHICLE', NULL), $default_value);
    }
    /*
     * Returns the first matching Vehicle ID based off of the model_id otherwise default_value 
     *
     * $model_id int - Will be used to query the db for vehicle models
     * $default_value - The default value to return if the fluids vehicle is not set
     *
     * return int - vehicle id or default_value
    */
    protected static function firstVehicleFromModelID($model_id = null, $default_value = 0)
    {
        if(!isset($model_id) || !is_numeric($model_id) || $model_id <= 0)
            return $default_value;
        
        $matching_result = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'vehicle` WHERE model_id = '.$model_id);
        
        return (!empty($matching_result) ? $matching_result['vehicle_id'] : $default_value);
    }
    
    /*
     * Retrieves products from category type id, can include universal/fluids and filter the result by a specific manufacturer id
     *
     * $type_id int - The targeted vehicle category id
     * $combinations bool - Whether to group by product id or return the full list of all combinations, default false
     * $fullProduct bool - Whether to join the product table and all its columns
     * $query ProductSearchQuery - used to determine current page/sort order
     * $lang_id int - used to determine the appropriate title to retrieve
     * $universal int - the vehicle id associated with universal parts
     * $fluids int - the vehicle id associated with fluid/chemical parts
     * $brand_id int - The manufacturer id that can be used to narrow the search
     * 
     * return array - products according to the filter
    */
    public static function getProdutsFromTypeID($type_id, $combinations = false, $fullProduct = false, ProductSearchQuery $query = null, $lang_id = null, $universal = false, $fluids = false, $brand_id = 0, $vehicle_id = 0)
    {
        if(!isset($type_id) || !is_numeric($type_id))
            return [];
        
        if($lang_id < 0 || !is_numeric($lang_id) || $lang_id == null)
            $lang_id = (int)$this->context->language->id;
        
        $universal_vehicle_id = 0;
        if($universal)
            $universal_vehicle_id = self::getUniversalVehicleID();
        
        $fluids_vehicle_id = 0;
        if($fluids)
            $fluids_vehicle_id = self::getFluidsVehicleID();

        //SELECT v.vehicle_id, v.model_id, v.id_year, m.make_id, m.id_type FROM ps_ca_vehicle as v LEFT JOIN ps_ca_model as m ON v.model_id = m.model_id WHERE m.id_type = 3;
        //SELECT v.vehicle_id FROM ps_ca_vehicle as v LEFT JOIN ps_ca_model as m ON v.model_id = m.model_id WHERE m.id_type = 3; 

        //SELECT * from ps_ca_vehicle_fitment AS vf LEFT JOIN ps_ca_product p ON vf.id_product = p.id_product WHERE id_vehicle IN (SELECT v.vehicle_id FROM ps_ca_vehicle as v LEFT JOIN ps_ca_model as m ON v.model_id = m.model_id WHERE m.id_type = 3) GROUP BY vf.id_product;
        
        $col_product_lang = ['pl.id_shop', 'pl.id_lang', 'pl.description', 'pl.description_short', 'pl.link_rewrite', 'pl.meta_description', 'pl.meta_keywords', 'pl.meta_title', 'pl.name', 'pl.available_now', 'pl.available_later', 'pl.delivery_in_stock', 'pl.delivery_out_stock' ];
        
        $sql = 'SELECT vf.id_product'.($combinations ? ', vf.id_product_attribute, vf.id_vehicle, vf.id_fitment' : '')
        .($fullProduct ? ', p.*, '.implode(',', $col_product_lang) : '')
        .' FROM `' . _DB_PREFIX_ . 'vehicle_fitment` AS vf '
        .($fullProduct ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product` AS p ON vf.id_product = p.id_product'
        .' LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` AS pl ON vf.id_product = pl.id_product AND pl.id_lang = '.$lang_id.' ' : '')
        .'WHERE (vf.id_vehicle IN (SELECT v.vehicle_id FROM `' . _DB_PREFIX_ . 'vehicle` AS v LEFT JOIN `' . _DB_PREFIX_ . 'model` AS m ON v.model_id = m.model_id WHERE m.id_type = '.$type_id.(is_numeric($vehicle_id) && $vehicle_id > 0 ? ' AND vf.id_vehicle = '.$vehicle_id : '').') '.($universal && $universal_vehicle_id > 0 ? ' OR vf.id_vehicle = '.$universal_vehicle_id : '').($fluids && $fluids_vehicle_id > 0 ? ' OR vf.id_vehicle = '.$fluids_vehicle_id : '').')'.(is_numeric($brand_id) && $brand_id > 0 ? ' AND p.id_manufacturer = '.$brand_id : '').($combinations ? '' : ' GROUP BY vf.id_product');
    
        $order_by = null;
        if($query != null)
        {
            $current_page = $query->getPage();
            $results_per_page = $query->getResultsPerPage();
            
            $order_by = $query->getSortOrder()->toLegacyOrderBy();
            $order_by = !Validate::isOrderBy($order_by) || $order_by == null ? 'name' : $order_by;
            
            $order_way = $query->getSortOrder()->toLegacyOrderWay();
            $order_way = !Validate::isOrderWay($order_way) || $order_way == null ? 'ASC' : $order_way;
            
            if($order_by == 'name' || $order_by == 'position')
                $sql .= ' ORDER BY pl.name '.$order_way;
            
            $sql .= ' LIMIT '.(int)(($current_page - 1) * $results_per_page) . ', '. (int)$results_per_page;
            
        }
        dbg::m('search query with type: '.$sql);
        $result = Db::getInstance()->executeS($sql);
        
        if($query != null && $order_by == 'price')
            Tools::orderbyPrice($result, $order_way);
   
        return $result;
    }

    
    /*
     * Retrieves products from one vehicle id, can include universal/fluids and filter the result by a specific manufacturer id
     *
     * $vehicle_id int - The targeted vehicle
     * $combinations bool - Whether to group by product id or return the full list of all combinations, default false
     * $fullProduct bool - Whether to join the product table and all its columns
     * $query ProductSearchQuery - used to determine current page/sort order
     * $lang_id int - used to determine the appropriate title to retrieve
     * $universal int - the vehicle id associated with universal parts
     * $fluids int - the vehicle id associated with fluid/chemical parts
     * $brand_id int - The manufacturer id that can be used to narrow the search
     * 
     * return array - products according to the filter
    */
    public static function getProductsFromVehicleID($vehicle_id, $combinations = false, $fullProduct = false, ProductSearchQuery $query = null, $lang_id = null, $universal = false, $fluids = false, $brand_id = 0)
    {
        if(!isset($vehicle_id) || !is_numeric($vehicle_id))
            return [];
        
        if($lang_id < 0 || !is_numeric($lang_id) || $lang_id == null)
            $lang_id = (int)$this->context->language->id;
        
        $universal_vehicle_id = 0;
        if($universal)
            $universal_vehicle_id = self::getUniversalVehicleID();
        
        $fluids_vehicle_id = 0;
        if($fluids)
            $fluids_vehicle_id = self::getFluidsVehicleID();
        
        //SELECT * FROM ps_ca_vehicle_fitment WHERE id_vehicle = 26788 GROUP BY id_product;
        $col_product_lang = ['pl.id_shop', 'pl.id_lang', 'pl.description', 'pl.description_short', 'pl.link_rewrite', 'pl.meta_description', 'pl.meta_keywords', 'pl.meta_title', 'pl.name', 'pl.available_now', 'pl.available_later', 'pl.delivery_in_stock', 'pl.delivery_out_stock' ];
        
        $sql = 'SELECT vf.id_product'.($combinations ? ', vf.id_product_attribute, vf.id_vehicle, vf.id_fitment' : '')
        .($fullProduct ? ', p.*, '.implode(',', $col_product_lang) : '')
        .' FROM `' . _DB_PREFIX_ . 'vehicle_fitment` AS vf '
        .($fullProduct ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product` AS p ON vf.id_product = p.id_product'
        .' LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` AS pl ON vf.id_product = pl.id_product AND pl.id_lang = '.$lang_id.' ' : '')
        .'WHERE (vf.id_vehicle = '.$vehicle_id.($universal && $universal_vehicle_id > 0 ? ' OR vf.id_vehicle = '.$universal_vehicle_id : '').($fluids && $fluids_vehicle_id > 0 ? ' OR vf.id_vehicle = '.$fluids_vehicle_id : '').')'.(is_numeric($brand_id) && $brand_id > 0 ? ' AND p.id_manufacturer = '.$brand_id : '').($combinations ? '' : ' GROUP BY vf.id_product');
        
        $order_by = null;
        if($query != null)
        {
            $current_page = $query->getPage();
            $results_per_page = $query->getResultsPerPage();
            
            $order_by = $query->getSortOrder()->toLegacyOrderBy();
            $order_by = !Validate::isOrderBy($order_by) || $order_by == null ? 'name' : $order_by;
            
            $order_way = $query->getSortOrder()->toLegacyOrderWay();
            $order_way = !Validate::isOrderWay($order_way) || $order_way == null ? 'ASC' : $order_way;
            
            if($order_by == 'name' || $order_by == 'position')
                $sql .= ' ORDER BY pl.name '.$order_way;
            
            $sql .= ' LIMIT '.(int)(($current_page - 1) * $results_per_page) . ', '. (int)$results_per_page;
            
        }
        //dbg::m('search query: '.$sql);
        $result = Db::getInstance()->executeS($sql);
        
        if($query != null && $order_by == 'price')
            Tools::orderbyPrice($result, $order_way);
   
        return $result;
    }
    /*
     * Retrieves all vehicle fitment for a particular product/product combination, can be filtered by a single vehicle id
     *
     * $id_product int - The particular product to retrieve fitment data for
     * $id_attribute int - Whether we are targeting a profuct combination
     * $id_vehicle int - Whether we want to only check if 1 vehcile_id fits
     * 
     * return array - The list of vehicle associated with this product
    */
    public static function getProductFitment($id_product, $id_attribute = null, $id_vehicle = null)
    {
        if(!isset($id_product))
            return [];
        /*
            SELECT vf.id_fitment, vf.id_product, vf.id_product_attribute, vf.id_vehicle, pac.id_attribute 
            FROM ps_ca_vehicle_fitment AS vf 
            LEFT JOIN ps_ca_product_attribute_combination AS pac ON vf.id_product_attribute = pac.id_product_attribute 
            WHERE vf.id_product = 29178 AND pac.id_attribute = 22370;
        */
        $sql = 'SELECT vf.id_fitment, vf.id_product, vf.id_product_attribute, vf.id_vehicle, pac.id_attribute'
            .' FROM `' . _DB_PREFIX_ . 'vehicle_fitment` AS vf'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` AS pac ON vf.id_product_attribute = pac.id_product_attribute'
            .' WHERE vf.id_product = '.$id_product
            .($id_attribute == null ? '' : ' AND pac.id_attribute = '.$id_attribute)
            .(isset($id_vehicle) ? ' AND vf.id_vehicle = '.$id_vehicle : '');
        
        dbg::m('sget fitments ql query: '.$sql);
        
        $result = Db::getInstance()->executeS($sql);
   
        return $result;
    }
    /*
     * Retrieves all vehicle makes that have vehicle fitment for a particular product/product combination
     *
     * $id_product int - The particular product to retrieve fitment data for
     * $id_attribute int - Whether we are targeting a profuct combination
     * 
     * return array - The list of vehicle makes associated with this product
    */
    public static function getProductFitmentMakes($id_product, $id_attribute = null) 
    {
        if(!isset($id_product))
            return [];
        /*
        //used for generating the make dropdown list
        SELECT m.make_id, mk.name, COUNT(m.make_id) AS model_count, f.id_fitment, f.id_product, pac.id_attribute, f.id_product_attribute
        FROM ps_ca_vehicle_fitment AS f
        LEFT JOIN ps_ca_product_attribute_combination AS pac ON f.id_product_attribute = pac.id_product_attribute        
        LEFT JOIN ps_ca_vehicle AS v ON f.id_vehicle = v.vehicle_id         
        LEFT JOIN ps_ca_model AS m ON v.model_id = m.model_id
        LEFT JOIN ps_ca_make AS mk ON m.make_id = mk.make_id
        WHERE f.id_product = 29178 AND pac.id_attribute = 22369                
        GROUP BY m.make_id;
        */
        
        $sql = 'SELECT m.make_id, mk.name, COUNT(m.make_id) AS model_count'
            .' FROM `' . _DB_PREFIX_ . 'vehicle_fitment` AS f'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` AS pac ON f.id_product_attribute = pac.id_product_attribute'        
            .' LEFT JOIN `' . _DB_PREFIX_ . 'vehicle` AS v ON f.id_vehicle = v.vehicle_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'model` AS m ON v.model_id = m.model_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'make` AS mk ON m.make_id = mk.make_id'
            .' WHERE f.id_product = '.$id_product.($id_attribute == null ? '' : ' AND pac.id_attribute = '.$id_attribute)
            .' GROUP BY m.make_id';
        
        $result = Db::getInstance()->executeS($sql);
        
        return $result;
    }
    /*
     * Retrieves all vehicle fitment for a particular product/product combination that is of a certain make only
     *
     * $make_id int - The particular make of vehicles we are interested in
     * $id_product int - The particular product to retrieve fitment data for
     * $id_attribute int - Whether we are targeting a profuct combination
     * 
     * return array - The list of vehicle associated with this product
    */ 
    public static function getProductFitmentByMake($make_id, $id_product, $id_attribute = null)
    {
        if(!isset($id_product) || !isset($make_id))
            return [];
        
        /*
        call via ajax call after make dropdown is populated
        
        SELECT v.vehicle_id, m.name AS model_name, MIN(y.year) AS min, MAX(y.year) AS max, f.id_fitment, f.id_product, f.id_product_attribute, pac.id_attribute, v.id_year, v.model_id, m.make_id
        FROM ps_ca_vehicle_fitment AS f
        LEFT JOIN ps_ca_product_attribute_combination AS pac ON f.id_product_attribute = pac.id_product_attribute
        LEFT JOIN ps_ca_vehicle AS v ON f.id_vehicle = v.vehicle_id         
        LEFT JOIN ps_ca_model AS m ON v.model_id = m.model_id          
        LEFT JOIN ps_ca_year AS y ON v.id_year = y.id_year         
        WHERE f.id_product = 29178 AND pac.id_attribute = 22369 AND m.make_id = 70                
        GROUP BY v.model_id
        ORDER BY m.name ASC;
        */
                
        $sql = 'SELECT v.vehicle_id, m.name AS model_name, MIN(y.year) AS min, MAX(y.year) AS max, f.id_fitment, v.model_id'
            .' FROM `' . _DB_PREFIX_ . 'vehicle_fitment` AS f'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` AS pac ON f.id_product_attribute = pac.id_product_attribute'        
            .' LEFT JOIN `' . _DB_PREFIX_ . 'vehicle` AS v ON f.id_vehicle = v.vehicle_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'model` AS m ON v.model_id = m.model_id'
            .' LEFT JOIN `' . _DB_PREFIX_ . 'year` AS y ON v.id_year = y.id_year'
            .' WHERE f.id_product = '.$id_product.($id_attribute == null ? '' : ' AND pac.id_attribute = '.$id_attribute)
            .' AND m.make_id = '.$make_id
            .' GROUP BY m.model_id'
            .' ORDER BY m.name ASC';
        
        $result = Db::getInstance()->executeS($sql);
              dbg::m('sql: '.$sql);
        dbg::m('result: '.print_r($result, true));
        return $result;
    }
    
    
    public function hookDisplayProductAdditionalInfo($params)
    {   
    
        if (isset($params['product']))
        {
            $id_product = $params['product']->getId();
            $product = new Product($id_product);
            $attribute_id = null;
            
            if(isset($product) && $product->hasAttributes())
            {
                $combination = $params['product']->getAttributes();
                $combination = array_pop($combination);
                $attribute_id = $combination['id_attribute'];
            }
            
            //if user is logged in retrieve garage
            $productDetailsList = [];
            $prefered_vehicle = null;
            if($this->context->customer->isLogged())
            {
                $productDetailsList = self::GetCustomerGarage($this->context->customer->id, true);
                foreach($productDetailsList as $row)
                {
                    if($row['is_prefered'] == 1)
                    {
                        $prefered_vehicle = $row;
                        break;
                    }
                }
            }

            dbg::m('product id:'.$id_product.' combo id:'.$attribute_id);
            
            //Get Universal Vehicle
            $universal_vehicle = self::getUniversalVehicleID();
            $prodFitments = $universal_vehicle > 0 ? self::getProductFitment($id_product, $attribute_id, $universal_vehicle) : null;                
            //Check if its a fluid/chemical
            $fluid = false;
            if(empty($prodFitments) || $prodFitments == null)
            {
                $fluids_vehicle = self::getFluidsVehicleID();
                $prodFitments = $fluids_vehicle > 0 ? self::getProductFitment($id_product, $attribute_id, $fluids_vehicle) : null;
                $fluid = true;
            }

            //check if cookie is set
            $modelInfo = self::GetModelNameFromCookie();
            $c_vehicle_id = $this->context->cookie->__isset('vehicle_id') ? (int) filter_var($this->context->cookie->__get('vehicle_id')) : 0;


            if(!isset($prodFitments) || $prodFitments == null)
            {
                if($prefered_vehicle == null && $c_vehicle_id == 0 && $this->context->customer->isLogged())
                    $modelVerify = '<div id="fitment-prompt" class="promptBox text-center fitment-warning" '.($product->hasAttributes() && Configuration::get('SHOPLYNC_FITMENT_COMBINATION_HIDE') == 1 ? 'style="display:none; height:0;"' : '').'>'
                        .'<h3 class="text-capitalize"><i class="material-icons">help</i>Your Garage Is Empty</h3>'
                        .'<a href="'.$this->context->link->getModuleLink('shoplync_bike_model_filter', 'mygarage', array(), true).'" class="btn btn-primary modelFilterButton">Add A Model</a>'
                        .'<button type="button" class="btn btn-primary" onclick="document.getElementById(\'fitment-tab-link\').click();document.getElementById(\'fitment-tab-link\').scrollIntoView();">View All Fitments</button>'
                        .'</div>';
                        //{$link->getModuleLink('shoplync_bike_model_filter', 'mygarage', array(), true)|escape:'htmlall':'UTF-8'}
                        //$this->context->link->getModuleLink('shoplync_bike_model_filter', 'mygarage', array(), true)
                else if($c_vehicle_id > 0 || $prefered_vehicle != null)
                {
                    $vehicle_id = $c_vehicle_id > 0 ? $c_vehicle_id : $prefered_vehicle['vehicle_id'];
                    $prodFitments = self::getProductFitment($id_product, $attribute_id, $vehicle_id);
                    dbg::m('vehicle_id: '.$vehicle_id.' prefered_vehicle: '.print_r($prefered_vehicle, true));
                    dbg::m('fitments: '.print_r($prodFitments, true));
                    $fits = !empty($prodFitments);
                    
                    $vehicle_name = !empty($modelInfo) ? ': '.$modelInfo : ' your: '.$prefered_vehicle['vehicle_name'];
                    $model_name = !empty($modelInfo) ? $modelInfo : $prefered_vehicle['model_name'];
                    
                    $clearButton = '<button id="clearCookieBtn" type="button" class="btn btn-primary '.($this->context->customer->isLogged() ? 'hidden' : '').'" id="modelFilterButton" onclick="clearFilterCookie();location.reload();">Clear</button>';
                    $button = $this->context->customer->isLogged() ? 
                        '<button type="button" class="btn btn-primary" id="modelFilterButton" onclick="toggleClass(\'select#selectPrefered\',\'hidden\');toggleClass(\'p#current-model\',\'hidden\');toggleClass(\'#selectFromGarage\',\'hidden\');toggleClass(\'#fitment-prompt hr\',\'hidden\');toggleClass(\'#clearCookieBtn\',\'hidden\');">Change</button>'
                        : '';
                    
                    //$this->smarty->assign('product_fitments', $prodFitments);
                    //document.getElementById('fitment-tab-link').click()
                    $modelVerify = '<div id="fitment-prompt" class="promptBox text-center '.($fits ? 'verified' : 'not-verified').'" '.($product->hasAttributes() && Configuration::get('SHOPLYNC_FITMENT_COMBINATION_HIDE') == 1 ? 'style="display:none; height:0;"' : '').'>'
                        .'<h3 class="text-capitalize"><i class="material-icons">'.($fits ? 'check' : 'close').'</i> '.($fits ? 'This fits' : 'Does not fit').$vehicle_name.'</h3>'
                        .$button
                        .'<p id="current-model" class="font-italic mt-1 hidden">Current: '.$model_name.'</p>'
                        .'<hr class="hidden" /><h4 id="selectFromGarage" class="hidden">Select From Garage</h4>'
                        .($this->context->customer->isLogged() ? self::GenerateList($productDetailsList, "-", 'mb-1 hidden', 'selectPrefered', true, 'preferedChanged(this)') : '')
                        .(!empty($modelInfo) ? $clearButton : '')
                        .'</div>';
                }
                else
                    $modelVerify = '<div id="fitment-prompt" class="promptBox text-center fitment-prompt" '.($product->hasAttributes() && Configuration::get('SHOPLYNC_FITMENT_COMBINATION_HIDE') == 1 ? 'style="display:none; height:0;"' : '').'>'
                        .'<h3 class="text-capitalize"><i class="material-icons">info</i>No Bike Model Selected</h3>'
                        .'<button type="button" class="btn btn-primary" onclick="document.getElementById(\'fitment-tab-link\').click();document.getElementById(\'fitment-tab-link\').scrollIntoView();">View All Fitments</button>'
                        .'</div>';
            }
            else 
            {
                $message = $fluid ? 'No Fitment Available For Oil & Chemicals' : 'This is a universal part & may require custom fitment';
                $modelVerify = '<div id="fitment-prompt" class="promptBox text-center fitment-info" '.($product->hasAttributes() && Configuration::get('SHOPLYNC_FITMENT_COMBINATION_HIDE') == 1 ? 'style="display:none; height:0;"' : '').'>'
                        .'<h3 class="text-capitalize"><i class="material-icons">error</i> This is a universal part & may require custom fitment</h3>'
                        .'</div>';
            }

            
            //Populates the make dropdown in the fitments tabs
            $fitmentMakes = self::getProductFitmentMakes($id_product, $attribute_id);
            
            $script_tag = '<script>window.addEventListener(\'DOMContentLoaded\', function() { var makeDropdown = document.getElementById(\'selectFitmentMake\'); '
            .'console.log(makeDropdown); '
            .'removeOptions(makeDropdown, 1); '
            .'resetFitmentMake(); ';
            
            if(!empty($fitmentMakes))
            {
                foreach($fitmentMakes as $row)
                {
                    $row['name'] = $row['name'].' ('.$row['model_count'].')';
                }
                
                $options = self::GenerateList($fitmentMakes, "Select A Make", '', '', true, '', true);
                $script_tag .= 'window.ps_product_id = '.$id_product.'; '
                    .'window.ps_attribute_id = '.($attribute_id ?? 'null').'; '
                    .'var options = \''.$options.'\'; '
                    .'if(makeDropdown){ makeDropdown.innerHTML = options; makeDropdown.disabled = false;} ';                
            }
            else
                $script_tag .= 'makeDropdown.disabled = true; ';
            
            $script_tag .= '} );</script>';
            
            
            return $modelVerify.$script_tag;
        }
    }
    
    public function hookDisplayProductTab()
    {
        return '<li class="nav-item">'
        .'<a class="nav-link" data-toggle="tab" href="#fitment" role="tab" id="fitment-tab-link" aria-controls="fitment" aria-expanded="false">Fitment</a></li>';
    }

    public function hookDisplayProductTabContent()
    {
        /* 
        29178 |               596573 
        use similar code but by searching up vehicle_fitment WHERE product_id && product_attribute Then join with this code (left join vehicle table) then the rest->
        */
        
        $this->smarty->assign('makeList', self::GenerateList([], "Select A Make", "", "selectFitmentMake", false, 'changeFitmentMake(this)') );
        return $this->display(__FILE__, 'views/templates/front/fitment-tab.tpl');
    }
    
    public function hookAddWebserviceResources()
    {
        return array(
            'vehicle_makes' => array('description' => 'Manage Vehicle Makes', 'class' => 'VehicleMake'),
            'vehicle_models' => array('description' => 'Manage Vehicle Models', 'class' => 'VehicleModel'),
            'vehicle_years' => array('description' => 'Manage Vehicle Years', 'class' => 'VehicleYear'),
            'vehicles' => array('description' => 'Manage Vehicles', 'class' => 'Vehicle'),
            'vehicle_types' => array('description' => 'Manage Vehicle Types', 'class' => 'VehicleType'),
            'VehicleUpload' => array('description' => 'Manage Vehicle Fitment', 'specific_management' => true),
        );
     
    }
    
    /**
     * In this hook we intercept the request for new products to add facets
     * @param $params
     * @return FitmentFilterSearchProvider
     */
    public function hookProductSearchProvider($params)
    {
        /*
        $query = $params['query'];
        if ($query->getQueryType() == 'new-products'
        || $query->getQueryType() == 'search'|| $query->getQueryType() == 'fitment') {
            return new FitmentFilterSearchProvider($this, $this->getTranslator(), []);
        }
        */
    }
    
    public function hookModuleRoutes($params)
    {
        /*
        only allow to either set 
        make/model/year -
        brand + make/model/year -
        brand + type -
        type  + make/model/year -
        brand/type + make/model/year -
        
        category + everything above?
        */
        
        $regex_pattern = '[_a-zA-Z0-9\pL\pS/.:+-]*';
        return array(
            //Will Allow Users to Specify Make-Model-Year (With/Without friendly name)
            // example.ca/2022-suzuki-gsxr1000/125-6810-68/
            'module-shoplync_bike_model_filter-MMY' => array(
                'controller' => 'fitmentsearch',
                'rule' => 'fitment/{yearMakeModelName}/{selectMake}-{selectModel}-{selectYear}/{rewrite}',
                'keywords' => array(
                    'selectMake' => array('regexp' => '[0-9]+', 'param' => 'selectMake'),
                    'selectModel' => array('regexp' => '[0-9]+', 'param' => 'selectModel'),
                    'selectYear' => array('regexp' => '[0-9]+', 'param' => 'selectYear'),
                    //Optional Args
                    'yearMakeModelName' => array('regexp' => $regex_pattern),
                    'rewrite' => array('regexp' => $regex_pattern),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'shoplync_bike_model_filter',
                    //'controller' => 'fitmentsearch',
                )
            ),
            //Will Allow Users to Specify Brand-Make-Model-Year (With/Without friendly name)
            // example.ca/arrow/2022-suzuki-gsxr1000/12-125-6810-68/
            'module-shoplync_bike_model_filter-BMMY' => array(
                'controller' => 'fitmentsearch',
                'rule' => 'fitment/{brandName}/{yearMakeModelName}/{selectBrand}-{selectMake}-{selectModel}-{selectYear}/{rewrite}',
                'keywords' => array(
                    'selectBrand' => array('regexp' => '[0-9]+', 'param' => 'selectBrand'),
                    'selectMake' => array('regexp' => '[0-9]+', 'param' => 'selectMake'),
                    'selectModel' => array('regexp' => '[0-9]+', 'param' => 'selectModel'),
                    'selectYear' => array('regexp' => '[0-9]+', 'param' => 'selectYear'),
                    //Optional Args
                    'brandName' => array('regexp' => $regex_pattern),
                    'yearMakeModelName' => array('regexp' => $regex_pattern),
                    'rewrite' => array('regexp' => $regex_pattern),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'shoplync_bike_model_filter',
                    //'controller' => 'fitmentsearch',
                )
            ),
            //Will Allow Users to Specify Brand-Type
            // example.ca/arrow/street/125-6810/
            'module-shoplync_bike_model_filter-BT' => array(
                'controller' => 'fitmentsearch',
                'rule' => 'fitment/{brandName}/{typeName}/{selectBrand}-{selectType}/{rewrite}',
                'keywords' => array(
                    'selectBrand' => array('regexp' => '[0-9]+', 'param' => 'selectBrand'),
                    'selectType' => array('regexp' => '[0-9]+', 'param' => 'selectType'),
                    //Optional Args
                    'brandName' => array('regexp' => $regex_pattern),
                    'typeName' => array('regexp' => $regex_pattern),
                    'rewrite' => array('regexp' => $regex_pattern),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'shoplync_bike_model_filter',
                    //'controller' => 'fitmentsearch',
                )
            ),
            //Will Allow Users to Specify Brand-Type-Make-Model-Year
            // example.ca/arrow/street/2022-suzuki-gsxr1000/11-22-125-6810-68/
            'module-shoplync_bike_model_filter-BTMMY' => array(
                'controller' => 'fitmentsearch',
                'rule' => 'fitment/{brandName}/{typeName}/{yearMakeModelName}/{selectBrand}-{selectType}-{selectMake}-{selectModel}-{selectYear}/{rewrite}',
                'keywords' => array(
                    'selectBrand' => array('regexp' => '[0-9]+', 'param' => 'selectBrand'),
                    'selectType' => array('regexp' => '[0-9]+', 'param' => 'selectType'),
                    'selectMake' => array('regexp' => '[0-9]+', 'param' => 'selectMake'),
                    'selectModel' => array('regexp' => '[0-9]+', 'param' => 'selectModel'),
                    'selectYear' => array('regexp' => '[0-9]+', 'param' => 'selectYear'),
                    //Optional Args
                    'brandName' => array('regexp' => $regex_pattern),
                    'typeName' => array('regexp' => $regex_pattern),
                    'yearMakeModelName' => array('regexp' => $regex_pattern),
                    'rewrite' => array('regexp' => $regex_pattern),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'shoplync_bike_model_filter',
                    //'controller' => 'fitmentsearch',
                )
            ),
            //Will Mask Generic Module URL
            'module-shoplync_bike_model_filter-fitmentsearch' => array(
                'controller' => 'fitmentsearch',
                'rule' => 'fitment/{rewrite}',
                'keywords' => array(
                    'rewrite' => array('regexp' => $regex_pattern),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'shoplync_bike_model_filter',
                    //'controller' => 'fitmentsearch',
                )
            ),
            'layered_rule' => array(
                'controller' =>    '404',
                'rule' => 'disablethisrule-'.uniqid(),
                'keywords' => array(),
                'params' => array()
            ),
        );
    }
    /*
    * Will generate a url friendly string
    *
    * $name string - The string to be cleansed
    *
    * return string - The cleansed string
    */
    public static function GeneratePrettyUrl($name = '')
    {
        if($name == '' || $name == null)
            return '';
        
        $pattern = '[^\d\w\-_]';
        //remove whitespace and slashes, to lower case
        $parsed_name = str_replace([' ', '/', '---', '--'], '-', trim(strtolower($name)));
        //remove non words/digits
        return preg_replace($pattern, '', $parsed_name);
    }
    
    /*
    * Forked from https://stackoverflow.com/a/42444621
    *
    * Will resize/overwrite an image larger than the supplied $maxwidth
    *
    * $file string - The local path to the file
    * $maxwidth int - The max width that images will be resized down to
    *
    * return boolean - whether resizing was succesful or not
    */
    public static function resizeImage($file = null, $maxwidth = 1000){
        
        if($file == null)
            return false;
        
        $image_info = getimagesize($file);
        $image_width = $image_info[0];
        $image_height = $image_info[1];
        $ratio = $image_width / $maxwidth;
        $info = getimagesize($file);
        if ($image_width > $maxwidth) {
            // GoGoGo
            $newwidth = $maxwidth;
            $newheight = (int)($image_height / $ratio);
            
            if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') {    
                $thumb = imagecreatetruecolor($newwidth, $newheight);
                $source = imagecreatefromjpeg($file);
                imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $image_width, $image_height);
                
                return imagejpeg($thumb,$file,90);
            }   
            if ($info['mime'] == 'image/png') {
                $im = imagecreatefrompng($file);
                $im_dest = imagecreatetruecolor($newwidth, $newheight);
                imagealphablending($im_dest, false);
                imagecopyresampled($im_dest, $im, 0, 0, 0, 0, $newwidth, $newheight, $image_width, $image_height);
                imagesavealpha($im_dest, true);
                
                return imagepng($im_dest, $file, 9);
            }
            if ($info['mime'] == 'image/gif') {
                $im = imagecreatefromgif($file);
                $im_dest = imagecreatetruecolor($newwidth, $newheight);
                imagealphablending($im_dest, false);
                imagecopyresampled($im_dest, $im, 0, 0, 0, 0, $newwidth, $newheight, $image_width, $image_height);
                imagesavealpha($im_dest, true);
                
                return imagegif($im_dest, $file);
            }
            
            return false;
        }
        
        return true;
    }
    /*
    * Deletes the specified image from the disk
    *
    * $filePath string - The local path to the file
    *
    * return boolean - whether resizing was succesful or not
    */
    public static function deleteImage($filePath = null)
    {
        if($filePath == null)
            return false;
        //_PS_CORE_DIR_
        $path_to_save = __PS_BASE_URI__.'modules/'.$this->module->name.'/users-garage/';
        
        if(file_exists(_PS_CORE_DIR_.$filePath) && is_file (_PS_CORE_DIR_.$filePath))
        {
            return unlink(_PS_CORE_DIR_.$filePath);
        }
        return false;
    }
}
