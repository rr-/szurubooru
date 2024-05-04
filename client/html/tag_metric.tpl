<div class='tag-metric'>
    <form class='horizontal edit-metric'>
        <div class='metric-bounds-edit'>
            <%= ctx.makeNumericInput({
                text: 'Minimum',
                name: 'metric-min',
                value: ctx.metricMin,
                step: 'any',
                readonly: !ctx.canEditMetricBounds,
            }) %>
            <%= ctx.makeNumericInput({
                text: 'Maximum',
                name: 'metric-max',
                value: ctx.metricMax,
                step: 'any',
                readonly: !ctx.canEditMetricBounds,
            }) %>
        </div>

        <% if (ctx.tag.metric && ctx.canDeleteMetric) { %>
        <div class='confirmation'>
            <%= ctx.makeCheckbox({name: 'confirm-delete',
                text: 'I confirm that I want to delete this metric.'}) %>
        </div>
        <% } %>

        <div class='messages'></div>

        <div class='buttons'><!--
            --><% if (!ctx.tag.metric && ctx.canCreateMetric) { %><!--
                --><input type='submit' value='Create metric'/><!--
            --><% } else if (ctx.tag.metric && ctx.canEditMetricBounds) { %><!--
                --><input type='submit' value='Update metric'/><!--
            --><% } %><!--
            --><% if (ctx.tag.metric && ctx.canDeleteMetric) { %><!--
                --><input type='button' name='delete' class='delete' value='Delete metric'/><!--
            --><% } %>
        </div>
    </form>
</div>
