define(['core/modal_factory', 'core/modal_events', 'core/notification'], function(ModalFactory, ModalEvents, Notification) {
    const init = (config) => {
        const buttons = document.querySelectorAll('[data-firma-preview]');
        if (!buttons.length) {
            return;
        }

        let modalPromise = null;

        const getModal = () => {
            if (!modalPromise) {
                modalPromise = ModalFactory.create({
                    type: ModalFactory.types.CANCEL,
                    title: config.title || ''
                }).then((modal) => {
                    modal.getRoot().on(ModalEvents.hidden, () => {
                        modal.setBody('');
                    });
                    return modal;
                });
            }
            return modalPromise;
        };

        buttons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const url = button.getAttribute('data-firma-preview');
                if (!url) {
                    return;
                }
                getModal().then((modal) => {
                    modal.setTitle(config.title || '');
                    modal.setBody(`<div class="local-firma-preview-frame"><iframe src="${url}" title="${config.title}" frameborder="0"></iframe></div>`);
                    modal.show();
                }).catch(Notification.exception);
            });
        });
    };

    return { init };
});
