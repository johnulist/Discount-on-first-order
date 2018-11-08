<?php

/**
 * @author     Luca Ioffredo
 * @copyright  Luca Ioffredo
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$require = array(
    'classes/DiscountOnFirstOrderElement.php',
);

foreach ($require as $item) {
    require_once(_PS_MODULE_DIR_ . 'discountonfirstorder/' . $item);
}

class Discountonfirstorder extends Module {

    const CART_RULE_PERCENT = 1;
    const CART_RULE_AMOUNT = 2;
    const CART_RULE_FREE_SHIPPING = 3;
    const PREFIX_DISCOUNT = 'FRSORD';
    const DISCOUNT_AMOUNT = 10; //5 percento o euro
    const APPLIED_AUTO = true; //applica automaticamente

    protected $config_form = false;

    public function __construct() {
        $this->name = 'discountonfirstorder';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Luca Ioffredo';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Discount On First Order');
        $this->description = $this->l('Add a voucher to Shopping Cart on First Order. Created by Luca Ioffredo, alias Latios93');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install() {
        return parent::install() && $this->installDB() && $this->registerHook($this->getHooks());
    }

    public function uninstall() {
        return parent::uninstall() && $this->deleteDB() && $this->uninstallHooks();
    }

    public function getHooks() {
        return array(
            'header',
            'displayHome', // Displayed on the content of the home page.
            'actionCustomerAccountAdd', // When a new customer creates an account successfully
        );
    }

    private function uninstallHooks() {
        $res = true;
        foreach ($this->getHooks() as $hook) {
            $res = $res && $this->unregisterHook($hook);
        }
        return $res;
    }

    public function installDB() {
        Db::getInstance()->execute('
		CREATE TABLE `' . _DB_PREFIX_ . 'discountonfirstorder` (
                    `id_discountonfirstorder` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_customer` INT(10) unsigned NOT NULL,
                    `state` INT(2) unsigned DEFAULT 0,
                    `date_add` DATETIME NOT NULL,
                    PRIMARY KEY (`id_discountonfirstorder`)
		) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=utf8; ');
        return true;
    }

    public function deleteDB() {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'discountonfirstorder`');
    }

    public function hookHeader($params) {
        // On every pages
        if (self::getVersion() != "1.6") {
            $this->context->controller->registerStylesheet(
                    'discountonfirstorder-css', 'modules/' . $this->name . '/views/css/front/discountonfirstorder.css'
            );
            $this->context->controller->registerJavascript(
                    'discountonfirstorder-js', 'modules/' . $this->name . '/views/js/front/discountonfirstorder.js', array(
                'position' => 'bottom',
                'inline' => false,
                'priority' => 1000,
                    )
            );
        } else {
            $this->context->controller->addCSS(($this->_path) . '/views/css/front/discountonfirstorder.css', 'all');
            $this->context->controller->addJS(($this->_path) . '/views/js/front/discountonfirstorder.js');
        }
    }

    public static function getVersion() {
        return Tools::substr(_PS_VERSION_, 0, 3);
    }

    public function hookActionCustomerAccountAdd($params) {
        if (Validate::isLoadedObject($params['newCustomer'])) {
            $this->createCartRule($params['newCustomer']);
            $d = new DiscountOnFirstOrderElement();
            $d->id_customer = $params['newCustomer']->id;
            $d->state = 1;
            $d->save();
        }
    }

    public function hookDisplayHome($params) {
        if (Validate::isLoadedObject($this->context->customer) && $this->context->customer->isLogged()) {
            $d = DiscountOnFirstOrderElement::getByIdCustomer($this->context->customer->id);
            if ($d && $d[0]['state'] == 1) {
                $d = new DiscountOnFirstOrderElement($d[0]['id_discountonfirstorder']);
                $d->state = 2;
                $d->save();
                return $this->display(__FILE__, 'custompopup.tpl');
            }
        }
    }

    public function createCartRule($user, $sendEmail = false) {
        if (!Validate::isLoadedObject($user)) {
            return false;
        }
        $voucher = new CartRule();
        $voucher->id_customer = (int) ($user->id);
        $discount_amount = (Configuration::hasKey('DISCOUNTONFIRSTORDER_DISCOUNT_AMOUNT')) ? (int) Configuration::get('DISCOUNTONFIRSTORDER_DISCOUNT_AMOUNT') : (int) Discountonfirstorder::DISCOUNT_AMOUNT;
        $voucher->id_discount_type = (Configuration::hasKey('DISCOUNTONFIRSTORDER_DISCOUNT_TYPE')) ? (int) Configuration::get('DISCOUNTONFIRSTORDER_DISCOUNT_TYPE') : (int) Discountonfirstorder::CART_RULE_PERCENT;
        $cart_rule_name = $this->l('Coupon first Order ') . $discount_amount . '% - Ref: ' . (int) ($voucher->id_customer) . ' - ' . date('Y');
        array('1' => $cart_rule_name, '2' => $cart_rule_name);
        $languages = Language::getLanguages();
        $array_name = array();
        foreach ($languages as $language) {
            $array_name[$language['id_lang']] = $cart_rule_name;
        }
        $voucher->name = $array_name;
        $voucher->description = $this->l('Coupon first order!');
        $voucher->id_currency = Configuration::get('PS_CURRENCY_DEFAULT'); /* Old */
        $voucher->quantity = 1;
        $voucher->quantity_per_user = 1;
        $voucher->reduction_tax = 1; // tasse incluse
        $voucher->partial_use = false;
        $voucher->product_restriction = false;
        $voucher->cart_rule_restriction = true; /* Opposto di cumulable */
        $voucher->date_from = date('Y-m-d');
        $voucher->date_to = strftime('%Y-%m-%d', strtotime('+2 year'));
        $voucher->minimum_amount = 0;
        $voucher->active = true;
        switch ((int) $voucher->id_discount_type) {
            case Discountonfirstorder::CART_RULE_FREE_SHIPPING:
                $voucher->free_shipping = true;
                break;
            case Discountonfirstorder::CART_RULE_PERCENT:
                $voucher->reduction_percent = $discount_amount;
                break;
            case Discountonfirstorder::CART_RULE_AMOUNT:
                $voucher->reduction_amount = $discount_amount;
                break;
        }
        $email_data = array('{firstname}' => $user->firstname, '{lastname}' => $user->lastname, '{coupon_amount}' => $discount_amount);
        $autoapply = (Configuration::hasKey('DISCOUNTONFIRSTORDER_DISCOUNT_AUTOAPPLY')) ? (int) Configuration::get('DISCOUNTONFIRSTORDER_DISCOUNT_AUTOAPPLY') : (int) Discountonfirstorder::APPLIED_AUTO;
        if (!$autoapply) {
            $voucher->code = Discountonfirstorder::PREFIX_DISCOUNT . Tools::strtoupper(Tools::passwdGen(8));
            $email_data['{coupon_code}'] = "<strong>" . $this->l('Code') . ": " . $voucher->code . "</strong>";
        }
        if ($voucher->add()) {
            if ($sendEmail) {
                Mail::Send((int) Configuration::get('PS_LANG_DEFAULT'), 'firstorder', Mail::l('Sconto applicato al tuo primo ordine!', (int) Configuration::get('PS_LANG_DEFAULT')), $email_data, $user->email, null, (string) Configuration::get('PS_SHOP_EMAIL'), (string) Configuration::get('PS_SHOP_NAME'), null, null, dirname(__FILE__) . '/mails/');
            }
        } else {
            echo Db::getInstance()->getMsgError();
        }
    }

    public function getContent() {

        $this->_header = '<div class="panel"><div class="alert alert-info" style="clear: both;">Here, you can set amount of the discount.</div></div>';
        $this->_html = '';
        $this->_postProcess();
        return $this->_header . $this->displayForm() . $this->_html;
    }

    public function displayForm() {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $fields_form = array(array());

        $options = array(
            1 => array('id_discount_type' => Discountonfirstorder::CART_RULE_PERCENT, 'name' => $this->l('Discount on order (%)')),
            2 => array('id_discount_type' => Discountonfirstorder::CART_RULE_AMOUNT, 'name' => $this->l('Discount on order (amount)')),
            3 => array('id_discount_type' => Discountonfirstorder::CART_RULE_FREE_SHIPPING, 'name' => $this->l('Free shiping'))
        );

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->l('Autoapply'),
                    'desc' => $this->l('The discount will be applied automatically on the first order OR the user have to apply himself the code generated.'),
                    'name' => 'discount_autoapply',
                    'required' => true,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'discount_autoapply_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'discount_autoapply_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Type'),
                    'desc' => $this->l('Choose a discount\'s type'),
                    'name' => 'id_discount_type',
                    'required' => true,
                    'options' => array(
                        'query' => $options,
                        'id' => 'id_discount_type',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Discount amount'),
                    'name' => 'discount_amount',
                    'class' => 'lg',
                    'required' => true,
                    'desc' => $this->l('Amount of the discount. Not insert percent, only number.')
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
        $helper = new HelperForm();
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;
        $helper->fields_value = $this->getConfigFormValues();
        return $helper->generateForm($fields_form);
    }

    public function getConfigFormValues() {
        return array(
            'id_discount_type' => (Configuration::hasKey('DISCOUNTONFIRSTORDER_DISCOUNT_TYPE')) ? (int) Configuration::get('DISCOUNTONFIRSTORDER_DISCOUNT_TYPE') : (int) Discountonfirstorder::CART_RULE_PERCENT,
            'discount_amount' => (Configuration::hasKey('DISCOUNTONFIRSTORDER_DISCOUNT_AMOUNT')) ? (int) Configuration::get('DISCOUNTONFIRSTORDER_DISCOUNT_AMOUNT') : (int) Discountonfirstorder::DISCOUNT_AMOUNT,
            'discount_autoapply' => (Configuration::hasKey('DISCOUNTONFIRSTORDER_DISCOUNT_AUTOAPPLY')) ? (int) Configuration::get('DISCOUNTONFIRSTORDER_DISCOUNT_AUTOAPPLY') : (int) Discountonfirstorder::APPLIED_AUTO,
        );
    }

    protected function _postProcess() {
        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('DISCOUNTONFIRSTORDER_DISCOUNT_AMOUNT', Tools::getValue('discount_amount'));
            Configuration::updateValue('DISCOUNTONFIRSTORDER_DISCOUNT_TYPE', Tools::getValue('id_discount_type'));
            Configuration::updateValue('DISCOUNTONFIRSTORDER_DISCOUNT_AUTOAPPLY', Tools::getValue('discount_autoapply'));
            $this->_html .= '<div class="conf confirm alert alert-success">' . $this->l('Settings updated') . '</div>';
        }
    }

}
