try {
    const origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        this.addEventListener('load', function() {
            // Temporary catch for displaying messages from server START
            // let response;
            // if (this.responseType != 'json') response = JSON.parse(this.responseText)
            // else response = this.response
            // // ========================================================
            // if( this.readyState == 4 && this.status < 300 ) {
            //     if (response?.status?.includes('error')){
            //         if (response.response?.message[0]?.text) return polipop.add({ content: `${response.response.message[0].text}`, title: `Ошибка`, type: 'error'});
            //         if (response.message) return polipop.add({ content: `${response.message}`, title: `Ошибка`, type: 'error'});
            //         if (!response.message && response.info) return polipop.add({ content: `${response.info}`, title: `Ошибка`, type: 'error'});
            //         if (!response.message && !response.info) return polipop.add({ content: `${response.status}`, title: `Ошибка`, type: 'error'});
            //     }
            // }
            // Temporary catch for displaying messages from server END


            if( this.status > 299 && this.readyState == 4) {
                if (this.status == 500) {
                    console.log(i18n.internal_error)
                    polipop.add({ content: i18n.internal_error, title: `${i18n.error} ${this.status}`, type: 'error'});
                    return
                }
                polipop.add({ content: `${this.statusText}`, title: `Ошибка ${this.status}`, type: 'error'});
            }
        });
        this.addEventListener('error', function(e) {
            polipop.add({ content: `xmlHTTP Error`, title: `Ошибка`, type: 'error'});
        });

    origOpen.apply(this, arguments);
  };
} catch (error) {
  console.error('Request interceptor error:' + error);
}
