<?php

    class TtikData extends BaseClass
    {

        private $page = 0;
        public $pageSize = 100;
        private $results = [];
        private $totalResults = 0;
        
        const TABLE = 'ttik';
        const TABLE_TRANSLATIONS = 'ttik_translations';

        public function init()
        {
            $this->connectDatabase();
        }

        public function setPage($page)
        {
            if (is_numeric($page))
            {
                $this->page = $page;                
            }
        }

        public function getBatch()
        {
            $this->results=[];
            $this->doQuery();
            return [
                'results' => $this->results,
                'totalResults' => $this->totalResults
            ];
        }

        private function doQuery()
        {
            $query = 
                "select
                    a.taxon,
                    nl.description as nl_text,
                    en.description as en_text
                    
                from
                    " . self::TABLE . " a

                left join
                    " . self::TABLE_TRANSLATIONS. " nl
                        on a.taxon_id = nl.taxon_id
                        and nl.language_code = 'nl'

                left join
                    " . self::TABLE_TRANSLATIONS . " en
                        on a.taxon_id = en.taxon_id
                        and en.language_code = 'en'

                where
                    (
                        nl.description is not null or
                        en.description is not null
                    )

                order by
                    a.taxon

                limit 
                    " . $this->pageSize . "

                offset 
                    " . ($this->page * $this->pageSize)
                ;

            try {
                $sql = $this->db->query($query);
                while ($row = $sql->fetch_assoc())
                {
                    $this->results[]= $row;
                }
            } catch (Exception $e) {
                $this->log($e->getMessage(),self::SYSTEM_ERROR,"ttik_data");
            }

            $query = 
                "select
                    count(*) as total
                from
                    " . self::TABLE . " a
                left join
                    " . self::TABLE_TRANSLATIONS. " nl
                        on a.taxon_id = nl.taxon_id
                        and nl.language_code = 'nl'
                left join
                    " . self::TABLE_TRANSLATIONS . " en
                        on a.taxon_id = en.taxon_id
                        and en.language_code = 'en'
                where
                    ifnull(length(nl.description),0) > 0
                ";

            try {
                $sql = $this->db->query($query);
                $row = $sql->fetch_assoc();
                $this->totalResults = $row["total"];
            } catch (Exception $e) {
                $this->log($e->getMessage(),self::SYSTEM_ERROR,"ttik_data");
            }
            
        }

    }