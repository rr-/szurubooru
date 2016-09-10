<li class='uploadable'>
    <div class='controls'>
        <a href class='move-up'><i class='fa fa-chevron-up'></i></a>
        <a href class='move-down'><i class='fa fa-chevron-down'></i></a>
        <a href class='remove'><i class='fa fa-remove'></i></a>
    </div>

    <div class='thumbnail-wrapper'>
        <% if (['image'].includes(ctx.uploadable.type)) { %>

            <a href='<%= ctx.uploadable.previewUrl %>'>
                <%= ctx.makeThumbnail(ctx.uploadable.previewUrl) %>
            </a>

        <% } else if (['video'].includes(ctx.uploadable.type)) { %>

            <div class='thumbnail'>
                <a href='<%= ctx.uploadable.previewUrl %>'>
                    <video id='video' nocontrols muted>
                        <source type='<%- ctx.uploadable.mimeType %>' src='<%- ctx.uploadable.previewUrl %>'/>
                    </video>
                </a>
            </div>

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
