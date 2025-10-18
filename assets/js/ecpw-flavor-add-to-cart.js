(function($){
    // Handle Add to Cart
    $(document).on('click', '.ecpw-addcart-btn', function(){
        var $product = $(this).closest('.ecpw-product-box');
        var product_id = $product.data('product-id') || 
                         $product.attr('data-product-id') || 
                         $product.find('input[name=add-to-cart],input[name=product_id]').val();

        var globalQty = parseInt($product.find('.ecpw-global-qty input[type=number]').val()||1,10);
        var flavors = {};

        $product.find('.ecpw-flavor-list .ecpw-flavor-row').each(function(){
            var $row = $(this);
            var name = $row.find('.ecpw-flavor-name').text().trim();
            var qty = parseInt($row.find('input[type=number]').val()||0,10);
            if (name && qty > 0) flavors[name] = qty;
        });

        if (!product_id) {
            alert('Product ID missing.');
            return;
        }

        var totalSelected = Object.values(flavors).reduce((t,v)=>t+v,0);
        if (totalSelected < 1) {
            alert('Please select at least one flavor.');
            return;
        }

        // ✅ Enforce per-box max flavor limit
        var maxAllowed = parseInt($product.data('max-flavors')) || 0;
        if (maxAllowed > 0 && totalSelected > maxAllowed) {
            alert('You can only select up to ' + maxAllowed + ' flavors for this box.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Ajout…');

        $.ajax({
            url: (window.ecpwFlavorData ? ecpwFlavorData.ajax_url : ''),
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ecpw_add_to_cart',
                product_id: product_id,
                quantity: globalQty,
                dev_chouchou_flavors: flavors
            },
            success: function(json) {
                function updateFragments(fragments) {
                    $.each(fragments, function(sel, html){
                        var $el = $(sel);
                        if($el.length) $el.html(html);
                    });
                }

                if(json && json.fragments) {
                    updateFragments(json.fragments);
                    $btn.text('Ajouté ✓');
                         // click the elementor cart toggle if found
                    const toggleBtn = document.querySelector('#elementor-menu-cart__toggle_button');
                    if (toggleBtn) toggleBtn.click();
                    setTimeout(function(){ $btn.text('Ajouter au panier').prop('disabled', false); }, 1200);
                } else if(json && json.data && json.data.fragments) {
                    updateFragments(json.data.fragments);
                    $btn.text('Ajouté ✓');
                            // click the elementor cart toggle if found
                    const toggleBtn = document.querySelector('#elementor-menu-cart__toggle_button');
                    if (toggleBtn) toggleBtn.click();

                    setTimeout(function(){ $btn.text('Ajouter au panier').prop('disabled', false); }, 1200);
                } else {
                    $btn.prop('disabled', false).text('Ajouter au panier');
                    alert((json && json.data && json.data.message) || (json && json.message) || 'Error');
                }
            },
            error: function(){
                $btn.prop('disabled', false).text('Ajouter au panier');
                alert('Erreur serveur, veuillez réessayer.');
            }
        });
    });

    // ✅ Increment/decrement local flavor qty with per-product limit
    $(document).on('click', '.ecpw-flavor-row .qty-plus', function(){
        var $row = $(this).closest('.ecpw-flavor-row');
        var $product = $(this).closest('.ecpw-product-box');
        var $input = $row.find('input[type=number]');
        var current = parseInt($input.val()||0,10);
        var maxAllowed = parseInt($product.data('max-flavors')) || 0;

        // Calculate total selected flavors in this product box
        var totalSelected = 0;
        $product.find('.ecpw-flavor-list input[type=number]').each(function(){
            totalSelected += parseInt($(this).val() || 0, 10);
        });

        if (maxAllowed > 0 && totalSelected >= maxAllowed) {
            alert('Maximum ' + maxAllowed + ' flavors allowed for this product.');
            return;
        }

        $input.val(current + 1);
        $input.trigger('change');
        updateFlavorButtons($product);
    });

    $(document).on('click', '.ecpw-flavor-row .qty-minus', function(){
        var $product = $(this).closest('.ecpw-product-box');
        var $input = $(this).siblings('input[type=number]');
        var v = parseInt($input.val()||0,10);
        if(v > 0) $input.val(v - 1).trigger('change');
        updateFlavorButtons($product);
    });

    // ✅ Increment/decrement global box qty
    $(document).on('click', '.ecpw-global-qty .qty-plus', function(){
        var $input = $(this).siblings('input[type=number]');
        $input.val(parseInt($input.val()||1,10)+1);
    });

    $(document).on('click', '.ecpw-global-qty .qty-minus', function(){
        var $input = $(this).siblings('input[type=number]');
        var v = parseInt($input.val()||1,10);
        if(v>1) $input.val(v-1);
    });

    // ✅ Auto-disable + buttons when limit is hit
    function updateFlavorButtons($product) {
        var maxAllowed = parseInt($product.data('max-flavors')) || 0;
        if (!maxAllowed) return;

        var totalSelected = 0;
        $product.find('.ecpw-flavor-list input[type=number]').each(function(){
            totalSelected += parseInt($(this).val() || 0, 10);
        });

        var disable = totalSelected >= maxAllowed;
        $product.find('.ecpw-flavor-row .qty-plus').prop('disabled', disable);
    }

})(jQuery);
