var config = {
    paths: {
        'clipboard': 'IoPay_Core/js/util/clipboard',
    },
    shim: {
        'clipboard': {
            exports: 'ClipboardJS',
            deps: ['jquery']
        },
    },
};
