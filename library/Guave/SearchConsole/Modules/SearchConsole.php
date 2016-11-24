<?php


namespace Guave\SearchConsole\Modules;


class SearchConsole extends \BackendModule
{

    protected $modules = array();

    /**
     * @return string
     */
    public function generate() {

        $search = \Input::postRaw('search_console');
        if(!$search) {
            $search = \Input::get('search');
        }

        if(!$search) {
            $search = \Session::getInstance()->get('lastSearchConsoleSearch');
        }
        \Session::getInstance()->set('lastSearchConsoleSearch', $search);

        $result = $this->doSearch($search);

        $resultCount = count($result['items']);

        if($resultCount) {
            foreach ($result['items'] as $item) {
                if($item['action'] == 'redirect') {
                    if($item['value'] == $search) {
                        header('Location:'. $item['url']);
                        exit;
                    }
                }
            }
        } else if($resultCount == 1 && $result['items'][0]['action'] == 'redirect') {
           header('Location:'. $result['items'][0]['url']);
            exit;
        }


        if($result['resultCount'] == 1) {

            $result = $result['results'][0];
            header('Location:'.$result['link']);
            exit;
        }

        return $result['resultHtml'];

    }

    public function doSearch($search = null)
    {
        global $GLOBALS;

        if($search == '%' || strstr($search, '%' !== false)) {
            $search = '';
        }

        \Session::getInstance()->set('lastSearchConsoleSearch', $search);

        $search = str_replace(array(' = ', ' < ', ' != ', '>'), array('=', '<', '!=', '>'), $search);

        $return = array();
        $return['items'] = array();

        $fragements = explode(' ', $search);

        $shortCuts = $this->getAvailableShortcuts($search);
        if(!empty($shortCuts)){
            $return['items'] = array_merge($return['items'], $shortCuts);
        }

        $template = new \FrontendTemplate('search_console_result');
        $template->search = $search;
        $return['resultCount'] = 0;
        $return['results'] = array();

        if(strlen($search) >= 1 && empty($shortCuts)) {

            //get last fragment
            $newSearch = $fragements[count($fragements)-1];

            $module = $this->getModuleByShortcut($fragements[0]);
            $fields = $this->getFields($newSearch, $module);
            if(!empty($fields)){
                $return['items'] = array_merge($return['items'], $fields);
            }

            $query = $this->buildQuery($search, $module);
            $return['query'] = $query;
            if($query) {
                $data = \Database::getInstance()->query($query)->fetchAllAssoc();
                $return['resultCount'] = count($data);
                if($data) {

                    foreach($data as &$v) {

                        \Controller::loadDataContainer($v['tableName']);
                        $links = array();

                        if($GLOBALS['TL_DCA'][$v['tableName']]['fields']['pid'] && $v['pid']) {

                            $parents = array();

                            if($GLOBALS['TL_DCA'][$v['tableName']]['list']['sorting']['mode'] == 5) { //treeview
                                $parents = $this->getParentElements($v['pid'], $v['tableName'], $v['module']);
                            } else if($GLOBALS['TL_DCA'][$v['tableName']]['config']['ptable'] || $v['ptable']) {
                                $pTable = ($GLOBALS['TL_DCA'][$v['tableName']]['config']['ptable']) ? $GLOBALS['TL_DCA'][$v['tableName']]['config']['ptable'] : $v['ptable'];
                                $parents = $this->getParentElements($v['pid'], $pTable, str_replace('tl_', '',$pTable));

                            }

                            if(!empty($parents)) {
                                krsort($parents);
                                foreach ($parents as $parent) {
                                    $links[] = $parent;
                                }
                            }
                        }


                        #original link
                        if($v['module'] == 'theme') {
                            $v['module'] = 'themes';
                        }
                        $links[] = $v;

                        $linkString = '';
                        $activeModule = null;
                        $counter = 0;
                        $linksCount = count($links);

                        for($i = 0; $i < $linksCount; $i++) {
                            if($activeModule != $links[$i]['module']) {
                                if($activeModule != null) {
                                    $linkString .= ' | ';
                                }

                                if(!$links[$i]['label'] || strlen($links[$i]['label']) == 1) {
                                    $links[$i]['label'] = $links[$i]['module'];
                                }

                                $linkString.= '<strong>'.$links[$i]['label'].'</strong>:';
                                $activeModule = $links[$i]['module'];
                            } else {
                                if($counter <= $linksCount) {
                                    $linkString .= ' < ';
                                }
                            }


                            \Controller::loadDataContainer($links[$i]['tableName']);


                            $name = (($links[$i]['name']) ? $links[$i]['name'] : $links[$i]['id']);
                            foreach ($fragements as $fragement) {

                                $name =  preg_replace('#'. preg_quote($fragement) .'#i', '<mark>\\0</mark>', $name);

                            }

                            if($GLOBALS['TL_DCA'][$links[$i]['tableName']]['list']['sorting']['mode'] == 4) { //display child record

                                $do = str_replace('tl_', '', $pTable);
                                if($do == 'theme') {
                                    $do = 'themes';
                                };

                                $link = $this->getBaseUrl()
                                    . '?do=' . str_replace('tl_', '', $do)
                                    . '&table=' . $links[$i]['tableName'] . '&act=edit&id=' . $links[$i]['id']
                                    . '&ref=' . TL_REFERER_ID
                                    . '&rt=' . \RequestToken::get();
                            } else if($GLOBALS['TL_DCA'][$links[$i]['tableName']]['list']['sorting']['mode'] == 6) { //Displays the child records within a tree structure
                                $link = $this->getBaseUrl()
                                    . '?do=' . $links[$i]['module']
                                    . '&table=' . $GLOBALS['TL_DCA'][$links[$i]['tableName']]['config']['ctable'][0] . '&id=' . $links[$i]['id']
                                    . '&ref=' . TL_REFERER_ID
                                    . '&rt=' . \RequestToken::get();
                            } else {
                                $link = $this->getBaseUrl()
                                    . '?do=' . $links[$i]['module'] . '&act=edit&id=' . $links[$i]['id']
                                    . '&ref=' . TL_REFERER_ID
                                    . '&rt=' . \RequestToken::get();
                            }

                            $linkString .= '<a href="' . $link . '">'. $name. '</a>';


                            $counter++;
                        }


                        $v['links'] = $linkString;
                        if(!empty($links)) {
                            $v['link'] = $link;
                        }
                    }

                    $template->results = $data;
                    $return['results'] = $data;
                }
            }
        }

        $template->query = $query;
        $template->resultCount = $return['resultCount'];
        $result = $template->parse();
        $return['resultHtml'] = $result;


        return $return;


    }

