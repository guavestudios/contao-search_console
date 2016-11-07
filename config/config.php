<?php

if(TL_MODE == 'BE') {
    $GLOBALS['TL_HOOKS']['outputBackendTemplate'][] = array('\Guave\SearchConsole\Helper\Helper','injectJavascript');
    $GLOBALS['TL_HOOKS']['executePreActions'][] = array('Guave\SearchConsole\Modules\SearchConsole','search');
    $GLOBALS['TL_HOOKS']['getUserNavigation'][] = array('\Guave\SearchConsole\Helper\Helper','disableModule');
}

//modules
$GLOBALS['BE_MOD']['search_console']['search_console'] = array(
    'callback' => 'Guave\SearchConsole\Modules\SearchConsole'
);


//search_console modules config
$GLOBALS['search_console']['modules']['article'] = array(
    'shortcut' => 'a',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'title', 'alias'),
    'module' => 'article'
);

$GLOBALS['search_console']['modules']['news'] = array(
    'shortcut' => 'news',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'headline', 'alias'),
    'module' => 'news'
);

$GLOBALS['search_console']['modules']['calendar'] = array(
    'shortcut' => 'cal',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'title'),
    'module' => 'calendar'
);

$GLOBALS['search_console']['modules']['faq_cat'] = array(
    'shortcut' => 'faq',
    'table' => 'tl_faq_category',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'title'),
    'module' => 'faq'
);

$GLOBALS['search_console']['modules']['newsletter_channel'] = array(
    'shortcut' => 'nl',
    'table' => 'tl_newsletter_channel',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'title'),
    'module' => 'newsletter'
);

$GLOBALS['search_console']['modules']['page'] = array(
    'shortcut' => 'p',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'pageTitle', 'alias'),
    'module' => 'page'
);

$GLOBALS['search_console']['modules']['form'] = array(
    'shortcut' => 'f',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'title', 'alias'),
    'module' => 'form'
);

$GLOBALS['search_console']['modules']['maintenance'] = array(
    'shortcut' => 'm',
    'enableNew' => false,
    'enableGoTo' => true,
    'module' => 'maintenance',
    'doNotSearch' => true
);

$GLOBALS['search_console']['modules']['content'] = array(
    'shortcut' => 'c',
    'enableNew' => false,
    'enableGoTo' => false,
    'module' => 'content',
    'defaultSearchFields' => array('id'),
    'table' => 'tl_content'
);