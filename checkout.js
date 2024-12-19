(() => {
    const { createElement, useEffect, useRef } = window.wp.element;
    const { decodeEntities } = window.wp.htmlEntities;
    const { getSetting } = window.wc.wcSettings;

    const settings = getSetting('mia_pos_data', {});
    const label = decodeEntities(settings.title) || window.wp.i18n.__('Mia Payment Gateway', 'mia');

    const injectHTML = (container, html) => {
        if (container) {
            container.innerHTML = html;
        }
    };

    const Content = () => {
        const description = decodeEntities(settings.description || '');
        const iconUrl = decodeEntities(settings.icon || '');

        const contentHtml = `
            ${description}
            ${iconUrl ? `<img src="${iconUrl}" alt="Payment Gateway Icon" style="display: block; margin: 10px 0 0;" />` : ''}
        `;

        // Use a reference to inject HTML after rendering
        const contentRef = useRef(null);

        useEffect(() => {
            if (contentRef.current) {
                injectHTML(contentRef.current, contentHtml);
            }
        }, [contentHtml]);

        return createElement('span', { ref: contentRef });
    };

    const MiaPosGateway = {
        name: 'mia_pos',
        label: label,
        content: createElement(Content),
        edit: createElement(Content),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: [
                'products',
                'refunds',
                'checkout',
                '__experimentalDestructureToBlocksCheckout',
            ],
        },
        paymentMethodId: 'mia_pos'
    };

    window.wc.wcBlocksRegistry.registerPaymentMethod(MiaPosGateway);
})();
