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



$sql = array();

$sql[0] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shoplync_bike_model_filter` (
    `id_shoplync_bike_model_filter` int(11) NOT NULL AUTO_INCREMENT,
    PRIMARY KEY  (`id_shoplync_bike_model_filter`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

//Vehicle Create Tables Code
$sql[1] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'type` (
    `id_type` int(11) NOT NULL,
    `id_sms` int(11) NOT NULL,
    `parent_id_type` int(11),
    `name` varchar(255) NOT NULL,
    `depth` int(11) NOT NULL,
    `is_visible` boolean DEFAULT TRUE,
    UNIQUE (`name`),
    PRIMARY KEY (`id_type`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[2] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'year` (
    `id_year` int(11) NOT NULL,
    `id_sms` int(11) NOT NULL,
    `year` int(11) NOT NULL,
    UNIQUE (`year`),
    PRIMARY KEY (`id_year`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[3] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'make` (
    `make_id` int(11) NOT NULL,
    `id_sms` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `is_visible` boolean DEFAULT TRUE,
    UNIQUE (`name`),
    PRIMARY KEY (`make_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[4] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'model` (
    `model_id` int(11) NOT NULL,
    `id_sms` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `make_id` int(11) NOT NULL,
    `id_type` int(11),
    PRIMARY KEY (`model_id`),
    FOREIGN KEY (`make_id`) REFERENCES ' . _DB_PREFIX_ . 'make(`make_id`) ON DELETE NO ACTION,
    FOREIGN KEY (`id_type`) REFERENCES ' . _DB_PREFIX_ . 'type(`id_type`) ON DELETE NO ACTION
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[5] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vehicle` (
    `vehicle_id` int(11) NOT NULL,
    `id_sms` int(11) NOT NULL,
    `model_id` int(11) NOT NULL,
    `id_year` int(11) NOT NULL,
    PRIMARY KEY  (`vehicle_id`),
    FOREIGN KEY (`model_id`) REFERENCES ' . _DB_PREFIX_ . 'model(`model_id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_year`) REFERENCES ' . _DB_PREFIX_ . 'year(`id_year`) ON DELETE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

//Table to store customers garage
$sql[6] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'garage` (
    `garage_id` int(11) NOT NULL AUTO_INCREMENT,
    `vehicle_id` int(11) NOT NULL,
    `customer_id` int(10) unsigned NOT NULL,
    `vehicle_name` varchar(255),
    `image_path` varchar(255),
    PRIMARY KEY (`garage_id`),
    UNIQUE KEY `unique_vehicle_name` (vehicle_id, customer_id, vehicle_name),
    FOREIGN KEY (`vehicle_id`) REFERENCES ' . _DB_PREFIX_ . 'vehicle(`vehicle_id`),
    FOREIGN KEY (`customer_id`) REFERENCES ' . _DB_PREFIX_ . 'customer(`id_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[7] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'prefered_vehicle` (
    `prefered_vehicle_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(10) unsigned NOT NULL,
    `garage_id` int(11) NOT NULL,
    PRIMARY KEY (`prefered_vehicle_id`),
    UNIQUE (`customer_id`),
    FOREIGN KEY (`garage_id`) REFERENCES ' . _DB_PREFIX_ . 'garage(`garage_id`),
    FOREIGN KEY (`customer_id`) REFERENCES ' . _DB_PREFIX_ . 'customer(`id_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[8] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vehicle_fitment` (
    `id_fitment` int(11) NOT NULL AUTO_INCREMENT,
    `id_product` int(10) unsigned NOT NULL,
    `id_product_attribute` int(10) unsigned,
    `id_vehicle` int(11) NOT NULL,
    PRIMARY KEY (`id_fitment`),
    UNIQUE KEY `unique_fitment` (`id_product`, `id_product_attribute`, `id_vehicle`),
    FOREIGN KEY (`id_product`) REFERENCES ' . _DB_PREFIX_ . 'product(`id_product`),
    FOREIGN KEY (`id_product_attribute`) REFERENCES ' . _DB_PREFIX_ . 'product_attribute(`id_product_attribute`),
    FOREIGN KEY (`id_vehicle`) REFERENCES ' . _DB_PREFIX_ . 'vehicle(`vehicle_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


$sql[9] = 'CREATE TRIGGER IF NOT EXISTS `' . _DB_PREFIX_ . 'unique_garage_names` BEFORE INSERT ON `' . _DB_PREFIX_ . 'garage`
  FOR EACH ROW BEGIN
     declare original_vehicle_name varchar(255);
     declare name_counter int;
     set original_vehicle_name = new.vehicle_name;
     set name_counter = 1;
     while exists (select true from `' . _DB_PREFIX_ . 'garage` where vehicle_name = new.vehicle_name) do
        set new.vehicle_name = concat(original_vehicle_name, "-", name_counter); 
        set name_counter = name_counter + 1;
     end while;
  END;';

/*
$sql[9] = 'delimiter | 
CREATE TRIGGER IF NOT EXISTS `' . _DB_PREFIX_ . 'unique_garage_names` BEFORE INSERT ON `' . _DB_PREFIX_ . 'garage`
  FOR EACH ROW BEGIN
     declare original_vehicle_name varchar(255);
     declare name_counter int;
     set original_vehicle_name = new.vehicle_name;
     set name_counter = 1;
     while exists (select true from `' . _DB_PREFIX_ . 'garage` where vehicle_name = new.vehicle_name) do
        set new.vehicle_name = concat(original_vehicle_name, '-', name_counter); 
        set name_counter = name_counter + 1;
     end while;
  END;|
  delimiter ;';

delimiter |
CREATE TRIGGER IF NOT EXISTS unique_garage_names BEFORE INSERT ON `ps_ca_garage`
FOR EACH ROW BEGIN
    declare original_vehicle_name varchar(255);
    declare name_counter int;
    set original_vehicle_name = new.vehicle_name;
    set name_counter = 1;
    while exists (select true from `ps_ca_garage` where vehicle_name = new.vehicle_name) do
    set new.vehicle_name = concat(original_vehicle_name, '-', name_counter); 
    set name_counter = name_counter + 1;
    end while;
END;|
delimiter ;


$sql[8] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vehicle_fitment` (
    `fitment_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `product_attribute_id` int(11) DEFAULT 0,
    `vehicle_id` int(11) NOT NULL,
    PRIMARY KEY (`fitment_id`),
    FOREIGN KEY (`vehicle_id`) REFERENCES vehicle(`vehicle_id`),
    FOREIGN KEY (`product_id`) REFERENCES ' . _DB_PREFIX_ . 'product(`id_product`),
    FOREIGN KEY (`product_attribute_id`) REFERENCES ' . _DB_PREFIX_ . 'product_attribute(`id_product_attribute`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[9] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vehicle_image` (
    `image_id` int(11) NOT NULL,
    `garage_id` int(11) NOT NULL,
    `image` BLOB NOT NULL,
    PRIMARY KEY (`image_id`),
    FOREIGN KEY (`garage_id`) REFERENCES garage(`garage_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

*/

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        error_log("Module Init: SQL Query Failed");
        return false;
    }
}
