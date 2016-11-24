<?php


namespace Guave\SearchConsole\Helper;


class Helper
{

    /**
     * do not display the module in the navigation
     * @param $arrModules
     * @return mixed
     */
    public function disableModule($arrModules)
    {
        unset($arrModules['search_console']);
        return $arrModules;
    }

}