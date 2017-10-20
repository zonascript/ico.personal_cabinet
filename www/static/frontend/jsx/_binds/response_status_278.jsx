$(document).ajaxComplete(function (e, xhr, settings) {
    if (xhr.status == 278) {
        let location = xhr.getResponseHeader("Location");
        if (location) {
            // if (window['loader'] && window['loader']['load']) {
            //     window.loader.load();
            // }
            window.location.href = xhr.getResponseHeader("Location");
        }
    }
});