   protected function getParentElements($pid, $table = null, $module) {

        if(!$table) {
            return;
        }


        if($module == 'theme') {
            $module = 'themes';
        }


//        echo $pid.'-'.$table.'<br />';

        $return = array();

       $query = 'SELECT * FROM '.$table.' WHERE id = ? LIMIT 1';
       $data = \Database::getInstance()->prepare($query)->execute($pid)->fetchAssoc();
       $allowedNameFields = array('name', 'title', 'alias');
       $nameField = 'id';
       if($data) {
           foreach ($allowedNameFields as $field) {
               if($data[$field]) {
                   $nameField = $field;
                   break;
               }
           }

           $return[] = array(
               'label' => $this->getLabelOfModule($module),
               'name' => $data[$nameField],
               'id' => $data['id'],
               'pid' => $data['pid'],
               'module' => $module,
               'tableName' => $table
           );
           if($data['pid'] > 0 && $GLOBALS['TL_DCA'][$table]['fields']['pid']) {


               if($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] == 5) { //treeview
                   $pTable = $table;
               } else if($GLOBALS['TL_DCA'][$table]['config']['ptable'] || $table) {
                   $pTable = ($GLOBALS['TL_DCA'][$table]['config']['ptable']) ? $GLOBALS['TL_DCA'][$table]['config']['ptable'] : $table;
                   $module = str_replace('tl_', '',$pTable);
               }


               $r = $this->getParentElements($data['pid'], $pTable, $module);
               if(!empty($r)) {
                   $return[] = $r[0];
               }
           }
       }

