<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ALDmbpm_WC_Gateway_Bangladeshi_Bank_Payment extends WC_Payment_Gateway {

    public $default_title;

    public function __construct() {
        $this->id                 = 'bangladeshi_bank_payment';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __( 'Bangladeshi Bank Payment Method', 'multiple-bank-payment-method' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option( 'title' );
        $this->default_title      = $this->title;

        $this->maybe_migrate_old_settings();

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_payment_receipt_admin_order' ) );
        add_filter( 'woocommerce_checkout_form_tag', array( $this, 'add_enctype_to_checkout_form' ), 99 );
    }

    /**
     * Get payment method data for block-based checkout compatibility.
     */
    public function get_payment_method_data() {
        $banks = $this->get_bank_accounts();
        $formatted_banks = array();
        
        foreach ( $banks as $index => $bank ) {
            $formatted_banks[] = array(
                'id'             => $index,
                'account_name'   => $bank['account_name'],
                'account_number'=> $bank['account_number'],
                'account_holder'=> $bank['account_holder'],
                'branch_name'   => $bank['branch_name'],
                'routing_number'=> $bank['routing_number'],
                'swift_code'    => $bank['swift_code'] ?? '',
                'bank_address'  => $bank['bank_address'] ?? '',
                'bank_logo_url' => $bank['bank_logo_url'] ?? '',
            );
        }
        
        return array(
            'title'       => $this->title,
            'description' => $this->get_option( 'description' ),
            'supports'   => array_filter(
                $this->supports,
                fn( $feature ) => in_array( $feature, array( 'products', 'refunds' ), true )
            ),
            'banks'      => $formatted_banks,
        );
    }

    /**
     * Migrate legacy single-bank settings.
     */
    private function maybe_migrate_old_settings() {
        $banks = $this->get_bank_accounts();
        if ( ! empty( $banks ) ) {
            return;
        }

        $old_account_name   = get_option( 'woocommerce_bangladeshi_bank_payment_account_name' );
        $old_account_number = get_option( 'woocommerce_bangladeshi_bank_payment_account_number' );
        $old_account_holder = get_option( 'woocommerce_bangladeshi_bank_payment_account_holder' );
        $old_branch_name    = get_option( 'woocommerce_bangladeshi_bank_payment_branch_name' );
        $old_routing_number = get_option( 'woocommerce_bangladeshi_bank_payment_routing_number' );
        $old_bank_logo_url  = get_option( 'woocommerce_bangladeshi_bank_payment_bank_logo_url' );

        if ( ! $old_account_name && ! $old_account_number ) {
            return;
        }

        $single_bank = array(
            'account_name'   => $old_account_name   ?: '',
            'account_number' => $old_account_number ?: '',
            'account_holder' => $old_account_holder ?: '',
            'branch_name'    => $old_branch_name    ?: '',
            'routing_number' => $old_routing_number ?: '',
            'swift_code'     => '',
            'bank_address'   => '',
            'bank_logo_url'  => $old_bank_logo_url  ?: '',
        );

        $settings = get_option( 'woocommerce_bangladeshi_bank_payment_settings', array() );
        $settings['bank_accounts'] = array( $single_bank );
        update_option( 'woocommerce_bangladeshi_bank_payment_settings', $settings );

        foreach ( array( 'account_name', 'account_number', 'account_holder', 'branch_name', 'routing_number', 'swift_code', 'bank_address', 'bank_logo_url' ) as $key ) {
            delete_option( 'woocommerce_bangladeshi_bank_payment_' . $key );
        }
    }

    private function get_bank_accounts() {
        return $this->get_option( 'bank_accounts', array() );
    }

    /**
     * Override process_admin_options to handle custom multi_bank field saving.
     */
    public function process_admin_options() {
        parent::process_admin_options();

        $settings_key = 'woocommerce_bangladeshi_bank_payment_settings';
        $settings = get_option( $settings_key, array() );
        $posted_data = isset( $_POST[ $settings_key ] ) ? $_POST[ $settings_key ] : array();
        
        if ( isset( $posted_data['bank_accounts'] ) && is_array( $posted_data['bank_accounts'] ) ) {
            $bank_accounts = array();
            
            foreach ( $posted_data['bank_accounts'] as $bank ) {
                if ( ! empty( $bank['account_name'] ) || ! empty( $bank['account_number'] ) ) {
                    $bank_accounts[] = array(
                        'account_name'   => sanitize_text_field( wp_unslash( $bank['account_name'] ?? '' ) ),
                        'account_number' => sanitize_text_field( wp_unslash( $bank['account_number'] ?? '' ) ),
                        'account_holder' => sanitize_text_field( wp_unslash( $bank['account_holder'] ?? '' ) ),
                        'branch_name'    => sanitize_text_field( wp_unslash( $bank['branch_name'] ?? '' ) ),
                        'routing_number' => sanitize_text_field( wp_unslash( $bank['routing_number'] ?? '' ) ),
                        'swift_code'     => sanitize_text_field( wp_unslash( $bank['swift_code'] ?? '' ) ),
                        'bank_address'   => sanitize_textarea_field( wp_unslash( $bank['bank_address'] ?? '' ) ),
                        'bank_logo_url'  => esc_url_raw( $bank['bank_logo_url'] ?? '' ),
                    );
                }
            }
            
            $settings['bank_accounts'] = $bank_accounts;
            update_option( $settings_key, $settings );
        }
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'multiple-bank-payment-method' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable', 'multiple-bank-payment-method' ),
                'default' => 'yes',
            ),
            'title'   => array(
                'title'   => __( 'Gateway Title', 'multiple-bank-payment-method' ),
                'type'    => 'text',
                'default' => __( 'Bangladeshi Bank Payment', 'multiple-bank-payment-method' ),
            ),
            'bank_accounts' => array(
                'title'             => __( 'Bank Accounts', 'multiple-bank-payment-method' ),
                'type'              => 'multi_bank',
                'description'       => __( 'Add one or more bank accounts.', 'multiple-bank-payment-method' ),
                'default'           => array(),
                'placeholder'       => array(),
            ),
        );
    }

    /**
     * Render the multi-bank repeater field in admin.
     */
    public function generate_multi_bank_html( $key, $data ) {
        $field_key   = 'woocommerce_bangladeshi_bank_payment_settings[' . $key . ']';
        $field_value = $this->get_option( $key, isset( $data['default'] ) ? $data['default'] : array() );
        if ( ! is_array( $field_value ) ) {
            $field_value = array();
        }

        ob_start();
        ?>
        <style>
            .mbpm-admin-repeater-wrap { padding: 0; }
            .mbpm-bank-item { border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; margin-bottom: 16px; background: #f6f7f7; }
            .mbpm-bank-item-header { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
            .mbpm-bank-item-title { font-weight:600; font-size:15px; color:#1d2327; flex:1; }
            .mbpm-remove-bank { background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; color:#c92c2c; cursor:pointer; padding:4px 10px; font-size:13px; }
            .mbpm-bank-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            .mbpm-bank-grid .forminp { margin: 0 !important; }
            .mbpm-bank-grid label { display:block; font-size:12px; font-weight:600; color:#1d2327; margin-bottom:4px; }
            .mbpm-bank-grid input[type="text"] { width:100%; }
            .mbpm-add-bank-btn { margin-top:8px; margin-bottom:20px; }
        </style>

        <div class="mbpm-admin-repeater-wrap">
            <div id="mbpm-bank-list">
                <?php foreach ( $field_value as $index => $bank ) : ?>
                    <div class="mbpm-bank-item" data-index="<?php echo esc_attr( $index ); ?>">
                        <div class="mbpm-bank-item-header">
                            <span class="mbpm-bank-item-title"><?php echo esc_html( isset( $bank['account_name'] ) && $bank['account_name'] ? $bank['account_name'] : __( '(New Bank)', 'multiple-bank-payment-method' ) ); ?></span>
                            <button type="button" class="mbpm-remove-bank" data-index="<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Remove', 'multiple-bank-payment-method' ); ?></button>
                        </div>
                        <div class="mbpm-bank-grid">
                            <div class="forminp">
                                <label><?php esc_html_e( 'Bank Name *', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][account_name]" value="<?php echo esc_attr( $bank['account_name'] ?? '' ); ?>" />
                            </div>
                            <div class="forminp">
                                <label><?php esc_html_e( 'A/C Number *', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][account_number]" value="<?php echo esc_attr( $bank['account_number'] ); ?>" />
                            </div>
                            <div class="forminp">
                                <label><?php esc_html_e( 'A/C Holder Name *', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][account_holder]" value="<?php echo esc_attr( $bank['account_holder'] ); ?>" />
                            </div>
                            <div class="forminp">
                                <label><?php esc_html_e( 'Branch Name', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][branch_name]" value="<?php echo esc_attr( $bank['branch_name'] ); ?>" />
                            </div>
                            <div class="forminp">
                                <label><?php esc_html_e( 'Routing Number', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][routing_number]" value="<?php echo esc_attr( $bank['routing_number'] ); ?>" />
                            </div>
                            <div class="forminp">
                                <label><?php esc_html_e( 'SWIFT Code', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][swift_code]" value="<?php echo esc_attr( $bank['swift_code'] ?? '' ); ?>" />
                            </div>
                            <div class="forminp">
                                <label><?php esc_html_e( 'Bank Address', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][bank_address]" value="<?php echo esc_attr( $bank['bank_address'] ?? '' ); ?>" />
                            </div>
                            <div class="forminp">
                                <label><?php esc_html_e( 'Bank Logo URL', 'multiple-bank-payment-method' ); ?></label>
                                <input type="text" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][bank_logo_url]" value="<?php echo esc_attr( $bank['bank_logo_url'] ); ?>" />
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button mbpm-add-bank-btn" id="mbpm-add-bank">+ <?php esc_html_e( 'Add Bank', 'multiple-bank-payment-method' ); ?></button>
        </div>

        <script type="text/javascript">
        (function() {
            var fieldKey = '<?php echo esc_js( $field_key ); ?>';
            var bankTemplate = 
                '<div class="mbpm-bank-item" data-index="{{INDEX}}">' +
                '<div class="mbpm-bank-item-header">' +
                '<span class="mbpm-bank-item-title"><?php echo esc_js( __( '(New Bank)', 'multiple-bank-payment-method' ) ); ?></span>' +
                '<button type="button" class="mbpm-remove-bank" data-index="{{INDEX}}"><?php echo esc_js( __( 'Remove', 'multiple-bank-payment-method' ) ); ?></button>' +
                '</div>' +
                '<div class="mbpm-bank-grid">' +
                '<div class="forminp"><label><?php echo esc_js( __( 'Bank Name *', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][account_name]" value="" /></div>' +
                '<div class="forminp"><label><?php echo esc_js( __( 'A/C Number *', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][account_number]" value="" /></div>' +
                '<div class="forminp"><label><?php echo esc_js( __( 'A/C Holder Name *', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][account_holder]" value="" /></div>' +
                '<div class="forminp"><label><?php echo esc_js( __( 'Branch Name', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][branch_name]" value="" /></div>' +
                '<div class="forminp"><label><?php echo esc_js( __( 'Routing Number', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][routing_number]" value="" /></div>' +
                '<div class="forminp"><label><?php echo esc_js( __( 'SWIFT Code', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][swift_code]" value="" /></div>' +
                '<div class="forminp"><label><?php echo esc_js( __( 'Bank Address', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][bank_address]" value="" /></div>' +
                '<div class="forminp"><label><?php echo esc_js( __( 'Bank Logo URL', 'multiple-bank-payment-method' ) ); ?></label><input type="text" name="<?php echo esc_js( $field_key ); ?>[{{INDEX}}][bank_logo_url]" value="" /></div>' +
                '</div>' +
                '</div>';
            
            // Add Bank button click
            document.getElementById('mbpm-add-bank').addEventListener('click', function() {
                var list = document.getElementById('mbpm-bank-list');
                var items = list.querySelectorAll('.mbpm-bank-item');
                var newIndex = items.length;
                var html = bankTemplate.split('{{INDEX}}').join(newIndex);
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                list.appendChild(wrapper.firstChild);
            });
            
            // Remove Bank button click (event delegation)
            document.getElementById('mbpm-bank-list').addEventListener('click', function(e) {
                if (e.target.classList.contains('mbpm-remove-bank')) {
                    var item = e.target.closest('.mbpm-bank-item');
                    if (item) {
                        item.remove();
                        // Re-index remaining items
                        var items = document.querySelectorAll('#mbpm-bank-list .mbpm-bank-item');
                        items.forEach(function(el, idx) {
                            el.setAttribute('data-index', idx);
                            var inputs = el.querySelectorAll('input');
                            inputs.forEach(function(input) {
                                var name = input.name;
                                name = name.replace(/\[\d+\]/, '[' + idx + ']');
                                input.name = name;
                            });
                        });
                    }
                }
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /* -----------------------------------------------------------
     *  FRONT-END PAYMENT FIELDS
     * -----------------------------------------------------------*/
    public function payment_fields() {
        $banks = $this->get_bank_accounts();
        ?>
        <div id="mbpm-payment-details-wrapper" class="ald-container">
            <?php if ( count( $banks ) > 1 ) : ?>
                <div class="ald-bank-selector" style="margin-bottom:18px;">
                    <label class="ald-select-label" for="mbpm_selected_bank">
                        <?php esc_html_e( 'Select your bank:', 'multiple-bank-payment-method' ); ?>
                    </label>
                    <select id="mbpm_selected_bank" name="mbpm_selected_bank" class="woocommerce-select ald-bank-select" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;">
                        <option value=""><?php esc_html_e( '— Choose a bank —', 'multiple-bank-payment-method' ); ?></option>
                        <?php foreach ( $banks as $i => $bank ) : ?>
                            <option value="<?php echo esc_attr( $i ); ?>"
                                data-name="<?php echo esc_attr( $bank['account_name'] ); ?>"
                                data-account="<?php echo esc_attr( $bank['account_number'] ); ?>"
                                data-holder="<?php echo esc_attr( $bank['account_holder'] ); ?>"
                                data-branch="<?php echo esc_attr( $bank['branch_name'] ); ?>"
                                data-routing="<?php echo esc_attr( $bank['routing_number'] ); ?>"
                                data-swift="<?php echo esc_attr( $bank['swift_code'] ?? '' ); ?>"
                                data-address="<?php echo esc_attr( $bank['bank_address'] ?? '' ); ?>"
                                data-logo="<?php echo esc_attr( $bank['bank_logo_url'] ?? '' ); ?>">
                                <?php echo esc_html( $bank['account_name'] ); ?> — <?php echo esc_html( $bank['account_number'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div id="mbpm-bank-info" class="ald-bank-card">
                <?php 
                if ( count( $banks ) === 1 ) {
                    $bank = reset( $banks );
                    // Display bank logo if available
                    if ( ! empty( $bank['bank_logo_url'] ) ) {
                        echo '<div class="ald-bank-logo" style="margin-bottom:10px;"><img src="' . esc_url( $bank['bank_logo_url'] ) . '" alt="' . esc_attr( $bank['account_name'] ) . '" style="max-height:60px;max-width:200px;height:auto;" /></div>';
                    }
                    echo '<span class="ald-bank-header" id="mbpm-bank-header">' . esc_html( $bank['account_name'] ) . '</span>';
                    echo '<span id="mbpm-bank-details">';
                    if ( ! empty( $bank['account_number'] ) ) {
                        echo '<div class="ald-info-row"><span class="ald-label">' . esc_html__( 'Account Number:', 'multiple-bank-payment-method' ) . '</span><span class="ald-value">' . esc_html( $bank['account_number'] ) . '</span></div>';
                    }
                    if ( ! empty( $bank['account_holder'] ) ) {
                        echo '<div class="ald-info-row"><span class="ald-label">' . esc_html__( 'Account Holder:', 'multiple-bank-payment-method' ) . '</span><span class="ald-value">' . esc_html( $bank['account_holder'] ) . '</span></div>';
                    }
                    if ( ! empty( $bank['branch_name'] ) ) {
                        echo '<div class="ald-info-row"><span class="ald-label">' . esc_html__( 'Branch:', 'multiple-bank-payment-method' ) . '</span><span class="ald-value">' . esc_html( $bank['branch_name'] ) . '</span></div>';
                    }
                    if ( ! empty( $bank['routing_number'] ) ) {
                        echo '<div class="ald-info-row"><span class="ald-label">' . esc_html__( 'Routing:', 'multiple-bank-payment-method' ) . '</span><span class="ald-value">' . esc_html( $bank['routing_number'] ) . '</span></div>';
                    }
                    if ( ! empty( $bank['swift_code'] ) ) {
                        echo '<div class="ald-info-row"><span class="ald-label">' . esc_html__( 'SWIFT Code:', 'multiple-bank-payment-method' ) . '</span><span class="ald-value">' . esc_html( $bank['swift_code'] ) . '</span></div>';
                    }
                    if ( ! empty( $bank['bank_address'] ) ) {
                        echo '<div class="ald-info-row"><span class="ald-label">' . esc_html__( 'Address:', 'multiple-bank-payment-method' ) . '</span><span class="ald-value">' . esc_html( $bank['bank_address'] ) . '</span></div>';
                    }
                    echo '</span>';
                } elseif ( count( $banks ) > 1 ) {
                    echo '<span class="ald-bank-header" id="mbpm-bank-header">—</span>';
                    echo '<span id="mbpm-bank-details"><p style="color:#64748b;">' . esc_html__( 'Please select a bank from the dropdown to see its details.', 'multiple-bank-payment-method' ) . '</p></span>';
                } else {
                    echo '<span class="ald-bank-header" id="mbpm-bank-header"></span>';
                    echo '<span id="mbpm-bank-details"><p style="color:#64748b;">' . esc_html__( 'No bank accounts configured.', 'multiple-bank-payment-method' ) . '</p></span>';
                }
                ?>
            </div>
            
            <?php if ( count( $banks ) > 1 ) : ?>
            <script type="text/javascript">
            (function() {
                var bankSelect = document.getElementById('mbpm_selected_bank');
                var bankHeader = document.getElementById('mbpm-bank-header');
                var bankDetails = document.getElementById('mbpm-bank-details');
                
                if (bankSelect && bankHeader && bankDetails) {
                    bankSelect.addEventListener('change', function() {
                        var idx = this.value;
                        var selectedOption = this.options[this.selectedIndex];
                        
                        if (idx === '' || !selectedOption) {
                            bankHeader.textContent = '—';
                            bankDetails.innerHTML = '<p style="color:#64748b;"><?php echo esc_js( __( 'Please select a bank from the dropdown to see its details.', 'multiple-bank-payment-method' ) ); ?></p>';
                            return;
                        }
                        
                        var bankName = selectedOption.getAttribute('data-name') || '';
                        var bankLogo = selectedOption.getAttribute('data-logo') || '';
                        bankHeader.textContent = bankName;
                        
                        var html = '';
                        // Display bank logo if available
                        if (bankLogo) {
                            html += '<div class="ald-bank-logo" style="margin-bottom:10px;"><img src="' + bankLogo + '" alt="' + (bankName || '') + '" style="max-height:60px;max-width:200px;height:auto;" /></div>';
                        }
                        
                        // Get data from selected option
                        var bankAcc = selectedOption.getAttribute('data-account') || '';
                        var bankHolder = selectedOption.getAttribute('data-holder') || '';
                        var bankBranch = selectedOption.getAttribute('data-branch') || '';
                        var bankRouting = selectedOption.getAttribute('data-routing') || '';
                        var bankSwift = selectedOption.getAttribute('data-swift') || '';
                        var bankAddress = selectedOption.getAttribute('data-address') || '';

                        if (bankAcc) html += '<div class="ald-info-row"><span class="ald-label"><?php echo esc_js( __( 'Account Number:', 'multiple-bank-payment-method' ) ); ?></span><span class="ald-value">' + bankAcc + '</span></div>';
                        if (bankHolder) html += '<div class="ald-info-row"><span class="ald-label"><?php echo esc_js( __( 'Account Holder:', 'multiple-bank-payment-method' ) ); ?></span><span class="ald-value">' + bankHolder + '</span></div>';
                        if (bankBranch) html += '<div class="ald-info-row"><span class="ald-label"><?php echo esc_js( __( 'Branch:', 'multiple-bank-payment-method' ) ); ?></span><span class="ald-value">' + bankBranch + '</span></div>';
                        if (bankRouting) html += '<div class="ald-info-row"><span class="ald-label"><?php echo esc_js( __( 'Routing:', 'multiple-bank-payment-method' ) ); ?></span><span class="ald-value">' + bankRouting + '</span></div>';
                        if (bankSwift) html += '<div class="ald-info-row"><span class="ald-label"><?php echo esc_js( __( 'SWIFT Code:', 'multiple-bank-payment-method' ) ); ?></span><span class="ald-value">' + bankSwift + '</span></div>';
                        if (bankAddress) html += '<div class="ald-info-row"><span class="ald-label"><?php echo esc_js( __( 'Address:', 'multiple-bank-payment-method' ) ); ?></span><span class="ald-value">' + bankAddress + '</span></div>';
                        
                        bankDetails.innerHTML = html;
                    });
                }
            })();
            </script>
            <?php endif; ?>

            <script type="text/javascript">
            (function() {
                // Handle payment method selection
                document.addEventListener('DOMContentLoaded', function() {
                    var form = document.querySelector('form.checkout');
                    if (!form) return;
                    
                    // Listen for payment method changes
                    document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
                        radio.addEventListener('change', function() {
                            if (this.value === 'bangladeshi_bank_payment') {
                                // Switch to standard form submission for file uploads
                                form.setAttribute('action', form.getAttribute('action') || window.location.href);
                                form.removeAttribute('data-checkout-url');
                                form.classList.remove('checkout');
                            }
                        });
                    });
                    
                    // Intercept form submission for our gateway
                    form.addEventListener('submit', function(e) {
                        var paymentInput = document.querySelector('input[name="payment_method"]:checked');
                        if (!paymentInput || paymentInput.value !== 'bangladeshi_bank_payment') return;
                        
                        var fileInput = document.getElementById('mbpm-payment-receipt');
                        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                            e.preventDefault();
                            alert('<?php echo esc_js( __( 'Please upload your payment receipt.', 'multiple-bank-payment-method' ) ); ?>');
                            return;
                        }
                        
                        // Validate file size
                        if (fileInput.files[0].size > 5242880) {
                            e.preventDefault();
                            alert('<?php echo esc_js( __( 'File size exceeds 5MB limit.', 'multiple-bank-payment-method' ) ); ?>');
                            return;
                        }
                        
                        // Remove processing state
                        jQuery(form).removeClass('processing').unblock();
                        
                        // Allow normal form submission
                        return;
                    }, false);
                    
                    // Check initial state
                    var checked = document.querySelector('input[name="payment_method"]:checked');
                    if (checked && checked.value === 'bangladeshi_bank_payment') {
                        form.removeAttribute('data-checkout-url');
                        form.classList.remove('checkout');
                    }
                });
            })();
            </script>

            <div class="ald-upload-section" style="margin-top:20px;">
                <label class="ald-upload-label" for="mbpm-payment-receipt">
                    <?php esc_html_e( 'Upload Payment Receipt (Required)', 'multiple-bank-payment-method' ); ?> <span style="color:red;">*</span>
                </label>
                <input id="mbpm-payment-receipt" class="ald-file-input" type="file" name="mbpm_payment_receipt" accept="image/png,image/jpeg,image/jpg" required />
                <?php wp_nonce_field( 'mbpm_payment_upload', 'mbpm_payment_nonce' ); ?>
                <p style="font-size:12px; color:#64748b; margin-top:5px;">
                    <?php esc_html_e( 'Max: 5MB. Formats: PNG, JPG, JPEG.', 'multiple-bank-payment-method' ); ?>
                </p>
            </div>

            <div class="ald-alert-box" style="margin-top:20px;">
                <p class="ald-alert-text">
                    <?php esc_html_e( 'Please pay the total amount and upload your payment receipt to confirm your order.', 'multiple-bank-payment-method' ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /* -----------------------------------------------------------
     *  PROCESS PAYMENT
     * -----------------------------------------------------------*/
    public function process_payment( $order_id ) {
        if ( ! isset( $_POST['mbpm_payment_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mbpm_payment_nonce'] ), 'mbpm_payment_upload' ) ) {
            wc_add_notice( __( 'Security check failed.', 'multiple-bank-payment-method' ), 'error' );
            return;
        }

        if ( ! isset( $_FILES['mbpm_payment_receipt'] ) || empty( $_FILES['mbpm_payment_receipt']['name'] ) ) {
            wc_add_notice( __( 'Please upload a payment receipt.', 'multiple-bank-payment-method' ), 'error' );
            return;
        }

        $banks = $this->get_bank_accounts();
        $selected_bank_index = isset( $_POST['mbpm_selected_bank'] ) ? intval( $_POST['mbpm_selected_bank'] ) : 0;

        if ( count( $banks ) > 1 ) {
            if ( $selected_bank_index === '' || $selected_bank_index < 0 || ! isset( $banks[ $selected_bank_index ] ) ) {
                wc_add_notice( __( 'Please select a bank account.', 'multiple-bank-payment-method' ), 'error' );
                return;
            }
        }

        if ( empty( $banks ) ) {
            wc_add_notice( __( 'No bank accounts are configured.', 'multiple-bank-payment-method' ), 'error' );
            return;
        }

        $allowed_mime_types = array( 'image/jpeg', 'image/png', 'image/jpg' );
        $file_info = wp_check_filetype( basename( $_FILES['mbpm_payment_receipt']['name'] ), null );
        if ( ! in_array( $file_info['type'], $allowed_mime_types ) ) {
            wc_add_notice( __( 'Invalid file type.', 'multiple-bank-payment-method' ), 'error' );
            return;
        }

        if ( $_FILES['mbpm_payment_receipt']['size'] > 5242880 ) {
            wc_add_notice( __( 'File size exceeds 5MB limit.', 'multiple-bank-payment-method' ), 'error' );
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload( $_FILES['mbpm_payment_receipt'], array( 'test_form' => false ) );
        if ( isset( $upload['error'] ) ) {
            wc_add_notice( $upload['error'], 'error' );
            return;
        }

        $attachment_id = wp_insert_attachment( array(
            'guid'           => $upload['url'],
            'post_mime_type' => $upload['type'],
            'post_title'     => basename( $upload['file'] ),
            'post_status'    => 'inherit'
        ), $upload['file'], $order_id );

        if ( ! is_wp_error( $attachment_id ) ) {
            wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
            update_post_meta( $order_id, '_mbpm_payment_receipt_id', $attachment_id );
        }
        update_post_meta( $order_id, '_mbpm_payment_receipt_url', $upload['url'] );

        $selected_bank = $banks[$selected_bank_index];
        update_post_meta( $order_id, '_mbpm_selected_bank_index', $selected_bank_index );
        update_post_meta( $order_id, '_mbpm_selected_bank_name',    sanitize_text_field( $selected_bank['account_name'] ?? '' ) );
        update_post_meta( $order_id, '_mbpm_selected_bank_number',  sanitize_text_field( $selected_bank['account_number'] ?? '' ) );
        update_post_meta( $order_id, '_mbpm_selected_bank_holder',  sanitize_text_field( $selected_bank['account_holder'] ?? '' ) );
        update_post_meta( $order_id, '_mbpm_selected_bank_branch',  sanitize_text_field( $selected_bank['branch_name'] ?? '' ) );
        update_post_meta( $order_id, '_mbpm_selected_bank_routing', sanitize_text_field( $selected_bank['routing_number'] ?? '' ) );
        update_post_meta( $order_id, '_mbpm_selected_bank_swift',   sanitize_text_field( $selected_bank['swift_code'] ?? '' ) );
        update_post_meta( $order_id, '_mbpm_selected_bank_address', sanitize_textarea_field( $selected_bank['bank_address'] ?? '' ) );

        $order = wc_get_order( $order_id );
        $order->update_status( 'on-hold', __( 'Awaiting manual confirmation.', 'multiple-bank-payment-method' ) );
        WC()->cart->empty_cart();

        return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
    }

    /* -----------------------------------------------------------
     *  ADMIN ORDER: DISPLAY RECEIPT
     * -----------------------------------------------------------*/
    public function display_payment_receipt_admin_order( $order ) {
        $receipt_url = get_post_meta( $order->get_id(), '_mbpm_payment_receipt_url', true );
        $receipt_id  = get_post_meta( $order->get_id(), '_mbpm_payment_receipt_id', true );
        $bank_name    = get_post_meta( $order->get_id(), '_mbpm_selected_bank_name',    true );
        $bank_number  = get_post_meta( $order->get_id(), '_mbpm_selected_bank_number',  true );
        $bank_holder  = get_post_meta( $order->get_id(), '_mbpm_selected_bank_holder',  true );
        $bank_branch  = get_post_meta( $order->get_id(), '_mbpm_selected_bank_branch',  true );
        $bank_routing = get_post_meta( $order->get_id(), '_mbpm_selected_bank_routing', true );
        $bank_swift   = get_post_meta( $order->get_id(), '_mbpm_selected_bank_swift',   true );
        $bank_address = get_post_meta( $order->get_id(), '_mbpm_selected_bank_address', true );

        echo '<div class="ald-admin-receipt-box" style="margin-top:20px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; overflow:hidden; max-width:380px;">';

            if ( $bank_name ) {
                echo '<div style="padding:14px; background:#f1f5f9; border-bottom:1px solid #cbd5e1; font-weight:700; color:#334155; font-size:15px;">';
                    echo esc_html__( 'Paid via Bank', 'multiple-bank-payment-method' );
                echo '</div>';
                echo '<table style="width:100%;border-collapse:collapse;padding:12px 15px;">';
                if ( $bank_name )    echo '<tr><td style="padding:6px 8px;font-weight:600;color:#64748b;font-size:13px;">' . esc_html__( 'Bank', 'multiple-bank-payment-method' ) . '</td><td style="padding:6px 8px;font-weight:600;color:#1e293b;font-size:14px;">' . esc_html( $bank_name ) . '</td></tr>';
                if ( $bank_number )  echo '<tr><td style="padding:6px 8px;font-weight:600;color:#64748b;font-size:13px;">' . esc_html__( 'A/C Number', 'multiple-bank-payment-method' ) . '</td><td style="padding:6px 8px;font-weight:600;color:#1e293b;font-size:14px;">' . esc_html( $bank_number ) . '</td></tr>';
                if ( $bank_holder )  echo '<tr><td style="padding:6px 8px;font-weight:600;color:#64748b;font-size:13px;">' . esc_html__( 'A/C Holder', 'multiple-bank-payment-method' ) . '</td><td style="padding:6px 8px;font-weight:600;color:#1e293b;font-size:14px;">' . esc_html( $bank_holder ) . '</td></tr>';
                if ( $bank_branch )  echo '<tr><td style="padding:6px 8px;font-weight:600;color:#64748b;font-size:13px;">' . esc_html__( 'Branch', 'multiple-bank-payment-method' ) . '</td><td style="padding:6px 8px;font-weight:600;color:#1e293b;font-size:14px;">' . esc_html( $bank_branch ) . '</td></tr>';
                if ( $bank_routing ) echo '<tr><td style="padding:6px 8px;font-weight:600;color:#64748b;font-size:13px;">' . esc_html__( 'Routing', 'multiple-bank-payment-method' ) . '</td><td style="padding:6px 8px;font-weight:600;color:#1e293b;font-size:14px;">' . esc_html( $bank_routing ) . '</td></tr>';
                if ( $bank_swift )   echo '<tr><td style="padding:6px 8px;font-weight:600;color:#64748b;font-size:13px;">' . esc_html__( 'SWIFT Code', 'multiple-bank-payment-method' ) . '</td><td style="padding:6px 8px;font-weight:600;color:#1e293b;font-size:14px;">' . esc_html( $bank_swift ) . '</td></tr>';
                if ( $bank_address ) echo '<tr><td style="padding:6px 8px;font-weight:600;color:#64748b;font-size:13px;">' . esc_html__( 'Address', 'multiple-bank-payment-method' ) . '</td><td style="padding:6px 8px;font-weight:600;color:#1e293b;font-size:14px;">' . esc_html( $bank_address ) . '</td></tr>';
                echo '</table>';
            }

            if ( $receipt_url ) {
                echo '<div style="padding:14px; background:#f1f5f9; border-top:1px solid #cbd5e1; font-weight:700; color:#334155; font-size:15px;">';
                    echo esc_html__( 'Payment Receipt Verification', 'multiple-bank-payment-method' );
                echo '</div>';
                echo '<div style="padding:15px; text-align:center; background:#fff;">';
                    echo '<div style="width:100%; height:200px; overflow:hidden; border-radius:4px; border:1px solid #e2e8f0; margin-bottom:15px; display:flex; align-items:center; justify-content:center; background:#f8fafc;">';
                        if ( $receipt_id ) {
                            echo wp_get_attachment_image( $receipt_id, 'medium', false, array( 'style' => 'max-width:100%; height:auto; object-fit:contain;' ) );
                        } else {
                            echo '<img src="' . esc_url( $receipt_url ) . '" style="max-width:100%; height:auto;" alt="Payment Receipt" />';
                        }
                    echo '</div>';
                    echo '<a href="' . esc_url( $receipt_url ) . '" target="_blank" class="button button-primary">' . esc_html__( 'View Full Image', 'multiple-bank-payment-method' ) . '</a>';
                echo '</div>';
            }
        echo '</div>';
    }

    public function add_enctype_to_checkout_form( $form_tag ) {
        if ( strpos( $form_tag, 'enctype=' ) === false ) {
            $form_tag = str_replace( '<form', '<form enctype="multipart/form-data"', $form_tag );
        }
        return $form_tag;
    }
}