(function($){
    $(document).on('click', '.ecpw-addcart-btn', function(){
        var $product = $(this).closest('.ecpw-product-box');
        var product_id = $product.data('product-id') || $product.attr('data-product-id') || $product.find('input[name=add-to-cart],input[name=product_id]').val();
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
        if (Object.values(flavors).reduce((t,v)=>t+v,0) < 1) {
            alert('Please select at least one flavor.');
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
                    setTimeout(function(){ $btn.text('Ajouter au panier').prop('disabled', false); }, 1200);
                } else if(json && json.data && json.data.fragments) {
                    updateFragments(json.data.fragments);
                    $btn.text('Ajouté ✓');
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
    // Increment/decrement local flavor qty
    $(document).on('click', '.ecpw-flavor-row .qty-plus', function(){
        var $input = $(this).siblings('input[type=number]');
        $input.val(parseInt($input.val()||0,10)+1);
        $input.trigger('change');
    });
    $(document).on('click', '.ecpw-flavor-row .qty-minus', function(){
        var $input = $(this).siblings('input[type=number]');
        var v = parseInt($input.val()||0,10);
        if(v>0) $input.val(v-1).trigger('change');
    });
    // Increment/decrement global box qty
    $(document).on('click', '.ecpw-global-qty .qty-plus', function(){
        var $input = $(this).siblings('input[type=number]');
        $input.val(parseInt($input.val()||1,10)+1);
    });
    $(document).on('click', '.ecpw-global-qty .qty-minus', function(){
        var $input = $(this).siblings('input[type=number]');
        var v = parseInt($input.val()||1,10);
        if(v>1) $input.val(v-1);
    });
})(jQuery);
