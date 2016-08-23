<li class='uploadable'>
    <a href class='remove'>
        <i class='fa fa-remove'></i>
    </a>

    <div class='thumbnail'>
        <% if (['image'].includes(ctx.uploadable.type)) { %>

            <%= ctx.makeThumbnail(ctx.uploadable.imageUrl) %>

        <% } else { %>

            <%= ctx.makeThumbnail(null) %>

        <% } %>
    </div>

    <div class='file'>
        <strong><%= ctx.uploadable.name %></strong>
    </div>

    <div class='safety'>
        <% for (let safety of ['safe', 'sketchy', 'unsafe']) { %>
            <%= ctx.makeRadio({
                name: 'safety-' + ctx.uploadable.key,
                value: safety,
                text: safety[0].toUpperCase() + safety.substr(1),
                selectedValue: ctx.uploadable.safety,
            }) %>
        <% } %>
    </div>

    <% if (ctx.canUploadAnonymously) { %>
        <div class='anonymous'>
            <%= ctx.makeCheckbox({
                text: 'Upload anonymously',
                name: 'anonymous',
                checked: ctx.uploadable.anonymous,
            }) %>
        </div>
    <% } %>
</li>
