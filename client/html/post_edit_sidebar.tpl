<div class='edit-sidebar'>
    <form autocomplete='off'>
        <% if (ctx.canEditPostSafety) { %>
            <section class='safety'>
                <label>Safety</label>
                <%= ctx.makeRadio({
                    name: 'safety',
                    class: 'safety-safe',
                    value: 'safe',
                    selectedValue: ctx.post.safety,
                    text: 'Safe'}) %>
                <%= ctx.makeRadio({
                    name: 'safety',
                    class: 'safety-sketchy',
                    value: 'sketchy',
                    selectedValue: ctx.post.safety,
                    text: 'Sketchy'}) %>
                <%= ctx.makeRadio({
                    name: 'safety',
                    value: 'unsafe',
                    selectedValue: ctx.post.safety,
                    class: 'safety-unsafe',
                    text: 'Unsafe'}) %>
            </section>
        <% } %>

        <% if (ctx.canEditPostRelations) { %>
            <section class='relations'>
                <%= ctx.makeTextInput({
                    text: 'Relations',
                    name: 'relations',
                    placeholder: 'space-separated post IDs',
                    pattern: '^[0-9 ]*$',
                    value: ctx.post.relations.map(rel => rel.id).join(' '),
                }) %>
            </section>
        <% } %>

        <% if (ctx.canEditPostFlags && ctx.post.type === 'video') { %>
            <section class='flags'>
                <label>Miscellaneous</label>
                <%= ctx.makeCheckbox({
                    text: 'Loop video',
                    name: 'loop',
                    checked: ctx.post.flags.includes('loop'),
                }) %>
            </section>
        <% } %>

        <% if (ctx.canEditPostTags) { %>
            <section class='tags'>
                <%= ctx.makeTextInput({
                    value: ctx.post.tags.join(' '),
                }) %>
            </section>
        <% } %>

        <% if (ctx.canEditPostNotes) { %>
            <section class='notes'>
                <a href class='add'>Add a note</a>
                <%= ctx.makeTextarea({disabled: true, text: 'Content (supports Markdown)', rows: '8'}) %>
                <a href class='delete inactive'>Delete selected note</a>
            </section>
        <% } %>

        <% if (ctx.canEditPostContent) { %>
            <section class='post-content'>
                <label>Content</label>
                <div class='dropper-container'></div>
            </section>
        <% } %>

        <% if (ctx.canEditPostThumbnail) { %>
            <section class='post-thumbnail'>
                <label>Thumbnail</label>
                <div class='dropper-container'></div>
                <a href>Discard custom thumbnail</a>
            </section>
        <% } %>

        <% if (ctx.canFeaturePosts) { %>
            <section class='management'>
                <ul>
                    <% if (ctx.canFeaturePosts) { %>
                        <li><a href class='feature'>Feature this post on main page</a></li>
                    <% } %>
                    <% if (ctx.canDeletePosts) { %>
                        <li><a href class='delete'>Delete this post</a></li>
                    <% } %>
                </ul>
            </section>
        <% } %>

        <div class='messages'></div>

        <input type='submit' value='Save' class='submit'/>
    </form>
</div>
