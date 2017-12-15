<?php


namespace Guave\SearchConsole\Modules;


use function Symfony\Component\HttpKernel\Tests\controller_func;

class SearchConsole extends \BackendModule
{

    protected $modules = array();

    protected $allowedSqlOperators = array(
        '||',
        'OR',
        'AND',
        '&&'
    );


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


            ////check for autocomplete from db field
            if(strstr($newSearch,'=') !== false && $module) {
                $dbFields = $this->getFields('',$module);

                if($dbFields) {
                    $dbAutocompleteExplode = explode('=', $newSearch, 2);
                    foreach ($dbFields as $data) {
                        if($data['value'] == $dbAutocompleteExplode[0]) {
                            $autoCompleteFromDbField = $this->getAutocompleteFromDbField($this->getTableNameFromModule($module), $dbAutocompleteExplode[0], $dbAutocompleteExplode[1]);
                            if(!empty($autoCompleteFromDbField)){
                                $return['items'] = array_merge($return['items'], $autoCompleteFromDbField);
                            }

                            break;
                        }
                    }
                }

            }


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

                                $linkString.= '<strong>'.$links[$i]['label'].'</strong>: ';
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

                            $linkString .= '<a data-activeModule="'.$activeModule.'" href="' . $link . '">'. $name. '</a>';


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

            if(strstr($moduleArray['module'], 'tl_') === false) {
				$table = 'tl_'.$moduleArray['module'];
			} else {
            	$table = $moduleArray['module'];
			}
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


                    $i = 0;
                    foreach($fragments as $fragment) {

                        $sqlOpperator = 'OR';

                        if($i != 0) {
                            if(in_array($fragments[$i-1], $this->allowedSqlOperators)) {
                                $sqlOpperator = $fragments[$i-1];
                            }
                        }


                        if(in_array($fragment, $this->allowedSqlOperators)) {
                            $i++;
                            continue;
                        }

                        $fields = $this->getFields($fragment, $m);

                        $re = '/(.*)(=|>|<|!=)(.*)/';
                        preg_match($re, html_entity_decode($fragment), $matches);

                        //do operator search
                        if($matches && $matches[3]) {
                            $check = $this->getFields($matches[1], $m);
                            if($check) {
                                if(isset($GLOBALS['TL_DCA'][$table]['fields'][$matches[1]])) {
                                    if(strstr($matches[3], '%') !== false) {
                                        $buildSelectFields[$sqlOpperator][] = $matches[1] . ' like ' . $this->getSqlEscape($matches[3], true);
                                    } else {
                                        $buildSelectFields[$sqlOpperator][] = $matches[1] . $matches[2] . $this->getSqlEscape($matches[3]);
                                    }
                                }
                            }
                            $i++;
                            continue;
                        }

                        //do like search on fragment
                        foreach($fields as $field) {
                            if($field == $fragment) {
                                if(isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                                    $buildSelectFields[$sqlOpperator][] = $field . ' like '.$this->getSqlEscape($fragment, true);
                                }
                            }
                        }
                        $i++;
                    }

                    $sqlOpperator = 'OR';

