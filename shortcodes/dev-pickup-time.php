<?php 
// ✅ 1. Register WooCommerce fields internally (for backend recognition)
add_filter('woocommerce_checkout_fields', function ($fields) {
    $fields['pickup']['pickup_date'] = array(
        'type' => 'text',
        'required' => false,
        'label' => __('Jour de retrait', 'elementor-cart-products-widget'),
        'class' => array('form-row-first'),
        'priority' => 25,
    );

    $fields['pickup']['pickup_time'] = array(
        'type' => 'text',
        'required' => false,
        'label' => __('Heure de retrait', 'elementor-cart-products-widget'),
        'class' => array('form-row-last'),
        'priority' => 26,
    );

    return $fields;
});

// ✅ 2. Shortcode to display pickup fields
add_shortcode('pickup_fields', function () {
    ob_start(); ?>
    <div class="pickup-fields">
        <p class="pickup-location">
            <i class="dashicons dashicons-location"></i>
            <?php _e('Lieu de retrait — Boutique Chouchou — 30 Rue de l’Évêché, 13002 Marseille', 'elementor-cart-products-widget'); ?>
        </p>
        <p class="form-row form-row-first validate-required" style="padding-right: 10px;">
            <input type="text" name="pickup_date" id="pickup_date" class="input-text" required
                placeholder="<?php esc_attr_e('Jour de retrait *', 'elementor-cart-products-widget'); ?>">
        </p>

        <p class="form-row form-row-last validate-required" style="padding-left: 10px;">
            <input type="text" name="pickup_time" id="pickup_time" class="input-text" required
                placeholder="<?php esc_attr_e('Heure de retrait *', 'elementor-cart-products-widget'); ?>">
        </p>

        <div class="clear"></div>
        <style>
            .pickup-fields .pickup-location {
                display: flex;
                align-items: center;
                color: #9C4A3B;
                font-size: 0.95rem;
                margin-bottom: 15px;
            }

            .ecpw-qty {
                border: 0px;
            }

            .pickup-fields .pickup-location i {
                margin-right: 8px;
                color: #B54E3B;
            }

            .pickup-fields .form-row {
                display: inline-block;
                width: 48%;
                vertical-align: top;
            }

            .pickup-fields input.input-text {
                width: 100%;
                padding: 12px 14px;
                border: 2px solid #E6C7BC;
                border-radius: 8px;
                font-size: 0.95rem;
                background-color: #fff;
                transition: border-color 0.3s, box-shadow 0.3s;
            }

            .pickup-fields input.input-text:focus {
                border-color: #B54E3B;
                box-shadow: 0 0 0 2px rgba(181, 78, 59, 0.2);
                outline: none;
            }

            .pickup-fields label {
                font-size: 0.9rem;
                font-weight: 600;
                color: #5A2320;
                display: block;
                margin-bottom: 5px;
            }

            .pickup-fields abbr.required {
                color: #B54E3B;
            }

            @media (max-width: 600px) {
                .pickup-fields .form-row {
                    width: 100%;
                }
            }
        </style>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form.checkout');
            if (!form) return;
            form.addEventListener('submit', function (e) {
                const dateInput = document.querySelector('#pickup_date');
                const timeInput = document.querySelector('#pickup_time');
                if (!dateInput || !timeInput) return;
                if (!dateInput.value.trim() || !timeInput.value.trim()) {
                    e.preventDefault();
                    return false;
                }
                const hiddenDate = document.createElement('input');
                hiddenDate.type = 'hidden';
                hiddenDate.name = 'pickup_date';
                hiddenDate.value = dateInput.value;
                const hiddenTime = document.createElement('input');
                hiddenTime.type = 'hidden';
                hiddenTime.name = 'pickup_time';
                hiddenTime.value = timeInput.value;
                form.appendChild(hiddenDate);
                form.appendChild(hiddenTime);
            });
        });
    </script>
    <?php
    return ob_get_clean();
});

// ✅ 3. Add admin settings for pickup time limits
add_filter('woocommerce_get_settings_general', function ($settings) {
    $updated_settings = [];
    foreach ($settings as $section) {
        $updated_settings[] = $section;
        if (isset($section['id']) && 'general_options' === $section['id'] && 'sectionend' === $section['type']) {
            $updated_settings[] = [
                'title' => __('Pickup Time Settings', 'elementor-cart-products-widget'),
                'type' => 'title',
                'id' => 'pickup_time_settings',
                'desc' => __('Set the available pickup hours for customers.', 'elementor-cart-products-widget'),
            ];

            $updated_settings[] = [
                'title' => __('Start Time', 'elementor-cart-products-widget'),
                'id' => 'pickup_start_time',
                'type' => 'text',
                'css' => 'min-width:100px;',
                'desc' => __('Enter start time in 24-hour format (e.g., 14:00)', 'elementor-cart-products-widget'),
                'default' => '14:00',
                'autoload' => false,
            ];

            $updated_settings[] = [
                'title' => __('End Time', 'elementor-cart-products-widget'),
                'id' => 'pickup_end_time',
                'type' => 'text',
                'css' => 'min-width:100px;',
                'desc' => __('Enter end time in 24-hour format (e.g., 18:00)', 'elementor-cart-products-widget'),
                'default' => '18:00',
                'autoload' => false,
            ];

            $updated_settings[] = [
                'type' => 'sectionend',
                'id' => 'pickup_time_settings',
            ];
        }
    }
    return $updated_settings;
});

