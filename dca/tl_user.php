<?php


foreach($GLOBALS['TL_DCA']['tl_user']['palettes'] as $k => $v) {

    if($k == '__selector__') continue;

    $GLOBALS['TL_DCA']['tl_user']['palettes'][$k] .= ';{search_console_legend},enableSearchConsole';
}


$GLOBALS['TL_DCA']['tl_user']['fields']['enableSearchConsole'] = array(
    'label' => array('enable search_console'),
    'inputType' => 'checkbox',
    'sql' => "char(1) NOT NULL default ''"
);

