<div class="file-manager" id="zone">
    <div class="row">
        <div class="column large-12">
            <div class="drop-info" id="select">
                <p class="info">Перетащите файлы сюда или нажмите для загрузки</p>
                <div class="progress">
                    <div class="meter progress_bar" id="progress_bar"></div>
                </div>
            </div>

            <div class="row actions-row manage">
                <div class="large-4 column">
                    <a class="button expand remove-selected" href="#">
                        Удалить выбранные файлы
                    </a>
                </div>
                <div class="large-8 column create-column">
                    <input class="create-folder-input" id="folderName" type="text" placeholder="Имя папки"/>
                    <button class="create-folder-button button expand">
                        Создать папку
                    </button>
                </div>
            </div>

            <div class="messages"></div>

            <table class="files">
                <tbody>
                {if $upFolder}
                    <tr class="dir">
                        <td class="file-check">

                        </td>
                        <td class="file-icon">
                            <i class="icon-folder"></i>
                        </td>
                        <td class="file-name">
                            <a href="#" class="file-link" data-path="{$upFolder}">
                                &larr;
                            </a>
                        </td>
                        <td class="file-actions"></td>
                    </tr>
                {/if}
                {foreach $structure['directories'] as $directory}
                    <tr class="dir">
                        <td class="file-check">
                            <input class="delete-checker" type="checkbox" name="deleteFiles[]"
                                   value="{$directory['path']}"/>
                        </td>
                        <td class="file-icon">
                            <i class="icon-folder"></i>
                        </td>
                        <td class="file-name">
                            <a href="#" class="file-link" data-path="{$directory['path']}" data-url="{$directory['url']}">
                                {$directory['name']}
                            </a>
                        </td>
                        <td class="file-actions">
                            <a href="#" class="delete-link" data-path="{$directory['path']}">
                                <i class="icon-delete_in_filter"></i>
                            </a>
                        </td>
                    </tr>
                {/foreach}
                {foreach $structure['files'] as $file}
                    <tr class="file">
                        <td class="file-check">
                            <input class="delete-checker" type="checkbox" name="files[]" value="{$file['path']}"/>
                        </td>
                        <td class="file-icon">
                            <i class="icon-file"></i>
                        </td>
                        <td class="file-name">
                            <a href="#" class="file-link" data-path="{$file['path']}" data-url="{$file['url']}">
                                <span class="name">
                                    {$file['name']}
                                </span>
                                {if $file['name']|is_image}
                                    <span class="image-preview-wrapper">
                                        <span class="image-preview">
                                            <span class="image">
                                                <img src="{$file['url']}" alt="">
                                            </span>
                                        </span>
                                    </span>
                                {/if}
                            </a>
                        </td>
                        <td class="file-actions">
                            <a href="#" class="delete-link" data-path="{$file['path']}">
                                <i class="icon-delete_in_filter"></i>
                            </a>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $('.file-manager').fileapi({
        field: '{$field}',
        startPath: '{$path}',
        uploadUrl: '{url route='editor:upload'}',
        listUrl: '{url route='editor:index'}',
        apiUrl: '{url route='editor:api'}',
        prevention: 'Вы действительно хотите удалить данный файл?'
    });
</script>