<div class='tag-merge'>
    <form class='tabular'>
        <p>Proceeding will remove <%= ctx.tag.names[0] %> and retag its posts with
        the tag specified below. Aliases and relations of <%= ctx.tag.names[0] %>
        will be discarded and need to be handled by hand.</p>
        <div class='input'>
            <ul>
                <li class='target'>
                    <%= ctx.makeTextInput({required: true, text: 'Target tag', pattern: ctx.tagNamePattern}) %>
                </li>
                <li class='confirm'>
                    <label></label>
                    <%= ctx.makeCheckbox({required: true, text: 'I confirm that I want to merge this tag.'}) %>
                </li>
            </ul>
        </div>
        <div class='messages'></div>
        <div class='buttons'>
            <input type='submit' value='Merge tag'/>
        </div>
    </form>
</div>
