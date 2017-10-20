{extends 'base/admin.tpl'}

{block 'content'}

    {smarty_admin_block name='Brands search'}
        <form action="{url 'brand:brand_list'}" method="get" name="search_brand">
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
        <div class="row">
            <div class="columns large-12">
                {include 'base/char_navigation.tpl' url='brand:brand_list' selected=$letter}
            </div>
        </div>
        <div class="row">
            <div class="columns large-12">
                {raw $pager}
            </div>
        </div>
        <table width="100%" cellspacing="1" cellpadding="3">
            <tr class="TableHead">
                <td width="1%"></td>
                <td width="40%">Brand</td>
                <td width="20%" align="center">Products</td>
                <td width="20%" align="center">Child brands</td>
                <td width="7%" align="center"></td>
            </tr>
            {if $brands}
                {foreach $brands as $brand}
                    {include 'brand/_brand_group.tpl' brand=$brand index=$brand@index}
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
    {/smarty_admin_block}

{/block}

{block 'js'}
    {parent}
    <script type="text/javascript">
        $('a.add-group').click(function () {
            var w = 970;
            var h = 800;
            var url = '{url 'brand:brand_group_index'}';
            var wLeft = window.screenLeft ? window.screenLeft : window.screenX;
            var wTop = window.screenTop ? window.screenTop : window.screenY;

            var left = wLeft + (window.innerWidth / 2) - (w / 2);
            var top = wTop + (window.innerHeight / 2) - (h / 2);
            var strWindowFeatures = 'menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes,width=' + w + ',height=' + h + ', top=' + top + ', left=' + left;
            window.open(url + '/' + $(this).closest('tr').data('brand-id'), "Brands Grouping", strWindowFeatures);
            return false;
        });

        $('a.brands_more').on('click', function(e){
            $(this).prev('div.brands_overflow').removeClass('brands_overflow').end().remove();
            e.preventDefault();
        });
    </script>
{/block}