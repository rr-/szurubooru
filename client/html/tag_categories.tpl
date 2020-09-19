<div class='content-wrapper tag-categories'>
    <form>
        <h1>Tag categories</h1>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class='name'>Category name</th>
                        <th class='color'>CSS color</th>
                        <th class='order'>Order</th>
                        <th class='usages'>Usages</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <% if (ctx.canCreate) { %>
            <p><a href class='add'>Add new category</a></p>
        <% } %>

        <div class='messages'></div>

        <% if (ctx.canCreate || ctx.canEditName || ctx.canEditColor || ctx.canEditOrder || ctx.canDelete) { %>
            <div class='buttons'>
                <input type='submit' class='save' value='Save changes'>
            </div>
        <% } %>
    </form>
</div>
