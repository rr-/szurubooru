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
                <% _.each(tagCategories, category => { %>
                    <tr data-category='<%= category.name %>'>
                        <td class='name'>
                            <% if (canEditName) { %>
                                <%= makeTextInput({value: category.name, required: true}) %>
                            <% } else { %>
                                <%= category.name %>
                            <% } %>
                        </td>
                        <td class='color'>
                            <% if (canEditColor) { %>
                                <%= makeColorInput({value: category.color}) %>
                            <% } else { %>
                                <%= category.color %>
                            <% } %>
                        </td>
                        <td class='usages'>
                            <a href='/tags/text=category:<%= category.name %>'>
                                <%= category.usages %>
                            </a>
                        </td>
                        <% if (canDelete) { %>
                            <td>
                                <% if (category.usages) { %>
                                    <a class='inactive remove' title="Can't delete category in use">Remove</a>
                                <% } else { %>
                                    <a href='#' class='remove'>Remove</a>
                                <% } %>
                            </td>
                        <% } %>
                    </tr>
                <% }) %>
            </tbody>
            <tfoot>
                <tr class='add-template'>
                    <td class='name'>
                        <%= makeTextInput({required: true}) %>
                    </td>
                    <td class='color'>
                        <%= makeColorInput({value: '#000000'}) %>
                    </td>
                    <td class='usages'>
                        0
                    </td>
                    <td>
                        <a href='#' class='remove'>Remove</a>
                    </td>
                </tr>
            </tfoot>
        </table>

        <% if (canCreate) { %>
            <p><a href='#' class='add'>Add new category</a></p>
        <% } %>

        <div class='messages'></div>

        <% if (canCreate || canEditName || canEditColor || canDelete) { %>
            <div class='buttons'>
                <input type='submit' class='save' value='Save changes'>
            </div>
        <% } %>
    </form>
</div>
