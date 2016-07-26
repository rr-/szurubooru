<div class='edit-sidebar'>
    <form autocomplete='off'>
        <div class='input'>
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

            <% if (ctx.canEditPostTags) { %>
                <section class='tags'>
                    <%= ctx.makeTextInput({
                        text: 'Tags',
                        value: ctx.post.tags.join(' '),
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
        </div>

        <div class='messages'></div>

        <div class='buttons'>
            <input class='encourage' type='submit' value='Submit' class='submit'/>
        </div>
    </form>
</div>
