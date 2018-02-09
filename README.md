# WP Plugin: Admin-No-Ajax

A WordPress plugin that changes the WP AJAX routine and rewrites the ajax requests to custom url rather than `/wp-admin/admin-ajax.php` back-end.

## Install

Recommended installation to WP project is through composer:
```
$ composer require alexsancho/wp-admin-no-ajax
```

## Use cases
- Rewrite all admin-ajax.php queries into custom url so you can allow `/wp-admin/` to only certain IP-addresses.
- You can use this to confuse bots which might try to use vulnerabilities in admin-ajax.php.

## Configuration
### Variables
This plugin url is by default `/admin-no-ajax/`. You can use filters to change it or you can set the default value by yourself by using:

```php
// This turns the no admin ajax url to -> /ajax/
define('WP_ADMIN_NO_AJAX_URL','ajax');
```

**Notice:** Value set here can be filtered too, this just sets the starting point for the custom url.

**Notice 2:** After plugin installation and other changes be sure to refresh your permalinks by just going to Settings > Permalinks > and saving it without any modification.

### Hooks & Filters
You can customize the url by using filter `admin-no-ajax/keyword`.
```php
<?php

// This changes /admin-no-ajax/ -> /ajax/
add_filter( 'admin-no-ajax/keyword', 'my_custom_admin_no_ajax_url' );
function my_custom_admin_no_ajax_url( $ajax_url ) {
    return "ajax";
}
```

You can run commands before ajax calls by using `admin-no-ajax/before` or `admin-no-ajax/before/{action}`
```php
<?php
// Writes log entries after hearthbeat action for debugging
do_action( 'admin-no-ajax/before/heartbeat' , 'my_custom_admin_no_ajax_debug' );
function my_custom_admin_no_ajax_debug() {
    error_log( 'DEBUG | heartbeat action was run by: '.$_SERVER[“REMOTE_ADDR”] );
}
```
