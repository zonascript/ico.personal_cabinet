<textarea id="{$id}" name="{$name}" {raw $html}>{$value}</textarea>

<script>
    tinymce.init({
        selector: '#{$id}',
//        language: 'ru',
        plugins: [
            'advlist autolink link image autoresize colorpicker autosave lists charmap print preview hr anchor',
            'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime image imagetools media nonbreaking',
            'save table contextmenu directionality emoticons template paste textcolor textpattern layer contextmenu'
        ],
        content_css: '/static/frontend/dist/css/main.css?t=' + new Date().getTime(),
        relative_urls: false,
        browser_spellcheck : true,
        file_browser_callback: function(field_name, url, type, win) {
            window.file_browser_window = win;
            window.file_browser_field = field_name;
            window.file_browser_url = url;
            window.file_browser_type = type;

            $('<a/>').attr('href', "{url route="editor:index"}?field=" + field_name + "&url=" + url).modal();
            return false;
        },
        images_upload_handler: function(blobInfo, success, failure){
            var xhr, formData;
            xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST','{url route="editor:changed"}');
            xhr.onload = function() {
                var json;
                if (xhr.status != 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }
                json = JSON.parse(xhr.responseText);
                success(json.url);
            };
            formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        }
    });
</script>