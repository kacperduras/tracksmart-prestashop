<?php

if (!defined('_PS_VERSION_'))
{
    exit;
}

class TrackSmart extends Module
{

    private $configuration_fields = [
        'TRACKSMART_STATE' => array(
            'type' => 'switch',
            'label' => 'State',
            'is_bool' => true,
            'desc' => 'General state of the module',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => true,
                    'label' => 'Enabled'
                ),
                array(
                    'id' => 'active_off',
                    'value' => false,
                    'label' => 'Disabled'
                ),
            ),
        ),

        'TRACKSMART_ID' => array(
            'type' => 'text',
            'label' => 'Container ID',
            'desc' => 'Format: GTM-XXXXXX',
            'required' => true
        )
    ];

    public function __construct()
    {
        $this->name = 'tracksmart';
        $this->tab = 'analytics_stats';
        $this->module_key = 'dc5b9ea5c7aeb8266461cf40270cc604';
        $this->version = '1.0.0';
        $this->author = 'Kacper Duras';
        $this->need_instance = 1;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('TrackSmart');
        $this->description = $this->l('Module to enhanced tracking for Google Analytics 4 (via Google Tag Manager)');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        foreach ($this->configuration_fields as $key => $value)
        {
            if ($value != null)
            {
                $boolean = $value['boolean'] ?? false;
                Configuration::updateValue($key, $boolean ? false : '');
            }
        }

        return parent::install() &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('header');
    }

    public function uninstall()
    {
        foreach ($this->configuration_fields as $key => $value)
        {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        if (Tools::isSubmit('submitTrackSmart'))
        {
            $state = Tools::getValue('TRACKSMART_STATE');
            $container = Tools::getValue('TRACKSMART_ID');

            if ($state && ($container == null || empty($container) || !substr($container, 0, 4 ) == "GTM-"))
            {
                $output .= $this->displayError('Please, provide valid format of container ID');
            }
            else
            {
                foreach (array_keys($this->configuration_fields) as $key)
                {
                    Configuration::updateValue($key, Tools::getValue($key));
                }

                $output .= $this->displayConfirmation('Settings updated');
            }
        }

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTrackSmart';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $vars = array();
        $input = array();

        foreach ($this->configuration_fields as $key => $value)
        {
            $vars[$key] = Configuration::get($key);

            $value['name'] = $key;
            array_push($input, $value);
        }

        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $output . $helper->generateForm(
            array(
                array(
                    'form' => array(
                        'legend' => array(
                            'title' => 'General settings',
                            'icon' => 'icon-cogs',
                        ),

                        'input' => $input,

                        'submit' => array(
                            'title' => 'Save',
                        ),
                    ),
                )
            ));
    }

    public function hookActionFrontControllerSetMedia()
    {
        if (!Configuration::get('TRACKSMART_STATE'))
        {
            return;
        }

        $this->context->controller->registerJavascript('tracksmart_sdk',
            'modules/' . $this->name . '/views/js/sdk.js', array('position' => 'head', 'priority' => 100));
        $this->context->controller->registerJavascript('tracksmart_front',
            'modules/' . $this->name . '/views/js/front.js', array('position' => 'bottom', 'priority' => 100));
    }

    public function hookHeader()
    {
        if (!Configuration::get('TRACKSMART_STATE'))
        {
            return;
        }

        $controller = $this->context->controller->php_self;
        if (empty($controller))
        {
            $controller = Tools::getValue('controller');
        }

        $event = array('data' => array());
        if ($controller === 'cart')
        {
            $event['name'] = 'begin_checkout';
            $event['data']['currency'] = $this->context->currency->iso_code;

            $products = array();

            foreach ($this->context->cart->getProducts() as $product)
            {
                $category = new Category($product['id_category_default'], $this->context->language->id);

                array_push($products, array(
                    'item_name' => Tools::replaceAccentedChars($product['name']),
                    'item_id' => $product['id_product'],
                    'price' => ((double) $product['price']),
                    'item_brand' => $product['manufacturer_name'] ?? null,
                    'item_category' => $category->name,
                    'item_variant' => $product['attributes_small'] ?? null,
                    'quantity' => $product['quantity']
                ));
            }

            $event['data']['items'] = $products;
        }
        elseif ($controller === 'order-confirmation')
        {
            $id_order = Tools::getValue('id_order');
            if ($id_order != null)
            {
                $cart = new Cart(Order::getCartIdStatic($id_order, $this->context->customer->id));

                $event['name'] = 'purchase';
                $event['data']['currency'] = $this->context->currency->iso_code;

                $products = array();

                foreach ($cart->getProducts() as $product)
                {
                    $category = new Category($product['id_category_default'], $this->context->language->id);

                    array_push($products, array(
                        'item_name' => Tools::replaceAccentedChars($product['name']),
                        'item_id' => $product['id_product'],
                        'price' => ((double) $product['price']),
                        'item_brand' => $product['manufacturer_name'] ?? null,
                        'item_category' => $category->name,
                        'item_variant' => $product['attributes_small'] ?? null,
                        'quantity' => $product['quantity']
                    ));
                }

                $coupons = array();
                foreach ($cart->getCartRules() as $target)
                {
                    $coupons[] = $target['name'];
                }

                $event['data']['transaction_id'] = $id_order;
                $event['data']['affiliation'] = Configuration::get('PS_SHOP_NAME');
                $event['data']['currency'] = $this->context->currency->iso_code;
                $event['data']['value'] = ((double) $cart->getOrderTotal(false, Cart::BOTH_WITHOUT_SHIPPING));
                $event['data']['tax'] = (((double) $cart->getOrderTotal(true)) - ((double) $cart->getOrderTotal(false)));
                $event['data']['shipping'] = ((double) $cart->getOrderTotal(false, Cart::ONLY_SHIPPING));
                $event['data']['coupon'] = implode(' | ', $coupons);
                $event['data']['items'] = $products;
            }
        }
        elseif ($controller === 'product')
        {
            $product = $this->context->controller->getTemplateVarProduct();
            $category = new Category($product['id_category_default'], $this->context->language->id);

            $event['name'] = 'view_item';
            $event['data']['currency'] = $this->context->currency->iso_code;
            $event['data']['items'] = array(array(
                'item_name' => Tools::replaceAccentedChars($product['name']),
                'item_id' => $product['id_product'],
                'price' => ((double) $product['price_amount']),
                'item_brand' => $product['manufacturer_name'] ?? null,
                'item_category' => $category->name,
                'item_variant' => $product['attributes_small'] ?? null,
                'quantity' => $product['minimal_quantity']
            ));
        }
        else if ($controller == 'category')
        {
            $page = Tools::getIsset('page') ? Tools::getValue('page') : 1;
            $limit = Configuration::get('PS_PRODUCTS_PER_PAGE') ?? 12;
            $products = $this->context->controller->getCategory()
                ->getProducts($this->context->language->id, $page, $limit);

            $event['name'] = 'view_item_list';
            $event['data']['currency'] = $this->context->currency->iso_code;
            $result = array();

            if ($products > 0)
            {
                foreach ($products as $product)
                {
                    $category = new Category($product['id_category_default'], $this->context->language->id);

                    array_push($result, array(
                        'item_name' => Tools::replaceAccentedChars($product['name']),
                        'item_id' => $product['id_product'],
                        'price' => ((double) $product['price']),
                        'item_brand' => $product['manufacturer_name'] ?? null,
                        'item_category' => $category->name,
                        'item_variant' => $product['attributes_small'] ?? null,
                        'quantity' => $product['quantity']
                    ));
                }
            }

            $event['data']['items'] = $result;
        }

        $variables = array(
            'tracksmart_container' => Configuration::get('TRACKSMART_ID'),
            'tracksmart_user' => $this->context->customer->id ?? null,
            'tracksmart_event' => $event['name'] ?? null,
            'tracksmart_data' => Tools::jsonDecode(Tools::jsonEncode($event['data'] ?? "{}"))
        );

        Media::addJsDef(array('tracksmart_frontcontroller' =>
            Context::getContext()->link->getModuleLink($this->name, 'ajax', array(), true)));

        $this->context->smarty->assign($variables);
        return $this->display(__FILE__, 'views/templates/hook/header.tpl');
    }

}
