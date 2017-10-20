{extends 'brand/layout/group_layout.tpl'}

{block 'content'}
    {smarty_admin_block name='Brands search'}
        <form action="{url 'brand:brand_group' id = $parent->brandid}" method="get" name="search_brand">
            <table width="100%" cellspacing="1" cellpadding="3">
                <tbody>
                <tr>
                    <td width="250">
                        <input style="width: 250px;" name="search" value="{$search}" type="text">
                    </td>
                    <td><input value="Search" type="submit"></td>
                </tr>
                </tbody>
            </table>
        </form>
    {/smarty_admin_block}

    {smarty_admin_block name='Brands list'}
        <form action="" method="post">
            <div class="row">
                <div class="columns large-12">
                    {raw $pager}
                </div>
            </div>
            <table width="100%" cellspacing="1" cellpadding="3">
                <tr class="TableHead">
                    <td width="1%"><input id="check_box_all" type="checkbox" title="Check/uncheck all" /></td>
                    <td width="40%">Brand</td>
                    <td width="20%" align="center">Products</td>
                    <td width="20%" align="center">Child brands</td>
                    <td width="7%" align="center"></td>
                </tr>
                {if $brands}
                    {foreach $brands as $brand}
                        {include 'brand/_brand_group.tpl' brand=$brand index=$brand@index parent=$parent}
                    {/foreach}
                {else}
                    <tr><td align="center" colspan="5">No data found</td></tr>
                {/if}
            </table>
            <div class="row">
                <div class="columns large-12">
                    {raw $pager}
                </div>
            </div>
            {include 'brand/form/buttons.tpl'}
        </form>
    {/smarty_admin_block}

{/block}

{block 'js'}
    {parent}

    <script>
        $('.button[name=group]').on('click', function(e){
            e.preventDefault();
            $('.brand-list').css('opacity', 0.4);
            var url_group = '{url 'brand:brand_group' id = $parent->brandid}';
            $.ajax({
                type: 'POST',
                url: url_group,
                data: $(this).closest('form').serialize(),
                success: function (data) {
                    if (data) {
                        window.close();
                        window.opener.location.reload();
                    }
                },
                error: function () {
                }
            });
        });

        $('#check_box_all').on('change', function(){
            var checkBoxes = $("input:checkbox").not(this);
            checkBoxes.prop("checked", !checkBoxes.prop("checked"));
        });

        $('a.brands_more').on('click', function(e){
            $(this).prev('div.brands_overflow').removeClass('brands_overflow').end().remove();
            e.preventDefault();
        });

    </script>

{/block}