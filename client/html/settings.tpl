<div class='content-wrapper' id='settings'>
    <form>
        <strong>Browsing settings</strong>
        <p>These settings are saved to the browser's local storage and are not coupled to the user account, so they don't apply to other devices or browsers alike.</p>
        <ul class='input'>
            <li>
                <%= ctx.makeCheckbox({
                    text: "Enable keyboard shortcuts <a class='append icon' href='" + ctx.formatClientLink('help', 'keyboard') + "'><i class='fa fa-question-circle-o'></i></a>",
                    name: 'keyboard-shortcuts',
                    checked: ctx.browsingSettings.keyboardShortcuts,
                }) %>
            </li>

            <li>
                <%= ctx.makeNumericInput({
                    text: 'Number of posts per page',
                    name: 'posts-per-page',
                    checked: ctx.browsingSettings.postCount,
                    value: ctx.browsingSettings.postsPerPage,
                    min: 10,
                    max: 100,
                }) %>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Use dark theme',
                    name: 'dark-theme',
                    checked: ctx.browsingSettings.darkTheme,
                }) %>
                <p class='hint'>Changing this setting will require you to refresh the page for it to apply.</p>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Upscale small posts',
                    name: 'upscale-small-posts',
                    checked: ctx.browsingSettings.upscaleSmallPosts}) %>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Enable endless scroll',
                    name: 'endless-scroll',
                    checked: ctx.browsingSettings.endlessScroll,
                }) %>
                <p class='hint'>Rather than using a paged navigation, smoothly scrolls through the content.</p>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Use post flow',
                    name: 'post-flow',
                    checked: ctx.browsingSettings.postFlow,
                }) %>
                <p class='hint'>Use a content-aware flow for thumbnails on the post search page.</p>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Enable transparency grid',
                    name: 'transparency-grid',
                    checked: ctx.browsingSettings.transparencyGrid,
                }) %>
                <p class='hint'>Renders a checkered pattern behind posts with transparent background.</p>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Show tag suggestions',
                    name: 'tag-suggestions',
                    checked: ctx.browsingSettings.tagSuggestions,
                }) %>
                <p class='hint'>Shows a popup with suggested tags in edit forms.</p>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Automatically play video posts',
                    name: 'autoplay-videos',
                    checked: ctx.browsingSettings.autoplayVideos,
                }) %>
            </li>

            <li>
                <%= ctx.makeCheckbox({
                    text: 'Display underscores as spaces',
                    name: 'underscores-as-spaces',
                    checked: ctx.browsingSettings.tagUnderscoresAsSpaces,
                }) %>
                <p class='hint'>Display all underscores as if they were spaces. This is only a visual change, which means that you'll still have to use underscores when searching or editing tags.</p>
            </li>
        </ul>

        <div class='messages'></div>
        <div class='buttons'>
            <input type='submit' value='Save settings'/>
        </div>
    </form>
</div>
