const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;

// Liste der Widgets, die als Blöcke registriert werden sollen
const widgets = [
    { name: 'mp_cart_widget', title: 'Shopping Cart' },
    { name: 'mp_categories_widget', title: 'Product Categories' },
    { name: 'mp_product_list_widget', title: 'Product List' },
    { name: 'mp_tag_cloud_widget', title: 'Product Tag Cloud' },
    { name: 'mp_global_product_list_widget', title: 'Global Product List' },
    { name: 'mp_global_category_list_widget', title: 'Global Product Categories' },
    { name: 'mp_global_tag_cloud_widget', title: 'Global Product Tag Cloud' },
];

widgets.forEach((widget) => {
    registerBlockType(`mp-plugin/${widget.name}`, {
        title: widget.title,
        icon: 'screenoptions',
        category: 'widgets',
        edit: () => {
            const { createElement } = wp.element;
            return createElement(
                'p',
                null,
                __('This widget will be rendered on the frontend.', 'mp')
            );
        },
        save: () => null, // Serverseitiges Rendering
    });
});

//console.log('mp-widgets-to-blocks.js wurde geladen');