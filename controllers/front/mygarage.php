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

/**
 * Class itself
 */
class shoplync_bike_model_filtermygarageModuleFrontController extends ModuleFrontController
{
    /**
     * For allow SSL URL
     */
    public $ssl = true;

    /**
     * String Internal controller name
     */
    public $php_self = 'mygarage';

    public $productList = null;
    public $productDetailsList = array();
    public $productRejectList = array();

    /**
     * Sets default medias for this controller
     */
    public function setMedia()
    {
        /**
         * Set media
         */
        parent::setMedia();
        
        $this->addCSS($this->module->getLocalPath().'/views/css/front.css');
        $this->addJS($this->module->getLocalPath().'/views/js/front.js');
       
    }

    /**
     * Redirects to canonical or "Not Found" URL
     *
     * !!!! There was not parameter which generated a "strict standards" warning
     *
     * @param string $canonical_url
     */
    public function canonicalRedirection($canonical_url = '')
    {
        //parameter added to function
        $canonical_url=null;
        if (Tools::getValue('live_edit')) {
            return $canonical_url;
        }
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
        $this->page_name = 'mygarage';

        $this->display_column_left = false;
        $this->display_column_right = false;

        parent::init();
    }

    /**
     * Initializes page content variables
     */
    public function initContent()
    {
        parent::initContent();

        if ($this->context->customer->isLogged()) {
            $this->context->smarty->assign('module_dir', __PS_BASE_URI__.'modules/'.$this->module->name);
            $this->context->smarty->assign('link_token', Tools::getToken(false));
            
            $this->context->smarty->assign('makeList', Shoplync_bike_model_filter::GenerateMakeList());
                       
            $this->setTemplate('module:shoplync_bike_model_filter/views/templates/front/my-garage.tpl');
            Media::addJsDef([
                'default_vehicle_image' => '',
                'new_entry_image' => '',
                'new_entry_actions' => ''
            ]);
            
            $productDetailsList = Shoplync_bike_model_filter::GetCustomerGarage($this->context->customer->id);
            if(is_array($productDetailsList) && !empty($productDetailsList))
            {
                $this->context->smarty->assign('productDetailsList', $productDetailsList);
                foreach($productDetailsList as $row)
                {
                    if($row['is_prefered'] == 1)
                    {
                        $this->context->smarty->assign('preferedBikeModel', $row);
                        break;
                    }
                }
            }
            
        } else {
            Tools::redirect('index.php');
        }
    }

    /**
     * Save form data.
     */
    public function postProcess()
    {
        return parent::postProcess(); 
    }
}
