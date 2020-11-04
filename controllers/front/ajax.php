<?php

use PrestaShop\PrestaShop\Adapter\ObjectPresenter;
use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;

class TrackSmartAjaxModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        if (!Tools::getIsset('id'))
        {
            Tools::redirect('400');
            return;
        }

        $id = Tools::getValue('id');
        $attribute = Tools::getIsset('attribute') ? ((int) Tools::getValue('attribute')) : 0;
        $customization = Tools::getIsset('customization') ? ((int) Tools::getValue('customization')) : 0;

        $product = null;

        if ($customization > 0)
        {
            $data = (new CartPresenter())->present($this->context->cart);

            foreach ($data['products'] as $target)
            {
                if ($target['id_product'] == $id && $target['id_product_attribute'] == $attribute
                    && $target['id_customization'] == $customization)
                {
                    $product = $target;
                    break;
                }
            }
        }

        if ($product == null)
        {
            $product = (new ObjectPresenter())->present(new Product($id, true, $this->context->language->id));
        }
        $category = new Category($product['id_category_default'], $this->context->language->id);

        ob_end_clean();
        header('Content-Type: application/json');

        die(Tools::jsonEncode(
            array(
                'item_name' => Tools::replaceAccentedChars($product['name']),
                'item_id' => $customization > 0 ? $product['id_product'] : $product['id'],
                'price' => ((double) $product['price']),
                'item_brand' => $product['manufacturer_name'] ?? null,
                'item_category' => $category->name,
                'item_variant' => $product['attributes_small'] ?? null,
                'quantity' => $customization > 0 ? $product['quantity'] : $product['minimal_quantity']
            )
        ));
    }

}
