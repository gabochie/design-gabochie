
// Get mwp_settings or use default empty object
const mwp_settings = window.wc?.wcSettings?.getSetting('mpwp_moolre_payment_gateway_data', {}) || {};
const label = window.wp.htmlEntities.decodeEntities(mwp_settings.title || 'Moolre Payment Gateway for WooCommerce');
const description = mwp_settings.description ? window.wp.htmlEntities.decodeEntities(mwp_settings.description) : '';

const Content = () => {
    return '';
    // return description ? description : window.wp.i18n.__('Pay securely using your Mobile Money Wallet.', 'mpwp_moolre_payment_gateway');
};

const labelWithIcon = wp.element.createElement('span', null, 
    wp.element.createElement('img', {
        src: mwp_settings.icon,
        alt: 'Moolre Icon',
        style: { width: '100%', height: '100%', margin: '0px' }
    }),
    ''// title string
);

const Block_Gateway = {
    name: 'mpwp_moolre_payment_gateway',
    label: labelWithIcon,
    content: window.wp.element.createElement(Content, null),
    edit: window.wp.element.createElement(Content, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: mwp_settings.supports || ['products'],
    },
};

// Register the payment method, ensure registry is accessible
if (window.wc?.wcBlocksRegistry?.registerPaymentMethod) {
    window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
} else {
    console.error('Payment registry is unavailable. Verify that wcBlocksRegistry is loaded.');
}
