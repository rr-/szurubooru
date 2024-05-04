<form class='horizontal'>
    <ul class='metric-list'></ul>
    <wbr>
    <%= ctx.makeCheckbox({
        text: 'Show values on posts',
        name: 'show-values-on-posts',
        checked: ctx.showValuesOnPost,
        class: 'append'}) %>
    <a class='mousetrap button append close sorting'
       href="<%= ctx.getMetricSorterUrl('random', ctx.parameters) %>">Start sorting</a>
</form>
