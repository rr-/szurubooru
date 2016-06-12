<div class='tag-merge'>
    <form class='tabular'>
        <p>Proceeding will remove this tag and retag its posts with the tag
        specified below. Aliases, suggestions and implications are discarded
        and need to be handled manually.</p>
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
