<div class='tag-delete'>
    <form>
        <% if (tag.usages) { %>
            <p>For extra <s>paranoia</s> safety, only tags that are unused can be deleted.</p>
            <p>Check <a href='/posts/<%= tag.names[0] %>'>which posts</a> are tagged with <%= tag.names[0] %>.</p>
        <% } else { %>
            <div class='input'>
                <ul>
                    <li>
                        <%= makeCheckbox({id: 'confirm-deletion', name: 'confirm-deletion', required: true, text: 'I confirm that I want to delete this tag.'}) %>
                    </li>
                </ul>
            </div>
            <div class='messages'></div>
            <div class='buttons'>
                <input type='submit' value='Delete tag'/>
            </div>
        <% } %>
    </form>
</div>
