<fieldset class="expanded">
    <legend>
        Excluded marketplaces
    </legend>
    <ul class="ul-main">
        <li>
            <div class="row">
                <div class="columns large-3">
                    <label for="o_disabled_marketplace">Excluded marketplaces:</label>
                </div>
                <div class="columns large-7">
                    <select id="o_disabled_marketplace" multiple class="select2"
                            name="excluded_marketplaces[]">
                        {foreach $aExternalMarketplaces as $market}
                            <option label="{raw $market->getMarketPlaceName()}" value="{$market->getMarketPlaceId()}"
                                    {if $market->getMarketPlaceId() in list $aDisabledMarketPlaces}selected{/if}>{raw $market->getMarketPlaceName()}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </li>
    </ul>
</fieldset>