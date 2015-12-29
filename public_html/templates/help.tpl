<div id="help-view">

<ul class="tabs">
    <li>
        <a class="big-button" href="#/help/about">About</a>
    </li>
    <li>
        <a class="big-button" href="#/help/keyboard">Keyboard</a>
    </li>
    <li>
        <a class="big-button" href="#/help/search-syntax">Search syntax</a>
    </li>
    <li>
        <a class="big-button" href="#/help/comments">Comments</a>
    </li>
    <li>
        <a class="big-button" href="#/help/tos">Terms of service</a>
    </li>
</ul>

<div data-tab="about">
    <h1>About</h1>

    <p>Szurubooru is an image board engine inspired by services such as
    Danbooru, Gelbooru and Moebooru. Its name <a
    href="http://sjp.pwn.pl/sjp/;2527372">has its roots in Polish language and
    has onomatopeic meaning of scraping or scrubbing</a>. It is pronounced as
    <em>shoorubooru</em>.</p>

    <h1>Registration</h1>

    <p>By default, szurubooru is shipped as an invite-only app. In other words,
    in order to use the service, you need to register and have someone inside
    accept your registration. The e-mail you enter during account creation is
    only used to retrieve your Gravatar and activate your account. Only you can
    see it (well, except the database staff&hellip; we won&rsquo;t spam your
    mailbox anyway).</p>

    <p>Oh, and you can delete your account at any time. Posts you uploaded will
    stay, unless some angry admin removes them.</p>
</div>

<div data-tab="keyboard">
    <h1>Keyboard shortcuts</h1>

    <p>You can use your keyboard to navigate around the site. There are a few
    shortcuts:</p>

    <table>
        <thead>
            <tr>
                <th>Hotkey</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[Q]</code></td>
                <td>Focus search field, if available</td>
            </tr>

            <tr>
                <td><code>[A]</code> and <code>[D]</code></td>
                <td>Go to newer/older page or post</td>
            </tr>

            <tr>
                <td><code>[F]</code></td>
                <td>Cycle post fit mode</td>
            </tr>

            <tr>
                <td><code>[E]</code></td>
                <td>Edit post</td>
            </tr>

            <tr>
                <td><code>[P]</code></td>
                <td>Focus first post in post list</td>
            </tr>
        </tbody>
    </table>

    <p>Additionally, each item in top navigation can be accessed using feature
    called &ldquo;access keys&rdquo;. Pressing underlined letter while holding
    Shfit or Alt+Shift (depending on your browser) will go to the desired page
    (most browsers) or focus the link (IE).</p>
</div>

<div data-tab="search-syntax">
    <h1>Search syntax</h1>

    <table>
        <thead>
            <tr>
                <th>Command</th>
                <th>Description</th>
            </tr>
        </thead>

        <tbody>
            <%
                var table = [
                    {search: 'Haruhi', description: 'containing tag &ldquo;Haruhi&rdquo;'},
                    {search: '-Kyon', description: 'not containing tag &ldquo;Kyon&rdquo;'},
                    {search: 'uploader:David', description: 'uploaded by user David'},
                    {search: 'comment:David', description: 'commented by David'},
                    {search: 'fav:David', description: 'favorited by David'},
                    {search: 'fav_count:4', description: 'favorited by exactly four users'},
                    {search: 'fav_count:4,5', description: 'favorited by four or five users'},
                    {search: 'fav_count:4..', description: 'favorited by at least four users'},
                    {search: 'fav_count:..4', description: 'favorited by at most four users'},
                    {search: 'fav_count:4..6', description: 'favorited by at least four, but no more than six users'},
                    {search: 'comment_count:3', description: 'having exactly three comments'},
                    {search: 'score:4', description: 'having score of 4'},
                    {search: 'tag_count:7', description: 'tagged with exactly seven tags'},
                    {search: 'note_count:1..', description: 'having at least one post note'},
                    {search: 'feature_count:1..', description: 'having been featured at least once'},
                    {search: 'date:today', description: 'posted today'},
                    {search: 'date:yesterday', description: 'posted yesterday'},
                    {search: 'date:2000', description: 'posted in year 2000'},
                    {search: 'date:2000-01', description: 'posted in January, 2000'},
                    {search: 'date:2000-01-01', description: 'posted on January 1st, 2000'},
                    {search: 'id:1', description: 'having specific post ID'},
                    {search: 'name:<em>hash</em>', description: 'having specific post name (hash in full URLs)'},
                    {search: 'type:image', description: 'only image posts'},
                    {search: 'type:flash', description: 'only Flash posts'},
                    {search: 'type:youtube', description: 'only Youtube posts'},
                    {search: 'type:video', description: 'only video posts'},
                    {search: 'special:liked', description: 'posts liked by currently logged in user'},
                    {search: 'special:disliked', description: 'posts disliked by currently logged in user'},
                    {search: 'special:fav', description: 'posts added to favorites by currently logged in user'},
                ];
                _.each(table, function(row) { %>
                    <tr>
                        <td><a href="#/posts/query=<%= row.search %>"><code><%= row.search %></code></a></td>
                        <td><%= row.description %></td>
                    </tr>
            <% }) %>
        </tbody>
    </table>

    <p>Most of the commands support ranged and composites values, e.g.
    <code>id:<em>number</em></code> operator supports respectively <a
    href="#/posts/query=id:5..7"><code>id:5..7</code></a> and <a
    href="#/posts/query=id:5,10,15"><code>id:5,10,15</code></a>. You can
    combine tags and negate any of them for interesting results. <a
    href="#/posts/query=sea -fav_count:..8 type:flash
    uploader:Pirate"><code>sea -fav_count:8.. type:swf
    uploader:Pirate</code></a> will show you flash files tagged as sea, that
    were liked by seven people at most, uploaded by user Pirate.</p>

    <p>All of the above can be sorted using additional tag in form of
    <code>order:<em>keyword</em></code>:</p>

    <table>
        <thead>
            <tr>
                <th>Command</th>
                <th>Description</th>
            </tr>
        </thead>

        <tbody>
            <%
                var table = [
                    {search: 'order:random', description: 'as random as it can get'},
                    {search: 'order:id', description: 'highest to lowest post ID (default browse view)'},
                    {search: 'order:creation_date', description: 'newest to oldest (pretty much same as above)'},
                    {search: '-order:creation_date', description: 'oldest to newest'},
                    {search: 'order:creation_date,asc', description: 'oldest to newest (ascending order, default = descending)'},
                    {search: 'order:edit_date', description: 'like <code>creation_date</code>, only looks at last edit time'},
                    {search: 'order:score', description: 'highest scored'},
                    {search: 'order:file_size', description: 'largest files first'},
                    {search: 'order:tag_count', description: 'with most tags'},
                    {search: 'order:fav_count', description: 'loved by most'},
                    {search: 'order:comment_count', description: 'most commented first'},
                    {search: 'order:fav_date', description: 'recently added to favorites'},
                    {search: 'order:comment_date', description: 'recently commented'},
                    {search: 'order:feature_date', description: 'recently featured'},
                    {search: 'order:feature_count', description: 'most often featured'},
                ];
                _.each(table, function(row) { %>
                <tr>
                    <td><a href="#/posts/query=<%= row.search %>"><code><%= row.search %></code></a></td>
                    <td><%= row.description %></td>
                </tr>
            <% }) %>
        </tbody>
    </table>

    <p>As shown with <a
    href="#/posts/query=-order:creation_date"><code>-order:creation_date</code></a>,
    any of them can be reversed in the same way as negating other tags: by
    placing a dash before the tag.</p>
