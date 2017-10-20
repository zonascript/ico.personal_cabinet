{if $this.getPagesCount() > 1 }
    <section class="pager-container">
        <ul class="pager">
            {if $this.hasPrevPage() }
                <li class="prev">
                    <a href="{$this.getUrl($this.currentPage - 1)}" class="link">
                        <span class="inner">&larr;</span>
                    </a>
                </li>
            {/if}

            {if $this.hasPrevPage()}
                {foreach $this.iterPrevPage() as $page }
                    <li>
                        <a href="{$this.getUrl($page)}" class="link">
                            <span class="inner">
                                {$page}
                            </span>
                        </a>
                    </li>
                {/foreach}
            {/if}

            <li class="selected">
                <span class="link">
                    <span class="inner">
                        {$this.currentPage}
                    </span>
                </span>
            </li>

            {if $this.hasNextPage()}
                {foreach  $this.iterNextPage() as $page}
                    <li>
                        <a href="{$this.getUrl($page)}" class="link">
                            <span class="inner">
                                {$page}
                            </span>
                        </a>
                    </li>
                {/foreach}
            {/if}

            {if $this.hasNextPage}
                <li class="next">
                    <a href="{$this.getUrl($this.currentPage+1)}" class="link">
                        <span class="inner">
                            &rarr;
                        </span>
                    </a>
                </li>
            {/if}
        </ul>
    </section>
{/if}