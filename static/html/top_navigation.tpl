<nav id='top-nav' class='text-nav'>
    <ul><!--
        -->{{#each items}}<!--
            -->{{#if this.available}}<!--
                --><li data-name='{{@key}}'><!--
                    --><a href='{{this.url}}'>{{this.name}}</a><!--
                --></li><!--
            -->{{/if}}<!--
        -->{{/each}}<!--
    --></ul>
</nav>
