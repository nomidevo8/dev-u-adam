(function($){
    // Quantity buttons
    $(document).on('click', '.ecpw-product .qty-minus', function(){
        var $input = $(this).closest('.ecpw-qty').find('input[name=quantity]');
        var v = parseInt($input.val() || '1', 10);
        if(v > 1) $input.val(v-1);
    });
    $(document).on('click', '.ecpw-product .qty-plus', function(){
        var $input = $(this).closest('.ecpw-qty').find('input[name=quantity]');
        var v = parseInt($input.val() || '1', 10);
        $input.val(v+1);
    });

    // === 🧩 Handle variation price updates ===
    $(document).on('change', '.ecpw-attributes input[type=radio]', function() {
        var $product = $(this).closest('.ecpw-product');
        var variationsJson = $product.find('.ecpw-variation-data').text();

        if (!variationsJson) return;
        var variations;
        try {
            variations = JSON.parse(variationsJson);
        } catch (e) {
            return;
        }

        // Collect selected attributes
        var chosen = {};
        $product.find('.ecpw-attributes input[type=radio]:checked').each(function(){
            chosen[this.name.replace(/^attribute_/, '')] = this.value;
        });

        // Find matching variation
        var matched = variations.find(function(v){
            var match = true;
            for (var key in v.attributes) {
                var cleanKey = key.replace(/^attribute_/, '');
                if (v.attributes[key] !== '' && chosen[cleanKey] !== v.attributes[key]) {
                    match = false;
                    break;
                }
            }
            return match;
        });
        // Update price
        var $priceBox = $product.find('.ecpw-price-tag');
        if (matched && matched.display_price) {
            if (matched.price_html) {
                $priceBox.html(matched.price_html);
            } else {
                $priceBox.html('€' + parseFloat(matched.display_price).toFixed(2));
            }
        } else {
            $priceBox.html($priceBox.data('default-price'));
        }
    });

    // Add-to-cart handling
    $(document).on('submit', '.ecpw-product form.ecpw-add-to-cart', function(e){
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('.ecpw-add-btn');
        var original = $btn.text();
        $btn.prop('disabled', true).text($btn.data('adding') || 'Adding…');

        var formData = $form.serializeArray();
        // Add selected attributes if they exist (radios)
        $form.closest('.ecpw-product').find('.ecpw-attributes input[type=radio]:checked').each(function(){
            formData.push({name: this.name, value: this.value});
        });

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(json){
                function updateFragments(fragments) {
                    $.each(fragments, function(sel, html){
                        var $el = $(sel);
                        if($el.length) $el.html(html);
                    });
                }
                if(json && json.fragments) {
                    updateFragments(json.fragments);
                    $btn.text($btn.data('added') || 'Added ✓');
                            // click the elementor cart toggle if found
                    const toggleBtn = document.querySelector('#elementor-menu-cart__toggle_button');
                    if (toggleBtn) toggleBtn.click();

                    setTimeout(function(){$btn.text(original).prop('disabled', false);}, 1200);
                } else if(json && json.data && json.data.fragments) {
                    updateFragments(json.data.fragments);
                    $btn.text($btn.data('added') || 'Added ✓');
                    const toggleBtn = document.querySelector('#elementor-menu-cart__toggle_button');
                    if (toggleBtn) toggleBtn.click();

                    setTimeout(function(){$btn.text(original).prop('disabled', false);}, 1200);
                } else {
                    $btn.prop('disabled', false).text(original);
                    alert((json && json.data && json.data.message) || (json && json.message) || 'Error');
                }
            },
            error: function(){
                $btn.prop('disabled', false).text(original);
                alert('Error');
            }
        });
    });
})(jQuery);
