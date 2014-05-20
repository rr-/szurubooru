# Browsing

Clicking the Browse button at the top will take you to the list of recent posts. Use the search box in the top right corner to find posts you want to see.

If you&rsquo;re not a registered user, you will only see public (Safe) posts. Logging in to your account will enable you to filter content by its rating: Safe, Sketchy, and NSFW.

You can use your keyboard to navigate around the site. There are a few shortcuts:

Hotkey          | Description
--------------- | -----------
`[Q]`           | Focus search field
`[W]` and `[S]` | Scroll up / down
`[A]` and `[D]` | Go to newer/older post or page
`[E]`           | Edit post
`[P]`           | Focus first post in post list

# Search syntax

Command                           | Description                                               | Aliases                                         |
--------------------------------- | --------------------------------------------------------- | ----------------------------------------------- |
[search]Haruhi[/search]           | containing tag "Haruhi"                                   | -                                               |
[search]-Kyon[/search]            | **not** containing tag "Kyon"                             | -                                               |
[search]submit:David[/search]     | uploaded by user David                                    | `upload`, `uploads`, `uploaded`, `uploader`     |
[search]comment:David[/search]    | commented by David                                        | `comments`, `commenter`, `commented`            |
[search]fav:David[/search]        | favorited by David                                        | `favs`, `favd`                                  |
[search]favmin:4[/search]         | favorited by at least four users                          | `fav_min`                                       |
[search]favmax:4[/search]         | favorited by at most four users                           | `fax_max`                                       |
[search]commentmin:3[/search]     | having at least three comments                            | `comment_min`                                   |
[search]commentmax:3[/search]     | having at most three comments                             | `comment_max`                                   |
[search]scoremin:4[/search]       | having minimum score of 4                                 | `score_min`                                     |
[search]scoremax:4[/search]       | having maximum score of 4                                 | `score_max`                                     |
[search]tagmin:7[/search]         | tagged with at least seven tags                           | `tag_min`                                       |
[search]tagmax:7[/search]         | tagged with at most seven tags                            | `tax_max`                                       |
[search]date:2000[/search]        | posted in year 2000                                       | -                                               |
[search]date:2000-01[/search]     | posted in January, 2000                                   | -                                               |
[search]date:2000-01-01[/search]  | posted on January 1st, 2000                               | -                                               |
[search]datemin:...[/search]      | posted on `...` or later (format like in `date:`)         | `date_min`                                      |
[search]datemax:...[/search]      | posted on `...` or earlier (format like in `date:`)       | `date_max`                                      |
[search]id:1,2,3[/search]         | having specific post ID                                   | `ids`                                           |
[search]name:...[/search]         | having specific post name (hash in full URLs)             | `names`, `hash`, `hashes`                       |
[search]idmin:5[/search]          | posts with ID greater than or equal to @5                 | `id_min`                                        |
[search]idmax:5[/search]          | posts with ID less than or equal to @5                    | `id_max`                                        |
[search]type:img[/search]         | only image posts                                          | `type:image`                                    |
[search]type:flash[/search]       | only Flash posts                                          | `type:swf`                                      |
[search]type:yt[/search]          | only Youtube posts                                        | `type:youtube`                                  |
[search]special:liked[/search]    | posts liked by currently logged in user                   | `special:likes`, `special:like`                 |
[search]special:disliked[/search] | posts disliked by currently logged in user                | `special:dislikes`, `special:dislike`           |
[search]special:fav[/search]      | posts added to favorites by currently logged in user      | `special:favs`, `special:favd`                  |
[search]special:hidden[/search]   | hidden (soft-deleted) posts; moderators only              | -                                               |

You can combine tags and negate any of them for interesting results. [search]sea -favmin:8 type:swf submit:Pirate[/search] will show you **flash files** tagged as **sea**, that were **liked by seven people** at most, uploaded by user **Pirate**.

All of the above can be sorted using additional tag in form of `order:...`:

Command                            | Description                                              | Aliases (`order:...`)                      |
 --------------------------------- | -------------------------------------------------------- | ------------------------------------------ |
[search]order:random[/search]      | as random as it can get                                  | -                                          |
[search]order:id[/search]          | highest to lowest post ID (default browse view)          | -                                          |
[search]order:date[/search]        | newest to oldest (pretty much same as above)             | -                                          |
[search]-order:date[/search]       | oldest to newest                                         | -                                          |
[search]order:date,asc[/search]    | oldest to newest (ascending order, default = descending) | -                                          |
[search]order:score[/search]       | highest scored                                           | -                                          |
[search]order:comments[/search]    | most commented first                                     | `comment`, `commentcount`, `comment_count` |
[search]order:favs[/search]        | loved by most                                            | `fav`, `favcount`, `fav_count`             |
[search]order:tags[/search]        | with most tags                                           | `tag`, `tagcount`, `tag_count`             |
[search]order:commentdate[/search] | recently commented                                       | `comment_date`                             |
[search]order:favdate[/search]     | recently added to favorites                              | `fav_date`                                 |
[search]order:filesize[/search]    | largest files first                                      | `file_size`                                |

As shown with [search]-order:date[/search], any of them can be reversed in the same way as negating other tags: by placing a dash before the tag.

# Registration

The e-mail you enter during account creation is only used to retrieve your [Gravatar](http://gravatar.com) and activate your account. Only you can see it (well, except the database staff&hellip; we won&rsquo;t spam your mailbox anyway).

Oh, and you can delete your account at any time. Posts you uploaded will stay, unless some angry admin removes them.

# Comments

Registered users can post comments. Comments support [Markdown syntax](http://daringfireball.net/projects/markdown/syntax), extended by some handy tags:

- permalink to post number 426: @426
- link to tag "Dragon_Ball": #Dragon_Ball
- mark text as spoiler and hide it: [spoiler]&#91;spoiler]There is no spoon.&#91;/spoiler][/spoiler]

# Uploads

After registering and activating your account, you gain the power to upload files to the service for everyone else to see.

Remember to follow the [rules](/help/rules)!
