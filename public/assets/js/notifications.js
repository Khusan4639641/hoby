let polipop;
        polipop = new Polipop('notification_container', {
        layout: 'popups',
        insert: 'before',
        sticky: false,
        hideEmpty: true,
        closer: false,
        closeText: 'Закрыть',
        loadMoreText: 'Показать еще',
        effect: 'slide',
        progressbar: true,
        life: 4000,
        beforeOpen: function (notification, element) {
            let svg_icons = {
                success: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.06495 11.757L11.438 14.129L15.695 9.87101M19.998 11.999C19.998 16.4162 16.4171 19.997 12 19.997C7.58278 19.997 4.00195 16.4162 4.00195 11.999C4.00195 7.58183 7.58278 4.00101 12 4.00101C16.4171 4.00101 19.998 7.58183 19.998 11.999Z" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `,
                error: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.14195 8.97501L15.192 15.025M9.14195 15.025L15.192 8.97501M19.998 11.999C19.998 16.4162 16.4171 19.997 12 19.997C7.58278 19.997 4.00195 16.4162 4.00195 11.999C4.00195 7.58183 7.58278 4.00101 12 4.00101C16.4171 4.00101 19.998 7.58183 19.998 11.999Z" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `,
                warning: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.999 8.7793V12.1373M11.999 15.2207L12.0009 15.2178M19.998 11.999C19.998 16.4162 16.4171 19.997 12 19.997C7.58278 19.997 4.00195 16.4162 4.00195 11.999C4.00195 7.58183 7.58278 4.00101 12 4.00101C16.4171 4.00101 19.998 7.58183 19.998 11.999Z" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `,
                info: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.3907 11.8428H12.0017V15.1998M12.0001 8.80372L12.0019 8.80078M19.9999 11.998C19.9999 16.4152 16.4191 19.996 12.0019 19.996C7.58473 19.996 4.00391 16.4152 4.00391 11.998C4.00391 7.58083 7.58473 4 12.0019 4C16.4191 4 19.9999 7.58083 19.9999 11.998Z" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `,
                close:`
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6.66699 6.646L17.333 17.31M6.66699 17.31L17.333 6.646" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `
            }
            $(element).find('.polipop__notification-close').html(svg_icons['close'])
            $(element).find('.polipop__notification-icon-inner').html(notification.type === 'default' ? svg_icons['info'] : svg_icons[notification.type])
        }
    });


