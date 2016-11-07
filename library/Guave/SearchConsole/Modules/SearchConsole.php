<?php


namespace Guave\SearchConsole\Modules;


class SearchConsole extends \BackendModule
{

    protected $modules = array();

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
            header('Location:'. '/contao/main.php?do='.$result['module'].'&act=edit&id='.$result['id'].'&ref='.TL_REFERER_ID.'&rt='.\RequestToken::get());
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
                    $template = new \FrontendTemplate('search_console_result');

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

                                $linkString.= $links[$i]['label'].':';
                                $activeModule = $links[$i]['module'];
                            } else {
                                if($counter <= $linksCount) {
                                    $linkString .= ' < ';
                                }
                            }

                            if($GLOBALS['TL_DCA'][$v['tableName']]['list']['sorting']['mode'] == 4) { //display child record
                                $linkString .= ' 
                                <a '
                                    . 'href="/contao/main.php?'
                                    . 'do=' . str_replace('tl_', '', $pTable)
                                    . '&table=tl_'.$links[$i]['module'] . '&act=edit&id='.$links[$i]['id']
                                    . '&ref=' . TL_REFERER_ID
                                    . '&rt=' . \RequestToken::get() . '">'
                                    . (($links[$i]['name']) ? $links[$i]['name'] : $links[$i]['id'])
                                    . '</a>';
                            } else {
                                $linkString .= ' 
                                <a '
                                    . 'href="/contao/main.php?'
                                    . 'do=' . $links[$i]['module'] . '&act=edit&id='.$links[$i]['id']
                                    . '&ref=' . TL_REFERER_ID
                                    . '&rt=' . \RequestToken::get() . '">'
                                    . (($links[$i]['name']) ? $links[$i]['name'] : $links[$i]['id'])
                                    . '</a>';
                            }


                            $counter++;
                        }


                        $v['links'] = $linkString;
                    }

                    $template->results = $data;
                    $template->query = $query;
                    $template->resultCount = $return['resultCount'];
                    $result = $template->parse();
                    $return['results'] = $data;
                    $return['resultHtml'] = $result;
                }
            }


        }

        return $return;


    }

   protected function getParentElements($pid, $table = null, $module) {

        if(!$table) {
            return;
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
               'label' => ($GLOBALS['TL_LANG']['MOD'][$module][0]) ? $GLOBALS['TL_LANG']['MOD'][$module][0] : $module,
               'name' => $data[$nameField],
               'id' => $data['id'],
               'pid' => $data['pid'],
               'module' => $module,
           );
           if($data['pid'] > 0 && $GLOBALS['TL_DCA'][$table]['fields']['pid']) {
               $r = $this->getParentElements($data['pid'], $table, $module);
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

                $fragments = explode(' ', $search);
                foreach($fragments as $fragment) {
                    $fields = $this->getFields($fragment, $m);

                    $re = '/(.*)(=|>|<|!=)(.*)/';
                    preg_match($re, html_entity_decode($fragment), $matches);

                    //do operator search
                    if($matches) {
                        $check = $this->getFields($matches[1], $m);
                        if($check) {
                            if(isset($GLOBALS['TL_DCA'][$table]['fields'][$matches[1]])) {
                                $buildSelectFields[] = $matches[1] . $matches[2] . $this->getSqlEscape($matches[3]);
                            }
                        }
                        continue;
                    }

                    //do like search on fragment
                    foreach($fields as $field) {
                        if($field == $fragment) {
                            if(!in_array($field, $buildSelectFields)) {
                                if(isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                                    $buildSelectFields[] = $field . ' like '.$this->getSqlEscape($fragment, true);
                                }
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

        if($queries) {
            $query = 'SELECT allData.* FROM (';
            $query .= implode(' UNION ', $queries);
            $query .= ') AS allData';
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
                        $data['label'] = $GLOBALS['TL_LANG']['MOD'][$data['module']][0];
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
                    'url' => '/contao/main.php?do='.$data['module'].'&ref='.TL_REFERER_ID.'&rt='.\RequestToken::get()
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
                    'url' => '/contao/main.php?do='.$data['module'].'&act=paste&mode=create&ref='.TL_REFERER_ID.'&rt='.\RequestToken::get()
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


}