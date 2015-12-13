Commenting (plugin for Omeka)
=============================


[Commenting] is a plugin for [Omeka] that provides a beefier implementation of
comments than the earlier Comment plugin. It allows for comments on any record
type (in theory, at least), and includes both ReCaptchas and Akismet spam
detection.

Commenting also allows for moderation and commenting permissions based on role.
Logged-in users are not required to submit recaptchas, if enabled.


Installation
------------

Uncompress files and rename plugin folder "Commenting".

Then install it like any other Omeka plugin and follow the config instructions.


Requirements
------------

The commenting plugin makes use of both ReCaptchas and the Akismet
spam-detection service. You will want to get API keys to both of these services
and add them to the plugin's configuration.


Configuration
-------------

* Threaded Comments: Check to display threaded comments
* ReCaptcha Keys: These keys duplicate the fields found in the Omeka Security
settings. ReCaptcha challenges only appear when there is no user logged in.
* Permissions
** Options are available to specify what roles can approve, add, and view
comments, and add comments without approval
** If the GuestUser plugin is installaed and activated, the guest role is
available in these options
** Allowing Public commenting will override the permissions set for adding and
viewing comments -- they will be open to all
* Akismet API key: You should get an API key to the Akismet spam management
service if you use public commenting
* Simple antispam: a simple question (addition of two digits) will be added to
the form.
* Honey pot: a hidden field will be added to avoid basic spam bots.

See Use Cases below for examples of configuration combinations


Displaying Comments
-------------------

Commenting will automatically add commenting options to Item and Collection show
pages via default public hooks:

```php
fire_plugin_hook('public_items_show', array('view' => $this, 'item' => $item));
```php

or:

```php
fire_plugin_hook('public_collections_show', array('view' => $this, 'collection' => $collection));
```

For flexibility and to enable commenting on other record types from modules
(e.g. SimplePages or ExhibitBuilder), you will have to add the following lines
to the appropriate place in the theme script:

```php
fire_plugin_hook('commenting_comments');
```

or with options:

```php
fire_plugin_hook('commenting_comments', array(
    'view' => $this,
    'display' => array('comments', 'comment_form'),
    'comments' => $comments,
));
```

For example, to show comments on exhibit sections and pages, the theme file `/exhibit-builder/exhibits/show.php`
could look like:

```php
<?php
echo head(array(
    'title' => metadata('exhibit_page', 'title') . ' &middot; ' . metadata('exhibit', 'title'),
    'bodyclass' => 'exhibits show'));
?>

<nav id="exhibit-pages">
    <?php echo exhibit_builder_page_nav(); ?>
</nav>

<h1><span class="exhibit-page"><?php echo metadata('exhibit_page', 'title'); ?></h1>

<nav id="exhibit-child-pages">
    <?php echo exhibit_builder_child_page_nav(); ?>
</nav>

<?php exhibit_builder_render_exhibit_page(); ?>

<?php fire_plugin_hook('commenting_comments'); ?>

<div id="exhibit-page-navigation">
    <?php if ($prevLink = exhibit_builder_link_to_previous_page()): ?>
    <div id="exhibit-nav-prev">
        <?php echo $prevLink; ?>
    </div>
    <?php endif; ?>
    <?php if ($nextLink = exhibit_builder_link_to_next_page()): ?>
    <div id="exhibit-nav-next">
        <?php echo $nextLink; ?>
    </div>
    <?php endif; ?>
    <div id="exhibit-nav-up">
        <?php echo exhibit_builder_page_trail(); ?>
    </div>
</div>

<?php echo foot(); ?>
```

The theme files (`comment.php`, `threaded-comments.php` and `comments.php`
should be adapted and saved in the `common` folder of the theme and, for
exhibits, in a sub-folder of `exhibit-builder`.

Keep in mind that updating themes or plugins will clobber your addition of the
commenting functions.

The Commenting plugin knows how to work with SimplePages and ExhibitBuilder. Due
to variability in how plugins store their data in the database, other record
types and views supplied by other plugins might or might not work out of the
box. Please ask on the forums or dev list if you would like Commenting to work
with other plugins.


Use Cases
---------

### Limited, moderated commenting

An institution wants only trusted people to leave comments for anyone to read.
It doesn't trust some of them enough to allow comments to be automatically
public.

The semi-trusted people could have the Researcher role in Omeka, with commenting
configured to allow Researchers to comment.
The Admin role is the only role that can moderate comments to approve them, and
Public is allowed to view comments.

### Open commenting, with registered users getting to submit comments without
approval

Install and configure the [GuestUser] plugin. Set commenting to Public so that
anyone can comment, and give the Guest User role permission to submit comments
without approval.


Warning
-------

Use it at your own risk.

It's always recommended to backup your files and database regularly so you can
roll back if needed.


Troubleshooting
---------------

See online issues on the [Commenting issues] page on GitHub.


License
-------

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Contact
-------

Current maintainers:
* [Center for History & New Media]


Copyright
---------

* Copyright Center for History and New Media, 2011-2013
* Copyright Daniel Berthereau, 2013-2015 [Daniel-KM] for [Mines ParisTech]


[Omeka]: https://omeka.org
[Commenting]: https://github.com/omeka/plugin-Commenting
[Commenting issues]: https://omeka.org/forums/forum/plugins
[GuestUser]: https://github.com/omeka/plugin-GuestUser
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[Center for History & New Media]: http://chnm.gmu.edu
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
[Mines ParisTech]: http://bib.mines-paristech.fr
