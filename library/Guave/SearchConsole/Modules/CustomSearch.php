<?php


namespace Guave\SearchConsole\Modules;

class CustomSearch extends SearchConsole {

    public function customSearchModule($search)
    {

        $fragments = explode(' ', $search);
        foreach($fragments as $fragment) {

            $moduleQuery = '
                            SELECT
                                module.id,
                                module.pid,
                                module.name,
                                "tl_themes" AS ptable,
                                "themes" AS module,
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
                            $buildSelectFields[] = $field . ' like '.$this->getSqlEscape($fragment, true);
                        }
                    }
                }
            }

            if(empty($buildSelectFields)) {
                $buildSelectFields[] = 'module.id like '.$this->getSqlEscape($fragment, true);
                $buildSelectFields[] = 'module.name like '.$this->getSqlEscape($fragment, true);
            }

            $moduleQuery .= ' WHERE ';
            $moduleQuery .= implode(' OR ', $buildSelectFields);
            $moduleQuery .= ' LIMIT 20';

            return $moduleQuery;

        }

    }
    
    public function customSearchPageArticle($search)
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
                            $buildSelectFields[] = $field . ' like '.$this->getSqlEscape($fragment, true);
                        }
                    }
                }
            } else {
                $buildSelectFields[] = 'a.id like '.$this->getSqlEscape($fragment, true);
                $buildSelectFields[] = 'a.title like '.$this->getSqlEscape($fragment, true);
                $buildSelectFields[] = 'a.alias like '.$this->getSqlEscape($fragment, true);
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
                            $buildSelectFields[] = $field . ' like '.$this->getSqlEscape($fragment, true);
                        }
                    }
                }
            } else {
                $buildSelectFields[] = 'p.id like '.$this->getSqlEscape($fragment, true);
                $buildSelectFields[] = 'p.title like '.$this->getSqlEscape($fragment, true);
                $buildSelectFields[] = 'p.alias like '.$this->getSqlEscape($fragment, true);
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