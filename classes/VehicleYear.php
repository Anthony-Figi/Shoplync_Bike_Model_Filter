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
class VehicleYear extends ObjectModel {
    
    /** @var bool Enables to define an ID before adding object. */
    public $force_id = true;
    
    /** @var string Document reference */ 
    public $id_sms;

 
    /** @var string name */ 
    public $year;
 
        /**
     * Adds current object to the database.
     *
     * @param bool $auto_date
     * @param bool $null_values
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        error_log("hello".$this->id_sms);
        if(isset($this->id_sms) && !is_null($this->id_sms))
            $this->id = (int)$this->id_sms;

        return parent::add($auto_date, $null_values);
    }
 
 
    /**
     * Definition of class parameters
     */ 
    public static $definition = array( 
        'table' => 'year', 
        'primary' => 'id_year', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array( 
            'year' => array('type' => self::TYPE_INT), 
            'id_sms' => array('type' => self::TYPE_INT), 
        ), 
    );

    /*
    public function hookActionObjectVehicleYearAddAfter(Product $product)
    {
        PrestaShopLogger::addLog(
            sprintf('Product with id %s was deleted with success', $product->id_product)
        );    
    }
    */
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'vehicle_years',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'vehicle_year',  // Detail of an entity 
        'fields' => [] 
    ]; 
}