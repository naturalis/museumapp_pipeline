<?php

    class PipelineStats extends BaseClass
    {

        private $natuurwijzerURLaddOn = [
            "domain" => "https://natuurwijzer.naturalis.nl",
            "query_param" => "?standalone"
        ];

        const TABLE_NATUURWIJZER = 'natuurwijzer';

        public function init()
        {
            $this->_connectDatabase();
        }

        public function setNatuurwijzer()
        {
            $this->natuurwijzer = $this->_getMySQLSource(self::TABLE_NATUURWIJZER);

            $d=[];
            foreach ($this->natuurwijzer as $key => $val)
            {
                if (empty($val["taxon"]) && empty($val["exhibition_rooms"]))
                {
                    continue;
                }

                foreach (["taxon","exhibition_rooms","image_urls"] as $key)
                {
                    $val["_".$key]=json_decode($val[$key],true);
                    unset($val[$key]);
                }

                $val["_full_url"] =
                    $this->natuurwijzerURLaddOn["domain"] . 
                    $val["url"] .
                    $this->natuurwijzerURLaddOn["query_param"];

                // eliminating doubles
                $d[$val["title"]]=$val;
            }

            $this->natuurwijzer = array_values($d);
        }


        public function getNatuurwijzer()
        {
            return [
                "data" => $this->natuurwijzer,
                "count" => count($this->natuurwijzer),
                "harvest_date" => $this->natuurwijzer[0]["inserted"]
            ];
        }
    }