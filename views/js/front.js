prestashop.on('clickQuickView', function(params)
{
    if (typeof(params) !== 'undefined')
    {
        let id = params.dataset.idProduct;
        let attribute = params.dataset.idProductAttribute;
        let customization = params.dataset.idCustomization;

        $.ajax({
            type: 'POST',
            url: tracksmart_frontcontroller,

            data: {
                id: id,
                attribute: attribute,
                customization: customization
            },

            success: function(data)
            {
                trackSmart.process('view_item', {
                    'currency': prestashop.currency.iso_code,
                    'items': [ data ]
                });
            },

            error: function(err)
            {
                console.error(err);
            }
        });
    }
});

prestashop.on('updateCart', function(params)
{
    if (typeof(params) !== 'undefined')
    {
        let id = params.reason.idProduct;
        let attribute = params.reason.idProductAttribute;
        let customization = params.reason.idCustomization;

        let action = params.reason.linkAction;

        if (action === 'add-to-cart' || action === 'delete-from-cart')
        {
            $.ajax({
                type: 'POST',
                url: tracksmart_frontcontroller,

                data: {
                    id: id,
                    attribute: attribute,
                    customization: customization
                },

                success: function(data)
                {
                    if (action === 'add-to-cart')
                    {
                        trackSmart.process('add_to_cart', {
                            'currency': prestashop.currency.iso_code,
                            'items': [ data ]
                        });
                    }
                    else if (action === 'delete-from-cart')
                    {
                        trackSmart.track('remove_from_cart', {
                            'currency': prestashop.currency.iso_code,
                            'items': [ data ]
                        });
                    }
                },

                error: function(err)
                {
                    console.error(err);
                }
            });
        }
    }
});
