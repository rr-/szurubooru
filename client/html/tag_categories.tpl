<div class='content-wrapper tag-categories'>
    <form>
        <h1>Tag categories</h1>
        <table>
            <thead>
                <tr>
                    <th class='name'>Category name</th>
                    <th class='color'>CSS color</th>
                    <th class='usages'>Usages</th>
                </tr>
            </thead>
            <tbody>
                <% for (let category of ctx.tagCategories) { %>
                    <% if (category.default) { %>
                        <tr data-category='<%= category.name %>' class='default'>
                    <% } else { %>
                        <tr data-category='<%= category.name %>'>
                    <% } %>
                        <td class='name'>
                            <% if (ctx.canEditName) { %>
                                <%= ctx.makeTextInput({value: category.name, required: true}) %>
                            <% } else { %>
                                <%= category.name %>
                            <% } %>
                        </td>
                        <td class='color'>
                            <% if (ctx.canEditColor) { %>
                                <%= ctx.makeColorInput({value: category.color}) %>
                            <% } else { %>
                                <%= category.color %>
                            <% } %>
                        </td>
                        <td class='usages'>
                            <a href='/tags/text=category:<%= category.name %>'>
                                <%= category.usages %>
                            </a>
                        </td>
                        <% if (ctx.canDelete) { %>
                            <td class='remove'>
                                <% if (category.usages) { %>
                                    <a class='inactive' title="Can't delete category in use">Remove</a>
                                <% } else { %>
                                    <a href='#'>Remove</a>
                                <% } %>
                            </td>
                        <% } %>
                        <% if (ctx.canSetDefault) { %>
                            <td class='set-default'>
                                <a href='#'>Make default</a>
                            </td>
                        <% } %>
                    </tr>
                <% } %>
            </tbody>
            <tfoot>
                <tr class='add-template'>
                    <td class='name'>
                        <%= ctx.makeTextInput({required: true}) %>
                    </td>
                    <td class='color'>
                        <%= ctx.makeColorInput({value: '#000000'}) %>
                    </td>
                    <td class='usages'>
                        0
                    </td>
                    <td class='remove'>
                        <a href='#'>Remove</a>
                    </td>
                    <td class='set-default'>
                        <a href='#'>Make default</a>
                    </td>
                </tr>
            </tfoot>
        </table>

        <% if (ctx.canCreate) { %>
            <p><a href='#' class='add'>Add new category</a></p>
        <% } %>

        <div class='messages'></div>

        <% if (ctx.canCreate || ctx.canEditName || ctx.canEditColor || ctx.canDelete) { %>
            <div class='buttons'>
                <input type='submit' class='save' value='Save changes'>
            </div>
        <% } %>
    </form>
</div>
