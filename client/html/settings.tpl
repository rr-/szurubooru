<div class='content-wrapper' id='settings'>
    <form>
        <strong>Browsing settings</strong>
        <p>These settings are saved to the browser's local storage and are not coupled to the user account, so they don't apply to other devices or browsers alike.</p>
        <div class='input'>
            <ul>
                <li>
                    <%= ctx.makeCheckbox({text: 'Enable keyboard shortcuts', id: 'keyboard-shortcuts', name: 'keyboard-shortcuts', checked: ctx.browsingSettings.keyboardShortcuts}) %>
                    <a class='append icon' href='/help/keyboard'><i class='fa fa-question-circle-o'></i></a>
                </li>
                <li>
                    <%= ctx.makeCheckbox({text: 'Upscale small posts', id: 'upscale-small-posts', name: 'upscale-small-posts', checked: ctx.browsingSettings.upscaleSmallPosts}) %>
                </li>
                <li>
                    <%= ctx.makeCheckbox({text: 'Endless scroll', id: 'endless-scroll', name: 'endless-scroll', checked: ctx.browsingSettings.endlessScroll}) %>
                    <p class='hint'>Rather than using a paged navigation, smoothly scrolls through the content.</p>
                </li>
                <li>
                    <%= ctx.makeCheckbox({text: 'Enable transparency grid', id: 'transparency-grid', name: 'transparency-grid', checked: ctx.browsingSettings.transparencyGrid}) %>
                    <p class='hint'>Renders a checkered pattern behind posts with transparent background.</p>
                </li>
                <li>
                    <%= ctx.makeCheckbox({text: 'Show tag suggestions', id: 'tag-suggestions', name: 'tag-suggestions', checked: ctx.browsingSettings.tagSuggestions}) %>
                    <p class='hint'>Shows a popup with suggested tags in edit forms.</p>
                </li>
            </ul>
        </div>
        <div class='messages'></div>
        <div class='buttons'>
            <input type='submit' value='Save settings'/>
        </div>
    </form>
</div>
