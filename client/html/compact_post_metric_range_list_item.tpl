<li><!--
--><% if (ctx.editMode) { %><!--
    --><a class='remove-metric' data-pseudo-content='×'/><!--
    --><a href="<%- ctx.formatClientLink('posts', {
                query: 'metric-' + ctx.escapeTagName(ctx.tag.names[0]) +
                    ':' + ctx.tag.metric.min + '..' + ctx.tag.metric.max +
                    ' sort:metric-' + ctx.escapeTagName(ctx.tag.names[0])
                }) %>"
          class="<%= ctx.makeCssName(ctx.tag.category, 'tag') %>"><!--
        --><i class='fas fa-arrows-alt-h tag-icon'></i><!--
        --><%- ctx.postMetricRange.tagName %>:</a><!--
    --><%= ctx.makeNumericInput({
           name: 'low',
           value: ctx.postMetricRange.low,
           step: 'any',
           min: ctx.tag.metric.min,
           max: ctx.tag.metric.max,
       }) %><!--
    --><span class='range-delimiter'>&mdash;</span><!--
    --><%= ctx.makeNumericInput({
        name: 'high',
        value: ctx.postMetricRange.high,
        step: 'any',
        min: ctx.tag.metric.min,
        max: ctx.tag.metric.max,
        }) %><!--
--><% } else { %><!--
    --><a href="<%- ctx.formatClientLink('tag', ctx.tag.names[0]) %>"
          class="<%= ctx.makeCssName(ctx.tag.category, 'tag') %>"><!--
        --><i class='fas fa-arrows-alt-h tag-icon'></i><!--
    --></a><!--
    --><a href="<%- ctx.formatClientLink('posts', {
                query: 'metric-' + ctx.escapeTagName(ctx.tag.names[0]) +
                    ':' + ctx.tag.metric.min + '..' + ctx.tag.metric.max +
                    ' sort:metric-' + ctx.escapeTagName(ctx.tag.names[0])
                }) %>"
          class="<%= ctx.makeCssName(ctx.tag.category, 'tag') %>"><!--
        --><%- ctx.postMetricRange.tagName %>:
        <%- ctx.postMetricRange.low || 0 %> &mdash; <%- ctx.postMetricRange.high || 0 %><!--
    --></a><!--
--><% } %><!--
--></li>
