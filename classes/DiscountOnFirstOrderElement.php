<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information. 
 *
 * @author    luca.ioffredo93@gmail.com
 * @copyright 2017 luca.ioffredo93@gmail.com 
 * @license   luca.ioffredo93@gmail.com
 * @category  PrestaShop Module 
 * Description
 *
 *  
 */

require_once (dirname(__FILE__) . '/../discountonfirstorder.php');
class DiscountOnFirstOrderElement extends ObjectModel {
	
	public $id_discountonfirstorder; 
	public $id_customer; 
	public $state; 
	public $date_add;
	
	public static $definition = array( 
            'table' => 'discountonfirstorder',
            'primary' => 'id_discountonfirstorder',
            'multilang' => false,
            'fields' => array(
                'id_discountonfirstorder' => array(
                    'type' => ObjectModel::TYPE_INT				
                ), 
                'id_customer' => array(
                    'type' => ObjectModel::TYPE_INT,
                    'required' => true
                ), 
                'state' => array(
                    'type' => ObjectModel::TYPE_INT,
                    'required' => true
                ), 
                'date_add' => array(
                    'type' => ObjectModel::TYPE_DATE
                )
            )
	);
	 
	public function __construct($id = null, $id_lang = null, $id_shop = null)
	{
            parent::__construct($id,$id_lang,$id_shop);	
	
	}
	
	public static function getHistory()
	{ 
            $sql  = 'SELECT * FROM `'._DB_PREFIX_.'discountonfirstorder` ORDER BY id_discountonfirstorder DESC';
            return (Db::getInstance()->executeS($sql)); 
	}
        
        public static function getByIdCustomer($id_customer) {
            $sql  = 'SELECT * FROM `'._DB_PREFIX_.'discountonfirstorder` WHERE state=1 AND id_customer='.$id_customer;
            return (Db::getInstance()->executeS($sql)); 
        }
	
}
