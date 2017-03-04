<div class='tag-merge'>
    <form>
        <ul class='input'>
            <li class='target'>
                <%= ctx.makeTextInput({name: 'target-tag', required: true, text: 'Target tag', pattern: ctx.tagNamePattern}) %>
            </li>

            <li>
                <p>Usages in posts, suggestions and implications will be
                merged. Category needs to be handled manually.</p>

                <%= ctx.makeCheckbox({name: 'alias', text: 'Make this tag an alias of the target tag.'}) %>

                <%= ctx.makeCheckbox({required: true, text: 'I confirm that I want to merge this tag.'}) %>
            </li>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Merge tag'/>
        </div>
    </form>
</div>