</div>

<div data-tab="comments">
    <h1>Comments</h1>
    <p>Comments support Markdown syntax, extended by some handy tags:</p>

    <table>
        <tbody>
            <tr>
                <td><code>@426</code></td>
                <td>links to post number 426</td>
            </tr>
            <tr>
                <td><code>#Dragon_Ball</code></td>
                <td>links to tag &ldquo;Dragon_Ball&rdquo;</td>
            </tr>
            <tr>
                <td><code>+Pirate</code></td>
                <td>links to user &ldquo;Pirate&rdquo;</td>
            </tr>
            <tr>
                <td><code>~~new~~</code></td>
                <td>adds strike-through</td>
            </tr>
            <tr>
                <td><code>[spoiler]Lelouch survives[/spoiler]</td>
                <td>marks text as spoiler and hides it</td>
            </tr>
        </tbody>
    </table>
</div>

<div data-tab="tos">
    <h1>Terms of service</h1>

    <p>By accessing <%= title %> (&ldquo;Site&rdquo;) you agree to the
    following Terms of Service. If you do not agree to these terms, then please
    do not access the Site.</p>

    <ul>
        <li>The Site is presented to you AS IS, without any warranty, express
        or implied. You will not hold the Site or its staff members liable for
        damages caused by the use of the site.</li>
        <li>The Site reserves the right to delete or modify your account, or
        any content you have posted to the site.</li>
        <li>The Site reserves the right to change these Terms of Service
        without prior notice.</li>
        <li>If you are a minor, then you will not use the Site.</li>
        <li>You are using the Site only for personal use.</li>
        <li>You will not spam, troll or offend anyone.</li>
        <li>You accept that the Site is not liable for any content that you may stumble upon.</li>
    </ul>

    <p><strong>Prohibited content</strong></p>

    <ul>
        <li>Child pornography: any photograph or photorealistic drawing or
        movie that depicts children in a sexual manner. This includes nudity,
        explicit sex, implied sex, or sexually persuasive positions.</li>

        <li>Bestiality: any photograph or photorealistic drawing or movie that
        depicts humans having sex (either explicit or implied) with other
        non-human animals.</li>

        <li>Any depiction of extreme mutilation, extreme bodily distension,
        feces.</li>

        <li>Personal images: any image that is suspected to be uploaded for
        personal use. This includes, but is not limited to, avatars and forum
        signatures.</li>
    </ul>

    <h1>Privacy policy</h1>

    <p>The Site will not disclose the IP address or email address of any user
    except to the staff.</p>

    Posts, comments, favorites, ratings and other actions linked to your
    account will be stored in the Site&rsquo;s database. The &ldquo;Upload
    anonymously&rdquo; option allows you to post content without linking it to
    your account&nbsp;&ndash; meaning your nickname will not be stored in the
    database nor shown in the &ldquo;Uploader&rdquo; field.</p>

    <p>Cookies are used to store your session data in order to keep you logged
    in and personalize your web experience.</p>
</div>

</div>
