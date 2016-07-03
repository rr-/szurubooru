<div class='edit-sidebar'>
    <form autocomplete='off'>
        <div class='input'>
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

            <section class='tags'>
                <%= ctx.makeTextInput({
                    text: 'Tags',
                    value: ctx.post.tags.join(' '),
                    readonly: !ctx.canEditPostTags}) %>
            </section>

            <section class='relations'>
                <%= ctx.makeTextInput({
                    text: 'Relations',
                    name: 'relations',
                    placeholder: 'space-separated post IDs',
                    pattern: '^[0-9 ]*$',
                    value: ctx.post.relations.map(rel => rel.id).join(' '),
                    readonly: !ctx.canEditPostRelations}) %>
            </section>

            <% if ((ctx.editingNewPost && ctx.canCreateAnonymousPosts) || ctx.post.type === 'video') { %>
                <section class='flags'>
                    <label>Miscellaneous</label>

                    <% if (ctx.editingNewPost && ctx.canCreateAnonymousPosts) { %>
                        <%= ctx.makeCheckbox({
                            text: 'Don\'t show me as uploader',
                            name: 'anonymous'}) %>
                    <% } %>

                    <% if (ctx.post.type === 'video') { %>
                        <!-- TODO: bind state -->
                        <%= ctx.makeCheckbox({
                            text: 'Loop video',
                            name: 'loop',
                            readonly: !ctx.canEditPostFlags}) %>
                    <% } %>
                </section>
            <% } %>
        </div>
        <div class='messages'></div>

        <div class='buttons'>
            <input class='encourage' type='submit' value='Submit' class='submit'/>
        </div>
    </form>
</div>
