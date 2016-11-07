<?php


namespace Guave\SearchConsole\Helper;


class Helper
{

    public static function injectJavascript($buffer, $template)
    {
        
        $script = '';
        $script .= '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>';
        $script .= '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>';
        $js = array();
        $js['search_console_main'] = '/system/modules/search_console/html/js/search_console.js';



        foreach($js as $s) {
            $options = \StringUtil::resolveFlaggedUrl($s);
            $script .= \Template::generateScriptTag(\Controller::addStaticUrlTo($s), false, $options->async) . "\n";
        }

        $script .= '<script>$.noConflict();</script>' . "\n";

        $css = array();
        $css['jquery_ui_css'] = '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';
        $css['search_console_main'] = '/system/modules/search_console/html/css/search_console.css';
        foreach($css as $c) {

            $options = \StringUtil::resolveFlaggedUrl($c);
            $script .= \Template::generateStyleTag(\Controller::addStaticUrlTo($c), $options->media) . "\n";
        }

        $buffer = str_replace('<head>', '<head>'.$script, $buffer);
        return $buffer;

    }

    public function disableModule($arrModules)
    {
        unset($arrModules['search_console']);
        return $arrModules;
    }

}