# contao-search_console
a little search_console module for contao admin

# dependencies
https://github.com/terminal42/contao-NamespaceClassLoader

# config
```php
<?php
//system/mymodule/config/config.php
$GLOBALS['search_console']['modules']['SOME_UNIQUE_NAME'] = array();
```

| key  | type | mandatory | description |
| ---- | ---  | --- | --- |
| module | string| M | the contao module name
| shortcut | string| O | shortcut for new and  go to
| enableNew | boolean | O | enables the new shortcut link (n ...)
| enableGoTo | boolean | O | enables the new shortcut link (g ...)
| defaultSearchFields | array | O | if no search field is specified, it does a like search on this fields
| doNotSearch | boolean | O | will not be used for search query only for shortcuts