// ✅ 4. Validate Fields before Checkout Submission
add_action('woocommerce_checkout_process', function () {
    $pickup_date = isset($_POST['pickup_date']) ? sanitize_text_field($_POST['pickup_date']) : '';
    $pickup_time = isset($_POST['pickup_time']) ? sanitize_text_field($_POST['pickup_time']) : '';

    if (empty($pickup_date)) {
        wc_add_notice(__('Please select a pickup date.', 'elementor-cart-products-widget'), 'error');
    }
    if (empty($pickup_time)) {
        wc_add_notice(__('Please select a pickup time.', 'elementor-cart-products-widget'), 'error');
    }
});

// ✅ 5. Save Fields to Order Meta
add_action('woocommerce_checkout_update_order_meta', function ($order_id) {
    if (!empty($_POST['pickup_date'])) {
        update_post_meta($order_id, '_pickup_date', sanitize_text_field($_POST['pickup_date']));
    }
    if (!empty($_POST['pickup_time'])) {
        update_post_meta($order_id, '_pickup_time', sanitize_text_field($_POST['pickup_time']));
    }
});

// ✅ 6. Display in Admin Order Details
add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $pickup_date = get_post_meta($order->get_id(), '_pickup_date', true);
    $pickup_time = get_post_meta($order->get_id(), '_pickup_time', true);
    if ($pickup_date || $pickup_time) {
        echo '<p><strong>' . __('Jour de retrait:', 'elementor-cart-products-widget') . '</strong> ' . esc_html($pickup_date) . '</p>';
        echo '<p><strong>' . __('Heure de retrait:', 'elementor-cart-products-widget') . '</strong> ' . esc_html($pickup_time) . '</p>';
    }
});

// ✅ 7. Display on Thank You Page
add_action('woocommerce_thankyou', function ($order_id) {
    $pickup_date = get_post_meta($order_id, '_pickup_date', true);
    $pickup_time = get_post_meta($order_id, '_pickup_time', true);
    if ($pickup_date || $pickup_time) {
        echo '<section class="woocommerce-order-details">
            <h2>' . __('Détails de retrait', 'elementor-cart-products-widget') . '</h2>
            <p><strong>' . __('Jour de retrait:', 'elementor-cart-products-widget') . '</strong> ' . esc_html($pickup_date) . '</p>
            <p><strong>' . __('Heure de retrait:', 'elementor-cart-products-widget') . '</strong> ' . esc_html($pickup_time) . '</p>
        </section>';
    }
});

// ✅ 8. Display on My Account → Orders
add_action('woocommerce_order_details_after_order_table', function ($order) {
    $pickup_date = get_post_meta($order->get_id(), '_pickup_date', true);
    $pickup_time = get_post_meta($order->get_id(), '_pickup_time', true);
    if ($pickup_date || $pickup_time) {
        echo '<section class="woocommerce-order-details">
            <h2>' . __('Détails de retrait', 'elementor-cart-products-widget') . '</h2>
            <p><strong>' . __('Jour de retrait:', 'elementor-cart-products-widget') . '</strong> ' . esc_html($pickup_date) . '</p>
            <p><strong>' . __('Heure de retrait:', 'elementor-cart-products-widget') . '</strong> ' . esc_html($pickup_time) . '</p>
        </section>';
    }
});

// ✅ 9. Enqueue Flatpickr for Date & Time Fields with dynamic times
add_action('wp_enqueue_scripts', function () {
    if (is_checkout()) {
        wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), '4.6.13', true);

        wp_add_inline_script('flatpickr-js', "
            jQuery(function($){
                const pickupDate = $('#pickup_date');
                const pickupTime = $('#pickup_time');

                // Flatpickr for date with Monday/Tuesday blocked
                flatpickr(pickupDate[0], {
                    dateFormat: 'd/m/Y',
                    locale: 'fr',
                    minDate: 'today',
                    disable: [
                        function(date) { return date.getDay() === 1 || date.getDay() === 2; }
                    ],
                   onChange: function(selectedDates, dateStr, instance) {
                        if (!selectedDates.length) return;
                        const day = selectedDates[0].getDay();
                        let minTime, maxTime;

                        if (day >= 3 && day <= 5) { // Wed-Fri
                            minTime = '14:00';
                            maxTime = '18:00';
                        } else if (day === 6 || day === 0) { // Sat-Sun
                            minTime = '10:00';
                            maxTime = '18:00';
                        } else { // Fallback
                            minTime = '00:00';
                            maxTime = '23:59';
                        }

                        // Get currently selected time
                        let currentTime = pickupTime.val();
                        // Convert to 24h format string (HH:MM)
                        if (currentTime) {
                            const d = new Date('1970-01-01T' + currentTime);
                            const hh = String(d.getHours()).padStart(2, '0');
                            const mm = String(d.getMinutes()).padStart(2, '0');
                            currentTime = `${hh}:${mm}`;
                            // Adjust if currentTime is outside range
                            if (currentTime < minTime) currentTime = minTime;
                            if (currentTime > maxTime) currentTime = maxTime;
                        } else {
                            currentTime = minTime;
                        }

                        // Re-initialize time picker dynamically
                        flatpickr(pickupTime[0], {
                            enableTime: true,
                            noCalendar: true,
                            dateFormat: 'H:i',
                            time_24hr: true,
                            minTime: minTime,
                            maxTime: maxTime,
                            defaultDate: currentTime
                        });
                    }

                });

                // Initial time picker setup
                flatpickr(pickupTime[0], {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minTime: '14:00',
                    maxTime: '18:00',
                });
            });
        ");
    }
});