       return $return;

   }

    protected function buildQuery($search, $module = null, $fields = array()) {

        global $GLOBALS;

        $queries = array();

        foreach ($this->modules as $m => $data) {
            if($module) {
                if($module != $m) {
                    continue;
                }
            }

            $params = array();
            $moduleArray = $GLOBALS['search_console']['modules'][$m];

            if($moduleArray['doNotSearch']) {
                continue;
            }

            $table = 'tl_'.$moduleArray['module'];
            if($moduleArray['table']) {
                $table = $moduleArray['table'];
            }


            //remove shortcut from search
            $fragments = explode(' ', $search);
            if($fragments[0] && $moduleArray['shortcut'] == $fragments[0]) {
                $search = substr($search, strlen($fragments[0])+1);
                $fragments = explode(' ', $search);
            }


            if (isset($moduleArray['customSearch']) && is_array($moduleArray['customSearch'])) { //do custom query?
                $subQuery = \System::importStatic($moduleArray['customSearch'][0])->{$moduleArray['customSearch'][1]}($search);
                if($subQuery) {
                    $queries[] = '('.$subQuery.')';
                }
            } else {


                $nameFields = $this->getFields('', $m);
                $nameField = 'id';
                $allowedNameFields = array('name', 'title', 'alias');
                foreach ($nameFields as $field) {
                    if(in_array($field['id'], $allowedNameFields) && isset($GLOBALS['TL_DCA'][$table]['fields'][$field['id']])) {
                        $nameField = $field['id'];
                        break;
                    }
                }

                if($GLOBALS['TL_DCA'][$table]['fields']['pid']) {
                    $pid = ','.$m.'.pid';
                } else {
                    $pid = ',"" AS pid';
                }

                if($GLOBALS['TL_DCA'][$table]['fields']['ptable']) {
                    $ptable = ','.$m.'.ptable';
                } else {
                    $ptable = ',"" AS ptable';
                }

                $subQuery = 'SELECT '.$m.'.id'.$pid.$ptable.', '.$m.'.'.$nameField.' AS name, "'.$m.'" AS module, "'.$data['label'].'" AS label, "'.$table.'" AS tableName FROM '.$table.' AS '.$m;

                if($moduleArray) {

                    $buildSelectFields = array();


                    foreach($fragments as $fragment) {
                        $fields = $this->getFields($fragment, $m);

                        $re = '/(.*)(=|>|<|!=)(.*)/';
                        preg_match($re, html_entity_decode($fragment), $matches);

                        //do operator search
                        if($matches && $matches[3]) {
                            $check = $this->getFields($matches[1], $m);
                            if($check) {
                                if(isset($GLOBALS['TL_DCA'][$table]['fields'][$matches[1]])) {
                                    if($matches[2] == '=') {
                                        $buildSelectFields[] = $matches[1] . ' like ' . $this->getSqlEscape($matches[3], true);
                                    } else {
                                        $buildSelectFields[] = $matches[1] . $matches[2] . $this->getSqlEscape($matches[3]);
                                    }
                                }
                            }
                            continue;
                        }

                        //do like search on fragment
                        foreach($fields as $field) {
                            if($field == $fragment) {
                                if(isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                                    $buildSelectFields[] = $field . ' like '.$this->getSqlEscape($fragment, true);
                                }
                            }
                        }
                    }

                    if(empty($buildSelectFields)) {
                        if ($moduleArray['defaultSearchFields']) {
                            foreach ($moduleArray['defaultSearchFields'] as $field) {
                                if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                                    $buildSelectFields[] = $field . ' like ' . $this->getSqlEscape($fragment, true);
                                }
                            }
                        }
                    }

                    if(!empty($buildSelectFields)) {
                        $subQuery .= ' WHERE ';
                        $subQuery .= implode(' OR ', $buildSelectFields);
                    }
                    $subQuery .= ' LIMIT 20';

                    $queries[] = '('.$subQuery.')';

                }
            }

        }

        if($queries) {
            $query = 'SELECT allData.* FROM (';
            $query .= implode(' UNION ', $queries);
            $query .= ') AS allData LIMIT 20';
        } else {
            return null;
        }

        return $query;

    }

    public function getModuleLink($module, $id)
    {

        global $GLOBALS;

        $modules = $this->modules;
        $table = 't_'.$module;
        if($GLOBALS['search_console'][$module]['table']) {
            $table = $GLOBALS['search_console'][$module]['table'];
        }

        \Controller::loadDataContainer();
    }

    public function getSqlEscape($str, $like = false)
    {

        if(is_integer($str)) {
            if($like) {
                return '%'.$str.'%';
            } else {
                return $str;
            }
        } else {
            if($like) {
                return '"%'.$str.'%"';
            } else {
                return '"'.$str.'"';
            }
        }

    }

    public function search($action) {

        if($action != 'search_console') {
            return;
        }
        $search = \Input::postRaw('term');

        $return = $this->doSearch($search);


        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($return);

        exit;

    }

    public function getModules()
    {

        global $GLOBALS;

        if(empty($this->modules)) {

            $modules = array();
            if($GLOBALS['search_console']['modules'] && is_array($GLOBALS['search_console']['modules'])) {

                $user = \BackendUser::getInstance();

                \Controller::loadLanguageFile('default');
                foreach ($GLOBALS['search_console']['modules'] as $module => $data) {

                    if($user->isAdmin || $user->hasAccess($data['module'], 'modules')) {

                        $label = $this->getLabelOfModule(($data['table']) ? $data['table'] : $data['moudle']);

                        $data['label'] = $label;
                        $modules[$module] = $data;
                    }
                }
                $this->modules = $modules;
            }

            return $this->modules;

        }

        return $this->modules;

    }

    protected function compile()
    {
        // TODO: Implement compile() method.
    }

    public function getAvailableShortcuts($search = null)
    {
        $modules = $this->getModules();

        $return = array();
        foreach ($modules as $module => $data) {

            //go to
            if($data['enableGoTo']) {
                $return[] = array(
                    'label' => 'g '.$data['shortcut'].'-'.$data['label'],
                    'value' => 'g '.$data['shortcut'],
                    'id' => 'g '.$data['shortcut'],
                    'category' => 'cmd',
                    'action' => 'redirect',
                    'url' => $this->getBaseUrl().'?do='.$data['module'].'&ref='.TL_REFERER_ID.'&rt='.\RequestToken::get()
                );
            }

            //new
            if($data['enableNew']) {
                $return[] = array(
                    'label' => 'n '.$data['shortcut'].'-'.$data['label'],
                    'value' => 'n '.$data['shortcut'],
                    'id' => 'n '.$data['shortcut'],
                    'category' => 'cmd',
                    'action' => 'redirect',
                    'url' => $this->getBaseUrl().'?do='.$data['module'].'&act=paste&mode=create&ref='.TL_REFERER_ID.'&rt='.\RequestToken::get()
                );
            }

        }

        //cleanup
        if($search) {
            foreach($return as $k => $v) {
                if(substr($v['value'],0, strlen($search)) != $search) {
                    unset($return[$k]);
                }
            }
        }

        return $return;

    }

    public function getModuleByShortcut($shortcut)
    {

        $modules = $this->getModules();
        foreach($modules as $m => $data) {
            if($data['shortcut'] == $shortcut) {
                return $m;
            }
        }

        return null;

    }

    public function getFieldsFromDca($table, $search=null)
    {

        $return = array();

        \Controller::loadDataContainer($table);

        if($GLOBALS['TL_DCA'][$table]) {
            if($GLOBALS['TL_DCA'][$table]['fields']) {
                foreach($GLOBALS['TL_DCA'][$table]['fields'] as $field => $data) {

                    if(!in_array($field, $return)) {

                        $return[] = array(
                            'label' => $field.' '.$data['label'][0],
                            'value' => $field.' '.$data['label'][0],
                            'id' => $field,
                            'category' => 'fields',
                        );
                    }
                }
            }
        }

        //cleanup
        if($search) {
            foreach($return as $k => $v) {
                if(substr($v['value'],0, strlen($search)) != $search) {
                    unset($return[$k]);
                }
            }
        }

        return $return;


    }

    public function getFields($search, $module = null) {

        global $GLOBALS;

        $return = array();

        $modules = $this->getModules();

        foreach($modules as $m => $data) {

            if($module) { //only fields from module
                if($m != $module) {
                    continue;
                }
            }

            $table = 'tl_'.$GLOBALS['search_console']['modules'][$m]['module'];
            if($GLOBALS['search_console']['modules']['table']) {
                $table = $GLOBALS['search_console']['modules']['table'];
            }

            $fields = $this->getFieldsFromDca($table, $search);

            if($GLOBALS['TL_DCA'][$table]) {
                if($GLOBALS['TL_DCA'][$table]['fields']) {
                    foreach($fields as $data) {
                        $return[] = $data;
                    }
                }
            }

        }


        //cleanup
        if($search) {
            foreach($return as $k => $v) {
                if(substr($v['value'],0, strlen($search)) != $search) {
                    unset($return[$k]);
                }
            }
        }

        return $return;
    }

    public function getBaseUrl()
    {
        $url = substr(\Idna::decode(\Environment::get('base')), 0, -1).\Environment::get('requestUri');
        $explode = explode('?', $url);
        return $explode[0];
    }

    public function injectJavascript($buffer, $template)
    {

        if(!\BackendUser::getInstance()->authenticate()) {
            return $buffer;
        }

        $hasJquery = strstr($buffer, 'jquery.');
        $hasJqueryUi = strstr($buffer, 'jquery-ui.');


        $script = '';
        if(!$hasJquery) {
            $script .= '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>';
        }
        if (!$hasJqueryUi) {
            $script .= '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>';
        }
        $js = array();
        $js['search_console_main'] = '/system/modules/search_console/html/js/search_console.js';



        foreach($js as $s) {
            $options = \StringUtil::resolveFlaggedUrl($s);
            $script .= \Template::generateScriptTag(\Controller::addStaticUrlTo($s), false, $options->async) . "\n";
        }

        $script .= '<script>$.noConflict();</script>' . "\n";

        $css = array();
        if(!$hasJqueryUi) {
            $css['jquery_ui_css'] = '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';
        }
        $css['search_console_main'] = '/system/modules/search_console/html/css/search_console.css';
        foreach($css as $c) {

            $options = \StringUtil::resolveFlaggedUrl($c);
            $script .= \Template::generateStyleTag(\Controller::addStaticUrlTo($c), $options->media) . "\n";
        }

        $buffer = str_replace('<head>', '<head>'.$script, $buffer);
        return $buffer;

    }

    /**
     * @param $data
     * @return mixed
     */
    protected function getLabelOfModule($module )
    {

        if(strstr($module, 'tl_')) {
            $module = 'tl_'.$module;
        }

        if ($module == 'tl_content') {
            $label = $GLOBALS['TL_LANG']['CTE']['alias'][0];
            return $label;
        } else if ($GLOBALS['TL_LANG']['CTE'][$module][0]) {
            $label = $GLOBALS['TL_LANG']['CTE'][$module][0];
            return $label;
        } else {
            $label = $GLOBALS['TL_LANG']['MOD'][$module][0];
            return $label;
        }
    }


}