<?php


namespace Guave\SearchConsole\Modules;

class CustomSearch extends SearchConsole {

    public function customSearchModule($search, &$params)
    {

        $fragments = explode(' ', $search);
        foreach($fragments as $fragment) {

            $moduleQuery = '
                            SELECT
                                module.id,
                                module.pid,
                                module.name,
                                "tl_themes" AS ptable,
                                "modules" AS module,
                                "'.$this->getLabelOfModule('module').'" AS label,
                                "tl_module" AS tableName
                            FROM
                                tl_module AS module
                            ';
            $fields = $this->getFieldsFromDca('tl_module', $fragment);

            $buildSelectFields = array();
            if($fields) {
                //do like search on fragment
                foreach($fields as $f) {
                    $field = $f['id'];
                    if($field == $fragment) {
                        if(isset($GLOBALS['TL_DCA']['tl_article']['fields'][$field])) {
                            $buildSelectFields[] = $field . ' like '.$this->getLikeSql($fragment);
                        }
                    }
                }
            }

            if(empty($buildSelectFields)) {
                $buildSelectFields[] = 'module.id like ?';
                $buildSelectFields[] = 'module.name like ?';
                $params[] = $this->getLikeSql($fragment);
                $params[] = $this->getLikeSql($fragment);
            }

            $moduleQuery .= ' WHERE ';
            $moduleQuery .= implode(' OR ', $buildSelectFields);
            $moduleQuery .= ' LIMIT 20';

            return $moduleQuery;

        }

    }

    /**
     * @param string $search
     * @param array $params
     * @return string
     */
    public function customSearchPageArticle($search, &$params)
    {

        $fragments = explode(' ', $search);
        foreach($fragments as $fragment) {

            //articleQeury
            $articleFields = $this->getFieldsFromDca('tl_article', $fragment);
            $articleQuery = 'SELECT a.id, a.pid, a.title FROM tl_article AS a';
            $buildSelectFields = array();
            if($articleFields) {
                //do like search on fragment
                foreach($articleFields as $f) {
                    $field = $f['id'];
                    if($field == $fragment) {
                        if(isset($GLOBALS['TL_DCA']['tl_article']['fields'][$field])) {
                            $buildSelectFields[] = $field . ' like ?';
                            $params[] = $this->getLikeSql($fragment);
                        }
                    }
                }
            } else {
                $buildSelectFields[] = 'a.id like ?';
                $buildSelectFields[] = 'a.title like ?';
                $buildSelectFields[] = 'a.alias like ?';
                $params[] = $this->getLikeSql($fragment);
                $params[] = $this->getLikeSql($fragment);
                $params[] = $this->getLikeSql($fragment);
            }

            if(!empty($buildSelectFields)) {
                $articleQuery .= ' WHERE ';
                $articleQuery .= implode(' OR ', $buildSelectFields);
            }

            //pageQuery
            $pageFields = $this->getFieldsFromDca('tl_page', $fragment);
            $pageQuery = 'SELECT pa.id, pa.pid, pa.title FROM tl_page AS p';
            $pageQuery .= ' LEFT JOIN tl_article AS pa ON pa.pid = p.id';
            $buildSelectFields = array();
            if($pageFields) {
                //do like search on fragment
                foreach($pageFields as $f) {
                    $field = $f['id'];
                    if($field == $fragment) {
                        if(isset($GLOBALS['TL_DCA']['tl_page']['fields'][$field])) {
                            $buildSelectFields[] = $field . ' like ?';
                            $params[] = $this->getLikeSql($fragment);
                        }
                    }
                }
            } else {
                $buildSelectFields[] = 'p.id like ?';
                $buildSelectFields[] = 'p.title like ?';
                $buildSelectFields[] = 'p.alias like ?';
                $params[] = $this->getLikeSql($fragment);
                $params[] = $this->getLikeSql($fragment);
                $params[] = $this->getLikeSql($fragment);
            }

            if(!empty($buildSelectFields)) {
                $pageQuery .= ' WHERE ';
                $pageQuery .= implode(' OR ', $buildSelectFields);
            }

            $query = '
                     SELECT
                        groupedData.id,
                        groupedData.pid,
                        groupedData.title AS name,
                        "" AS ptable,
                        "article" AS module,
                        "'.$this->getLabelOfModule('article').'" AS label,
                        "tl_article" AS tableName
                    FROM
                        (
                            ('.$pageQuery.') UNION ('.$articleQuery.')
                        
                        ) AS groupedData   
                    GROUP BY
                        groupedData.id
                    LIMIT 20
                    ';

//            echo $articleQuery.'<br /><hr>';
//            echo $pageQuery.'<br /><hr>';
//            echo $query.'<br /><hr>';

            return $query;


        }

    }

}