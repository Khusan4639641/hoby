<script>
    Vue.directive('tinymce', {
        bind(el) {
            tinymce.init({
                target: el,
                theme: 'modern',
                setup: function (editor) {
                    editor.on('change', function () {
                        tinymce.triggerSave();
                    });
                }
            })
        }
    });
</script>