                    if(empty($buildSelectFields)) {
                        if ($moduleArray['defaultSearchFields']) {
                            foreach ($moduleArray['defaultSearchFields'] as $field) {
                                if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                                    $buildSelectFields[$sqlOpperator][] = $field . ' like ' . $this->getSqlEscape($fragment, true);
                                }
                            }
                        }
                    }

                    if(!empty($buildSelectFields)) {
                        $subQuery .= ' WHERE ';
                        $i = 0;
                        foreach ($buildSelectFields as $opperator => $wheres) {
                            if($i != 0) {
                                if(count($opperator) == 1) {
                                    $subQuery .= ' '.$opperator.' ';
                                } else {
                                    $subQuery .= ' OR ';
                                }
                            }
                            $subQuery .= '('.implode(' '.$opperator.' ', $wheres).')';
                            $i++;
                        }
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

                    	if($data['table'] || $data['module']) {
	                        $label = $this->getLabelOfModule(($data['table']) ? $data['table'] : $data['module']);
    	                    $data['label'] = $label;
						}
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
                            'value' => $field,
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

            if(strstr($GLOBALS['search_console']['modules'][$m]['module'], 'tl_') === false) {
            	$table = 'tl_'.$GLOBALS['search_console']['modules'][$m]['module'];
			} else {
            	$table = $GLOBALS['search_console']['modules'][$m]['module'];
			}
            if($GLOBALS['search_console']['modules'][$m]['table']) {
                $table = $GLOBALS['search_console']['modules'][$m]['table'];
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


//		if(!\BackendUser::getInstance()->authenticate()) {
//			return $buffer;
//		}

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
            $script .= '<script src="' . \Controller::addStaticUrlTo($s) . '"></script>' . "\n";
        }

        $script .= '<script>$.noConflict();</script>' . "\n";

        $css = array();
        if(!$hasJqueryUi) {
            $css['jquery_ui_css'] = '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';
        }
        $css['search_console_main'] = '/system/modules/search_console/html/css/search_console.css';
        foreach($css as $c) {
			$script .=  '<link rel="stylesheet" href="' . \Controller::addStaticUrlTo($c) . '">' . "\n";
        }

        $buffer = str_replace('<head>', '<head>'.$script, $buffer);
        return $buffer;

    }

    /**
     * @param $data
     * @return mixed
     */
    protected function getLabelOfModule($module)
    {

    	$table = $module;
        if(strstr($table, 'tl_') === false) {
			$table = 'tl_'.$table;
        }

		\Controller::loadDataContainer($module);


        $label = '';
        if ($module == 'tl_content') {
            $label = $GLOBALS['TL_LANG']['CTE']['alias'][0];
        } else if ($GLOBALS['TL_LANG']['CTE'][$module][0]) {
			$label = $GLOBALS['TL_LANG']['CTE'][$module][0];
		} else if ($GLOBALS['TL_LANG']['CTE'][$table][0]) {
			$label = $GLOBALS['TL_LANG']['CTE'][$table][0];
		} else if($GLOBALS['TL_LANG']['MOD'][$module][0]) {
			$label = $GLOBALS['TL_LANG']['MOD'][$module][0];
        } else if($GLOBALS['TL_LANG']['MOD'][$table][0]) {
			$label = $GLOBALS['TL_LANG']['MOD'][$table][0];
		}

		return $label;

    }

    public function getTableNameFromModule($module)
    {
        global $GLOBALS;

        $moduleArray = $GLOBALS['search_console']['modules'][$module];

        $table = '';

        if(strstr($moduleArray['module'], 'tl_') === false) {
            $table = 'tl_'.$moduleArray['module'];
        } else {
            $table = $moduleArray['module'];
        }
        if($moduleArray['table']) {
            $table = $moduleArray['table'];
        }

        return $table;


    }

    public function getAutocompleteFromDbField($table, $field, $search = null)
    {

        $query = '
                SELECT
                    '.$field.'
                FROM
                    '.$table.'
                GROUP BY
                    '.$field.'
                LIMIT 
                    20
                ';

        $result = \Database::getInstance()->prepare($query)->execute()->fetchAllAssoc();

        $return = array();

        if($result) {
            foreach ($result as $data) {

                if(!$data[$field]) {
                    continue;
                }

                $return[] = array(
                    'label' => $data[$field],
                    'value' => $field.'='.$data[$field],
                    'id' => $data[$field],
                    'category' => 'fields',
                );
            }
        }

        //cleanup
        if($search) {
            foreach($return as $k => $v) {
                if(substr($v['label'],0, strlen($search)) != $search) {
                    unset($return[$k]);
                }
            }
        }

        return $return;

    }


}