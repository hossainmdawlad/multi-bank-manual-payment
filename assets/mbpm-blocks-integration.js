/**
 * Block-based Checkout Integration for Multiple Bank Payment Method
 * Registers the payment method with WooCommerce Blocks
 */
( function () {
    'use strict';

    // Only run if WooCommerce Blocks registry is available
    if ( typeof wcBlocksRegistry === 'undefined' ) {
        return;
    }

    const { registerPaymentMethod } = wcBlocksRegistry;

    // Get payment method settings from localized data
    const settings = window.wc.wcSettings.getSetting( 'bangladeshi_bank_payment_data' );

    if ( ! settings ) {
        return;
    }

    // Create label component
    const Label = function () {
        return wp.element.createElement(
            'div',
            { className: 'mbpm-blocks-label' },
            wp.element.createElement(
                'span',
                { className: 'title' },
                settings.title
            )
        );
    };

    // Create payment form content component
    const Content = function () {
        const [ selectedBank, setSelectedBank ] = React.useState( '' );
        const [ receiptUploaded, setReceiptUploaded ] = React.useState( false );

        const banks = settings.banks || [];

        // Handle bank selection
        const handleBankChange = function ( event ) {
            setSelectedBank( event.target.value );
            // Dispatch event for existing JS to handle
            const event = new CustomEvent( 'mbpm:bank-selected', {
                detail: { bankIndex: event.target.value }
            } );
            document.dispatchEvent( event );
        };

        // Handle file selection
        const handleFileChange = function ( event ) {
            const file = event.target.files[ 0 ];
            if ( file ) {
                setReceiptUploaded( true );
            }
        };

        // Single bank - show directly, multiple banks - show dropdown
        if ( banks.length === 1 ) {
            const bank = banks[ 0 ];
            return wp.element.createElement(
                'div',
                { className: 'mbpm-blocks-single-bank' },
                wp.element.createElement(
                    'div',
                    { className: 'bank-details' },
                    wp.element.createElement( 'strong', null, bank.account_name ),
                    wp.element.createElement( 'div', { className: 'account-number' }, bank.account_number ),
                    bank.account_holder ?
                        wp.element.createElement( 'div', { className: 'account-holder' }, bank.account_holder ) :
                        null,
                    bank.branch_name ?
                        wp.element.createElement( 'div', { className: 'branch_name' }, bank.branch_name ) :
                        null
                ),
                wp.element.createElement(
                    'div',
                    { className: 'receipt-upload' },
                    wp.element.createElement( 'label', null, 'Upload Payment Receipt *' ),
                    wp.element.createElement( 'input', {
                        type: 'file',
                        accept: 'image/png,image/jpeg,image/jpg',
                        onChange: handleFileChange,
                        required: true
                    } ),
                    wp.element.createElement(
                        'p',
                        { className: 'help-text' },
                        'Max: 5MB. Formats: PNG, JPG, JPEG.'
                    )
                ),
                wp.element.createElement(
                    'p',
                    { className: 'instructions' },
                    'Please upload your payment receipt to confirm your order.'
                )
            );
        }

        // Multiple banks - show dropdown
        return wp.element.createElement(
            'div',
            { className: 'mbpm-blocks-multiple-bank' },
            wp.element.createElement(
                'div',
                { className: 'bank-selector' },
                wp.element.createElement( 'label', { htmlFor: 'mbpm_blocks_bank' }, 'Select your bank:' ),
                wp.element.createElement(
                    'select',
                    {
                        id: 'mbpm_blocks_bank',
                        onChange: handleBankChange,
                        required: true
                    },
                    wp.element.createElement(
                        'option',
                        { value: '' },
                        '— Choose a bank —'
                    ),
                    banks.map( function ( bank, index ) {
                        return wp.element.createElement(
                            'option',
                            { value: index },
                            bank.account_name + ' — ' + bank.account_number
                        );
                    } )
                )
            ),
            selectedBank !== '' ? wp.element.createElement(
                'div',
                { className: 'selected-bank-details' },
                wp.element.createElement( 'strong', null, banks[ selectedBank ].account_name ),
                wp.element.createElement( 'div', null, banks[ selectedBank ].account_number )
            ) : null,
            wp.element.createElement(
                'div',
                { className: 'receipt-upload' },
                wp.element.createElement( 'label', null, 'Upload Payment Receipt *' ),
                wp.element.createElement( 'input', {
                    type: 'file',
                    accept: 'image/png,image/jpeg,image/jpg',
                    onChange: handleFileChange,
                    required: true
                } ),
                wp.element.createElement(
                    'p',
                    { className: 'help-text' },
                    'Max: 5MB. Formats: PNG, JPG, JPEG.'
                )
            ),
            wp.element.createElement(
                'p',
                { className: 'instructions' },
                'Please pay the total amount and upload your payment receipt to confirm your order.'
            )
        );
    }();

    // Edit component (for admin settings in block editor)
    const Edit = function () {
        return wp.element.createElement(
            'div',
            { className: 'mbpm-blocks-edit' },
            wp.element.createElement(
                'p',
                null,
                'This payment method allows customers to pay using bank transfer and upload a payment receipt.'
            ),
            wp.element.createElement(
                'p',
                { className: 'note' },
                'Configure bank accounts in WooCommerce → Settings → Payments → Bangladeshi Bank Payment Method.'
            )
        );
    }();

    // Register the payment method with WooCommerce Blocks
    registerPaymentMethod( {
        name: 'bangladeshi_bank_payment',
        label: wp.element.createElement( Label ),
        content: wp.element.createElement( Content ),
        edit: wp.element.createElement( Edit ),
        canMakePayment: function () {
            // Check if banks are configured
            const banks = settings.banks || [];
            return banks.length > 0;
        },
        ariaLabel: settings.title || 'Bangladeshi Bank Payment',
        supports: {
            features: settings.supports || [ 'products' ]
        }
    } );

} )();
