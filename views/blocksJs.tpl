(function() {
    "use strict";

    const React = window.React;
    const { __ } = window.wp.i18n;
    const wcBlocksRegistry = window.wc.wcBlocksRegistry;
    const htmlEntities = window.wp.htmlEntities;
    const wcSettings = window.wc.wcSettings;

    const altapayData = wcSettings.getSetting("altapay_{terminal_id}_data", {});
    const defaultTitle = __("{name}", "woo-gutenberg-products-block");
    const decodedTitle = htmlEntities.decodeEntities(altapayData.title) || defaultTitle;

    const getDescription = () => {
        return htmlEntities.decodeEntities(altapayData.description || "");
    };

    const altapayPaymentMethod = {
        name: "altapay_{terminal_id}",
        label: React.createElement((props) => {
            const { PaymentMethodLabel } = props.components;
            return React.createElement(PaymentMethodLabel, { text: decodedTitle });
        }, null),
        content: React.createElement(getDescription, null),
        edit: React.createElement(getDescription, null),
        canMakePayment: () => true,
        ariaLabel: decodedTitle,
        supports: { features: altapayData.supports }
    };

    wcBlocksRegistry.registerPaymentMethod(altapayPaymentMethod);
})();