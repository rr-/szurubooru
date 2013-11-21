# Browsing

Clicking the Browse button at the top will take you to the list of recent posts. Use the search box in the top right corner to find posts you want to see.

If you&rsquo;re not a registered user, you will only see public (Safe) posts. Logging in to your account will enable you to filter content by its rating: Safe, Sketchy, and NSFW.

You can use your keyboard to navigate around the site. There are a few shortcuts:

- focus search field: `[Q]`
- scroll up/down: `[W]` and `[S]`
- go to newer/older post or page: `[A]` and `[D]`
- edit post: `[E]`
- focus first post in post list: `[P]`

# Search syntax

- contatining tag "Haruhi": [search]Haruhi[/search]
- **not** contatining tag "Kyon": [search]-Kyon[/search]
- uploaded by David: [search]submit:David[/search] (note no spaces)
- favorited by David: [search]fav:David[/search]
- favorited by at least four users: [search]favmin:4[/search]
- commented by David: [search]comment:David[/search]
- having at least three comments: [search]commentmin:3[/search]
- having minimum score of 4: [search]scoremin:4[/search]
- tagged with at least seven tags: [search]tagmin:7[/search]
- exactly from the specified date: [search]date:2001[/search], [search]date:2012-09-29[/search] (yyyy-mm-dd format)
- from the specified date onwards: [search]datemin:2001-01-01[/search]
- up to the specified date: [search]datemax:2004-07[/search]
- having specific ID: [search]id:1,2,3,8[/search]
- having ID no less than specified value: [search]idmin:28[/search]
- by content type: [search]type:img[/search], [search]type:swf[/search], [search]type:yt[/search] (images, flash files and YouTube videos, respectively)

You can combine tags and negate any of them for interesting results. [search]sea -favmin:8 type:swf submit:Pirate[/search] will show you **flash files** tagged as **sea**, that were **liked by seven people** at most, uploaded by user **Pirate**.

All of the above can be sorted using additional sorting tags:

- as random as it can get: [search]order:random[/search]
- newest to oldest: [search]order:date[/search] (pretty much default browse view)
- oldest to newest: [search]-order:date[/search]
- most commented first: [search]order:comments[/search]
- loved by most: [search]order:favs[/search]
- highest scored: [search]order:score[/search]
- with most tags: [search]order:tags[/search]

As shown with [search]-order:date[/search], any of them can be reversed in the same way as negating other tags: by placing a dash before the tag. If there is a "min" tag, there&rsquo;s also its "max" counterpart, e.g. [search]favmax:7[/search].

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
