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
class VehicleMake extends ObjectModel {
 
    /** @var int make ID */ 
    public $make_id;
 
    /** @var string name */ 
    public $name;
 
 
    /**
     * Definition of class parameters
     */ 
    public static $definition = array( 
        'table' => 'make', 
        'primary' => 'make_id', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array(
            'make_id' => array('type' => self::TYPE_INT),         
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
        ), 
    );
 
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'vehicle_makes',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'vehicle_make',  // Detail of an entity 
        'fields' => [] 
    ]; 
}