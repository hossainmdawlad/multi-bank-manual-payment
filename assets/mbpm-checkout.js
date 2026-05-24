(function($) {
    'use strict';

    /* -----------------------------------------------------------
     *  Localised labels (PHP-in-JS — WordPress checkout pattern)
     * -----------------------------------------------------------*/
    <?php $lbl_account  = esc_js( _x( "Account Number", "checkout label", "multiple-bank-payment-method" ) ); ?>
    <?php $lbl_holder   = esc_js( _x( "Account Holder", "checkout label", "multiple-bank-payment-method" ) ); ?>
    <?php $lbl_branch   = esc_js( _x( "Branch Name",    "checkout label", "multiple-bank-payment-method" ) ); ?>
    <?php $lbl_routing  = esc_js( _x( "Routing Number", "checkout label", "multiple-bank-payment-method" ) ); ?>
    <?php $lbl_select   = esc_js( __( "Select a Bank Account", "multiple-bank-payment-method" ) ); ?>
    <?php $lbl_hint     = esc_js( __( "Please select a bank from the dropdown above to see its payment details.", "multiple-bank-payment-method" ) ); ?>
    <?php $lbl_nodetails= esc_js( __( "No details available for this bank.", "multiple-bank-payment-method" ) ); ?>

    /* -----------------------------------------------------------
     *  Render bank card from the selected <option>'s data-* attrs
     * -----------------------------------------------------------*/
    function updateBankInfo() {
        // Find the wrapper - use multiple selectors for compatibility
        var $wrapper = $('#mbpm-payment-details-wrapper');
        if ( ! $wrapper.length ) {
            // Try alternative selector for block-based checkout
            $wrapper = $('.mbpm-payment-details-wrapper');
        }
        if ( ! $wrapper.length ) return;

        var $select = $wrapper.find('#mbpm_selected_bank');
        var $info   = $wrapper.find('#mbpm-bank-info');

        /* Single-bank mode — no dropdown */
        if ( ! $select.length ) {
            $info.show();
            return;
        }

        /* No real banks configured — hide card */
        if ( ! $select.find('option').not(':first').length ) {
            $info.hide();
            return;
        }

        var idx = $select.val();

        if ( idx === '' || idx === undefined ) {
            $wrapper.find('#mbpm-bank-header').text('<?php echo $lbl_select; ?>');
            $wrapper.find('#mbpm-bank-details').html(
                '<p style="color:#64748b;"><?php echo $lbl_hint; ?></p>'
            );
            $info.show();
            return;
        }

        // Find the selected option element
        var $opt = $select.find('option:selected');
        
        // Use native getAttribute for reliability (works better than jQuery .data())
        var bankName   = $opt.attr('data-name')      || '';
        var bankAcc   = $opt.attr('data-account')    || '';
        var bankHold  = $opt.attr('data-holder')    || '';
        var bankBranch = $opt.attr('data-branch')   || '';
        var bankRoute = $opt.attr('data-routing')    || '';

        $wrapper.find('#mbpm-bank-header').text(bankName);

        var html = '';
        if ( bankAcc   ) html += '<div class="ald-info-row"><span class="ald-label"><?php echo $lbl_account; ?>:</span><span class="ald-value">'  + bankAcc    + '</span></div>';
        if ( bankHold  ) html += '<div class="ald-info-row"><span class="ald-label"><?php echo $lbl_holder; ?>:</span><span class="ald-value">'   + bankHold   + '</span></div>';
        if ( bankBranch) html += '<div class="ald-info-row"><span class="ald-label"><?php echo $lbl_branch; ?>:</span><span class="ald-value">'   + bankBranch + '</span></div>';
        if ( bankRoute ) html += '<div class="ald-info-row"><span class="ald-label"><?php echo $lbl_routing; ?>:</span><span class="ald-value">'  + bankRoute  + '</span></div>';

        if ( ! html ) html = '<p style="color:#64748b;"><?php echo $lbl_nodetails; ?></p>';
        $wrapper.find('#mbpm-bank-details').html(html);
        $info.show();
    }

    /* Bootstrap on pageload - use setTimeout to ensure DOM is ready */
    $(document).ready(function() { 
        setTimeout(updateBankInfo, 100); 
    });
    
    /* Use event delegation for dynamic content (works with WooCommerce AJAX) */
    $(document.body).on('change', '#mbpm_selected_bank', function() {
        updateBankInfo();
    });
    
    /* Also trigger on WooCommerce updated event */
    $(document.body).on('updated_checkout', function() {
        setTimeout(updateBankInfo, 100);
    });

    /* -----------------------------------------------------------
     *  Place-order: validate that receipt was uploaded
     * -----------------------------------------------------------*/
    var uploadMsg = "<?php echo esc_js( __( "Please upload your payment receipt screenshot.", "multiple-bank-payment-method" ) ); ?>";
    $(document).on('click', '#place_order', function(e) {
        var selected = $('input[name="payment_method"]:checked').val();

        if ( selected === mbpm_Data.gatewayId ) {
            var fileInput = $(mbpm_Data.fileInputSelector);

            if ( fileInput.length > 0 && fileInput[0].files.length === 0 ) {
                alert(uploadMsg);
                e.preventDefault();
                return false;
            }

            /* Disable AJAX so multipart file upload works reliably */
            $('form.checkout').off('submit');
            $('form.checkout').removeClass('processing').unblock();
            $('form.checkout').attr('enctype', 'multipart/form-data');

            return true;
        }
    });

    /* -----------------------------------------------------------
     *  File-size validation (5 MB)
     * -----------------------------------------------------------*/
    $(document.body).on('change', mbpm_Data.fileInputSelector, function(e) {
        var file = e.target.files[0];
        if ( file && file.size > 5242880 ) {
            alert(mbpm_Data.fileSizeError);
            e.target.value = "";
        }
    });

}(jQuery));

