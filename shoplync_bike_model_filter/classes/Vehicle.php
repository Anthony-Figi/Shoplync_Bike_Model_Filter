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
class Vehicle extends ObjectModel {
 
    /** @var int vehicle ID */ 
    public $vehicle_id;
 
    /** @var int model ID */ 
    public $model_id;
 
    /** @var int year ID */ 
    public $year_id;
 
    /**
     * Definition of class parameters
     */ 
    public static $definition = array( 
        'table' => 'vehicle', 
        'primary' => 'vehicle_id', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array( 
            'vehicle_id' => array('type' => self::TYPE_INT), 
            'model_id' => array('type' => self::TYPE_INT), 
            'year_id' => array('type' => self::TYPE_INT), 
        ), 
    );
 
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'vehicles',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'vehicle',  // Detail of an entity 
        'fields' => [] 
    ]; 
}