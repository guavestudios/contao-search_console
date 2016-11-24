<?php

if(TL_MODE == 'BE') {
    $GLOBALS['TL_HOOKS']['outputBackendTemplate'][] = array('\Guave\SearchConsole\Modules\SearchConsole','injectJavascript');
    $GLOBALS['TL_HOOKS']['executePreActions'][] = array('\Guave\SearchConsole\Modules\SearchConsole','search');
    $GLOBALS['TL_HOOKS']['getUserNavigation'][] = array('\Guave\SearchConsole\Helper\Helper','disableModule');
}

//modules
$GLOBALS['BE_MOD']['search_console']['search_console'] = array(
    'callback' => 'Guave\SearchConsole\Modules\SearchConsole'
);

require_once __DIR__.'/search_console_config.php';