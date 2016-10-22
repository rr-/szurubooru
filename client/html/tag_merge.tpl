<div class='tag-merge'>
    <form>
        <ul>
            <li class='target'>
                <%= ctx.makeTextInput({required: true, text: 'Target tag', pattern: ctx.tagNamePattern}) %>
            </li>

            <li>
                <p>Usages in posts, suggestions and implications will be
                merged. Category and aliases need to be handled manually.</p>

                <%= ctx.makeCheckbox({required: true, text: 'I confirm that I want to merge this tag.'}) %>
            </li>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Merge tag'/>
        </div>
    </form>
</div>
