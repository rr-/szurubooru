<div class='pool-merge'>
    <form>
        <ul class='input'>
            <li class='target'>
                <%= ctx.makeTextInput({name: 'target-pool', required: true, text: 'Target pool', pattern: ctx.poolNamePattern}) %>
            </li>

            <li>
                <p>Posts in the two pools will be combined.
                Category needs to be handled manually.</p>

                <%= ctx.makeCheckbox({required: true, text: 'I confirm that I want to merge this pool.'}) %>
            </li>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Merge pool'/>
        </div>
    </form>
</div>
