<?php

    class PipelineStats extends BaseClass
    {

        private $natuurwijzerURLaddOn = [
            "domain" => "https://natuurwijzer.naturalis.nl",
            "query_param" => "?standalone"
        ];

        private $metric="leerobjecten";

        const TABLE_NATUURWIJZER = 'natuurwijzer';
        const TABLE_TAXONLIST = 'taxonlist';
        const TABLE_TTIK = 'ttik';
        const TABLE_MASTERLIST = 'tentoonstelling';
        const TABLE_CRS = 'crs';
        
        public function init()
        {
            $this->connectDatabase();
        }

        public function getLeerobjecten()
        {
            $this->natuurwijzer = $this->getMySQLSource(self::TABLE_NATUURWIJZER);

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

            usort($d, function($a,$b)
            {
                return $a["title"] > $b["title"];
            });

            $this->natuurwijzer = array_values($d);

            return [
                "data" => $this->natuurwijzer,
                "count" => count($this->natuurwijzer),
                "harvest_date" => $this->natuurwijzer[0]["inserted"]
            ];
        }


        public function getTaxa()
        {
            $nw_links = json_decode($this->getManagementData( "nw-dekking" ),true);

            $this->taxonlist = $this->getMySQLSource(self::TABLE_TAXONLIST);
            $d=[];
            foreach ($this->taxonlist as $val)
            {
                $key = array_search($val["taxon"], array_column($nw_links, "taxon"));

                if ($key!==false)
                {
                    $val = [ "taxon" => $val["taxon"], "links" => $nw_links[$key]["links"] ];
                }

                $d[]=$val;
            }
            
            return $d;

        }

        public function getManagementData( $type )
        {
            $db = new SQLite3($this->SQLitePath["management"], SQLITE3_OPEN_READWRITE);

            if ($type=="nw-dekking")
            {
                $sql = $db->prepare('SELECT * FROM natuurwijzer_dekking');
                $results = $sql->execute();
                $d=[];
                while($row = $results->fetchArray())
                {
                    $d[]=[ "taxon" => $row["taxon"], "links" => json_decode($row["links"]) ];
                }
            }
            else
            if ($type=="ttik-content-dekking")
            {
                $sql = $db->prepare('SELECT * FROM ttik_content_dekking');
                $results = $sql->execute();
                $d=[];
                while($row = $results->fetchArray())
                {
                    $d[]=[ "taxon" => $row["taxon"], "status" => $row["status"] ];
                }
            }
            else
            if ($type=="taxa_per_room")
            {
                $sql = $db->prepare('SELECT * FROM taxa_per_room');
                $results = $sql->execute();
                $d=[];
                while($row = $results->fetchArray())
                {
                    $d[]=$row;
                }
                return $d;
            }
            else
            if ($type=="taxa_overall")
            {
                $sql = $db->prepare('SELECT * FROM taxa_overall');
                $results = $sql->execute();
                return $results->fetchArray();
            }


            $db->close();
            return json_encode($d);

        }

        public function getObjectsPerRoom()
        {

            $a = $this->getMySQLQuery("
                select 
                    Zaal, count(distinct Registratienummer) total
                from ".self::TABLE_MASTERLIST." 
                left join ".self::TABLE_CRS." 
                    on Registratienummer = REGISTRATIONNUMBER 
                where URL is not null 
                and Zaal is not null 
                and Zaal !='' 
                and `SCname controle` is not null
                and `SCname controle` != ''  
                group by Zaal
            ");

            $b = $this->getMySQLQuery("
                select 
                    Zaal, count(distinct Registratienummer) total
                from ".self::TABLE_MASTERLIST."  
                left join ".self::TABLE_CRS." 
                    on Registratienummer = REGISTRATIONNUMBER 
                where URL is null 
                and Zaal is not null 
                and Zaal !='' 
                and `SCname controle` is not null
                and `SCname controle` != ''  
                group by Zaal
            ");

            foreach (array_unique(array_column(array_merge($a,$b),"Zaal")) as $zaal)
            {
                $c[] = [
                    "zaal" =>$zaal, 
                    "unique_unitids_with_image_URL" => $a[array_search($zaal, array_column($a, "Zaal"))]["total"],
                    "unique_unitids_without_image_URL" => $b[array_search($zaal, array_column($b, "Zaal"))]["total"]
                ];
            
            }

            return $c;
        }
    }