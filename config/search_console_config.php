<?php


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
    'shortcut' => 'maintenance',
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

$GLOBALS['search_console']['modules']['pa'] = array(
    'shortcut' => 'pa',
    'enableNew' => false,
    'enableGoTo' => false,
    'customSearch' => array('\Guave\SearchConsole\Modules\CustomSearch', 'customSearchPageArticle')
);

$GLOBALS['search_console']['modules']['module'] = array(
    'shortcut' => 'mod',
    'enableNew' => false,
    'enableGoTo' => false,
    'customSearch' => array('\Guave\SearchConsole\Modules\CustomSearch', 'customSearchModule')
);

$GLOBALS['search_console']['modules']['files'] = array(
    'shortcut' => 'files',
    'enableNew' => false,
    'enableGoTo' => true,
    'module' => 'files',
    'doNotSearch' => true
);

$GLOBALS['search_console']['modules']['themes'] = array(
    'shortcut' => 'themes',
    'enableNew' => true,
    'enableGoTo' => true,
    'defaultSearchFields' => array('id', 'name'),
    'module' => 'themes'
);

$GLOBALS['search_console']['modules']['logs'] = array(
    'shortcut' => 'log',
    'enableNew' => false,
    'enableGoTo' => true,
    'module' => 'log',
    'doNotSearch' => true
);

$GLOBALS['search_console']['modules']['settings'] = array(
    'shortcut' => 'settings',
    'enableNew' => false,
    'enableGoTo' => true,
    'module' => 'settings',
    'doNotSearch' => true
);