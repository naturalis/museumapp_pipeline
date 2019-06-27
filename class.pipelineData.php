<?php


    class PipelineData extends BaseClass
    {
        private $db_credentials;
        private $db;
        private $SQLitePath = [ "selector" => null, "squares" => null ];
        private $masterList;
        private $CRS;
        private $IUCN;
        private $natuurwijzer;
        private $topstukken;
        private $ttik;
        private $imageSelection;
        private $imageSquares;
        private $exhibitionRooms_NW;
        private $exhibitionRooms_ML;
        private $overallTextOccurrences=[];
        private $overallTextUsage=[];
        private $taxonList;
        private $brahmsUnitIDs;
        private $dateStamp;
        private $languageDefault="nl";
        private $rawDocData=[];
        private $document=[];
        private $documentId=1;

        private $softMaxTaxonArticles=3;
        private $hardMaxTotalArticles=5;

        // https://nl.wikipedia.org/wiki/Rode_Lijst_van_de_IUCN
        private $IUCN_statusTranslations = [
            "EX" => "Uitgestorven",
            "EW" => "Uitgestorven in het wild",
            "CR" => "Ernstig bedreigd (kritiek)",
            "EN" => "Bedreigd",
            "VU" => "Kwetsbaar",
            "NT" => "Gevoelig",
            "LR/nt" => "Gevoelig",
            "CD" => "Van bescherming afhankelijk",
            "LR/cd" => "Van bescherming afhankelijk",
            "LC" => "Niet bedreigd (veilig)",
            "LR/lc" => "Niet bedreigd (veilig)",
            "DD" => "Onzeker",
            "NE" => "Niet geëvalueerd"
        ];

        private $IUCN_trendTranslations = [
            "Unknown" => "Onbekend",
            "Stable" => "Stabiel",
            "Decreasing" => "Afnemend",
            "Increasing" => "Toenemend"
        ];

        // masterlijst => natuurwijzer
        private $exhibitionRoomsTranslations = [
            "Dinos" => "Dinotijd",
            "Verleiding" => "De verleiding",
            "IJstijd" => "De ijstijd",
            "Dood" => "De dood",
            "Ontmoeting" => "Leven (de Ontmoeting)",
            "LiveScience" => "LiveScience",
            "Aarde" => "De aarde",
            "Mens" => "De vroege mens",
            "Schatkamer 1.0" => "",
            "Schatkamer 2.0" => "",
        ];

        private $brahmsPrefixes = [
            "WAG", "U", "NYIMG",
            "L", "BALGOOY", "XXXXXXXXXXXX",
            "W", "USUS", "US", "S", "P", 
            "O", "LL", "LISM", "LBV","K", 
            "GXMI", "DSCNG", "DSCNCJ", "DSCN", 
            "BR", "BISH", "AMS", "AMD",
        ];

        private $natuurwijzerURLaddOn = [
            "domain" => "https://natuurwijzer.naturalis.nl",
            "query_param" => "?standalone"
        ];

        private $topstukkenURLaddOn = [
            "query_param" => "?standalone"
        ];

        private $debug_masterListSCnames = [
            // "Ursus maritimus",
            // "Canis lupus"
        ];

        const TABLE_MASTER = 'tentoonstelling';
        const TABLE_CRS = 'crs';
        const TABLE_IUCN = 'iucn';
        const TABLE_NATUURWIJZER = 'natuurwijzer';
        const TABLE_TOPSTUKKEN = 'topstukken';
        const TABLE_TTIK = 'ttik';

        const DATA_ERROR = 1;
        const DATA_MESSAGE = 2;        

        public function init()
        {
            $this->checkJsonPaths();   
            $this->_connectDatabase();
        }

        public function setMasterList()
        {
            $this->masterList = $this->_getMySQLSource(self::TABLE_MASTER);

            if (!empty($this->debug_masterListSCnames))
            {                
                $b=$this->debug_masterListSCnames;
                $this->masterList = array_filter($this->masterList,
                function($a) use ($b)
                {
                    return in_array($a["SCname"],$b);
                });
            }
        }

        public function setCRS()
        {
            $this->CRS = $this->_getMySQLSource(self::TABLE_CRS);
        }

        public function setIUCN()
        {
            $this->IUCN = $this->_getMySQLSource(self::TABLE_IUCN);
        }

        public function setNatuurwijzer()
        {
            $this->natuurwijzer = $this->_getMySQLSource(self::TABLE_NATUURWIJZER);

            $d=[];
            foreach ($this->natuurwijzer as $key => $val)
            {
                if (empty($val["taxon"]) && $val["exhibition_rooms"])
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

        public function setTopstukken()
        {
            $this->topstukken = $this->_getMySQLSource(self::TABLE_TOPSTUKKEN);

            $d=[];
            foreach($this->topstukken as $val)
            {
                $val["_registrationNumber_ic"]=strtolower($val["registrationNumber"]);
                unset($val["description"]);

                $val["_full_url"] =
                    $val["url"] .
                    $this->topstukkenURLaddOn["query_param"];

                $d[]=$val;
            }
            $this->topstukken = $d;
        }

        public function setTTIK()
        {
            $this->ttik = $this->_getMySQLSource(self::TABLE_TTIK);

            $d=[];
            foreach($this->ttik as $val)
            {
                $val["classification"]=json_decode($val["classification"],true);
                $val["description"]=json_decode($val["description"],true);
                $key=array_search(strtolower($val["rank"]), array_column($val["classification"], 'rank'));
                $val["_nomen"]=$val["classification"][$key]["taxon"];
                $val["_nomen_ic"]=strtolower($val["_nomen"]);
                $val["_taxon_ic"]=strtolower($val["taxon"]);
                $d[]=$val;
            }
            $this->ttik = $d;
        }

        public function setExhibitionRooms()
        {
            $this->exhibitionRooms_NW=[];
            foreach ($this->natuurwijzer as $val)
            {
                foreach($val["_exhibition_rooms"] as $room)
                {
                    $this->exhibitionRooms_NW[$room]=$room;
                }
            }

            $this->exhibitionRooms_NW = array_values($this->exhibitionRooms_NW);
        }

        public function setImageSelection()
        {
            $this->imageSelection=[];
            $db = new SQLite3($this->SQLitePath["selector"], SQLITE3_OPEN_READWRITE);
            $sql = $db->prepare('SELECT * FROM selected_urls');
            $results = $sql->execute();
            while($row = $results->fetchArray())
            {
                $this->imageSelection[$row["unitid"]]=array_map(function($a)
                {
                    return [ "url" => $a ];
                },
                json_decode($row["urls"]));
            }
            $db->close();

        }

        // TODO: STUB
        public function setImageSquares()
        {
            $this->imageSquares=[];
            return;

            $db = new SQLite3($this->SQLitePath["squares"], SQLITE3_OPEN_READWRITE);
            $sql = $db->prepare('SELECT * FROM squared_images');
            $results = $sql->execute();
            while($row = $results->fetchArray())
            {
                $this->imageSquares[]=[
                    $row["scientific_name"],
                    $row["unitid"],
                    $row["filename"]
                ];
            }
            $db->close();
        }


        public function getMasterList()
        {
            return $this->masterList;
        }
        public function getCRS()
        {
            return $this->CRS;
        }
        public function getIUCN()
        {
            return $this->IUCN;
        }
        public function getNatuurwijzer()
        {
            return $this->natuurwijzer;
        }
        public function getTopstukken()
        {
            return $this->topstukken;
        }
        public function getTtik()
        {
            return $this->ttik;
        }
        public function getImageSelection()
        {
            return $this->imageSelection;
        }
        public function getImageSquares()
        {
            return $this->imageSquares;
        }

        public function makeTaxonList()
        {
            $this->taxonList=[];
            foreach ($this->masterList as $key => $val)
            {
                $val["SCname"]=trim($val["SCname"]);
                if (empty($val["SCname"]))
                {
                    continue;
                }
                $this->taxonList[$val["SCname"]]["taxon"] = $val["SCname"];
            }

            uasort($this->taxonList,function($a,$b)
            {
                $a=strtolower($a["taxon"]);
                $b=strtolower($b["taxon"]);
                return ($a==$b ? 0 : (($a<$b) ? -1 : 1));
            });
        }

        public function addTaxonomyToTL()
        {
            $d=[];
            foreach ($this->taxonList as $val)
            {
                $key = array_search(strtolower($val["taxon"]), array_column($this->ttik, '_taxon_ic'));

                if ($key===false)
                {
                    $key = array_search(strtolower($val["taxon"]), array_column($this->ttik, '_nomen_ic'));
                }

                if ($key===false)
                {
                    $this->log(sprintf("no TTIK match found for taxon %s",$val["taxon"]),self::DATA_ERROR,"TTIK");
                }
                else
                {
                    $val["taxonomy"]=
                        [
                            "classification"=>$this->ttik[$key]["classification"],
                            "uninomial"=>$this->ttik[$key]["uninomial"],
                            "infra_specific_epithet"=>$this->ttik[$key]["infra_specific_epithet"],
                            "authorship"=>$this->ttik[$key]["authorship"],
                            "taxon"=>$this->ttik[$key]["taxon"],
                            "rank"=>$this->ttik[$key]["rank"],
                            "english"=>$this->ttik[$key]["english"],
                            "dutch"=>$this->ttik[$key]["dutch"],
                            "_nomen"=>$this->ttik[$key]["_nomen"],
                            "_nomen_ic"=>$this->ttik[$key]["_nomen_ic"],
                            "_taxon_ic"=>$this->ttik[$key]["_taxon_ic"],
                            "taxon_id"=>$this->ttik[$key]["taxon_id"],
                            "ttik_id"=>$this->ttik[$key]["id"],
                        ];
                }
                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function addObjectDataToTL()
        {
            $d=[];
            foreach ($this->taxonList as $val)
            {
                $matches = array_filter($this->masterList,function($a) use ($val)
                {
                    return $val["taxon"]==$a["SCname"];
                });

                if (empty($matches))
                {
                    $this->log(sprintf("no masterList match found for taxon %s (!?)",$val["taxon"]),self::DATA_ERROR);
                }
                else
                {
                    foreach ($matches as $match)
                    {
                        $val["object_data"][]=
                            [
                                "unitid"=>$match["Registratienummer"],
                                "exhibition_room"=>$match["Zaal"],
                                "location"=>$match["Zaaldeel"],
                            ];
                        $this->exhibitionRooms_ML[$this->masterList[$key]["Zaal"]]=$this->masterList[$key]["Zaal"];
                    }
                }
                $d[]=$val;

            }
            $this->taxonList = $d;
            $this->exhibitionRooms_ML=array_values($this->exhibitionRooms_ML);
        }

        public function addCRSToTL()
        {
            $m=[];
            foreach ($this->taxonList as $key => $val)
            {
                $d=[];
                foreach ($val["object_data"] as $object)
                {
                    $matches = array_filter($this->CRS,function($a) use ($object)
                    {
                        return $object["unitid"]==$a["REGISTRATIONNUMBER"];
                    });

                    if (empty($matches))
                    {
                        $this->log(sprintf("no CRS match found for unitid %s",$object["unitid"]),self::DATA_ERROR,"CRS");
                    }
                    else
                    {
                        foreach ($matches as $match)
                        {
                            if (!empty($match["URL"]))
                            {
                                if (!filter_var($match["URL"], FILTER_VALIDATE_URL))
                                {
                                    $this->log(sprintf("invalid image URL for CRS record %s: %s",$object["id"],$match["URL"]),self::DATA_ERROR,"CRS");
                                }
                                else
                                {
                                    $object["images"][]=
                                        [
                                            "url"=>$match["URL"]
                                        ];
                                }
                            }
                            if (!empty($match["FULLSCIENTIFICNAME"]))
                            {
                                $val["taxonomy"]["_crs"]=$match["FULLSCIENTIFICNAME"];
                            }

                        }
                    }
                    $d[]=$object;
                }

                $m[$key]=$val;
                $m[$key]["object_data"]=$d;
            }
            $this->taxonList=$m;
        }

        public function addIUCNToTL()
        {
            $d=[];
            foreach ($this->taxonList as $key => $val)
            {
                $key = array_search($val["taxon"], array_column($this->IUCN, "scientific_name"));

                if ($key===false && isset($val["taxonomy"]) && isset($val["taxonomy"]["_nomen"]))
                {
                    $key = array_search($val["taxonomy"]["_nomen"], array_column($this->IUCN, "scientific_name"));
                }

                if ($key===false)
                {
                    $this->log(sprintf("no IUCN match found for taxon %s",$val["taxon"]),self::DATA_ERROR,"IUCN");
                }
                else
                {
                    $val["IUCN"]=
                        [
                            "category"=>$this->IUCN[$key]["category"],
                            "population_trend"=>$this->IUCN[$key]["population_trend"],
                            "_category_label"=>$this->IUCN_statusTranslations[$this->IUCN[$key]["category"]],
                            "_trend_label"=>$this->IUCN_trendTranslations[$this->IUCN[$key]["population_trend"]]
                        ];
                }
                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function resolveExhibitionRooms()
        {
            foreach ($this->exhibitionRooms_ML as $val)
            {
                if(!isset($this->exhibitionRoomsTranslations[$val]))
                {
                    $this->unknownRooms["unknown_room_in_masterlist"][]=$val;
                }
                else
                if(empty($this->exhibitionRoomsTranslations[$val]))
                {
                    $this->unknownRooms["masterlist_room_not_in_natuurwijzer"][]=$val;
                }
            }

            foreach ($this->exhibitionRooms_NW as $val)
            {
                if(!isset(array_flip($this->exhibitionRoomsTranslations)[$val]))
                {
                    $this->unknownRooms["natuurwijzer_room_not_in_masterlist"][]=$val;
                }
            }
        }

        public function addTTIKTextsToTL()
        {
            $d=[];
            foreach ($this->taxonList as $val)
            {
                $key = array_search(strtolower($val["taxon"]), array_column($this->ttik, '_taxon_ic'));

                if ($key===false)
                {
                    $key = array_search(strtolower($val["taxon"]), array_column($this->ttik, '_nomen_ic'));
                }

                if ($key===false)
                {
                    //
                }
                else
                if (!empty($this->ttik[$key]["description"]))
                {
                    $val["texts"]["ttik"]=$this->ttik[$key]["description"];
                }
                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function addNatuurwijzerTextsToTL()
        {
            $d=[];

            foreach ($this->taxonList as $val)
            {
                if ($val["taxonomy"]["classification"])
                {
                    foreach ($val["taxonomy"]["classification"] as $taxon)
                    {
                        $needle = $taxon["taxon"];
                        $needle_rank = $taxon["rank"];

                        $matched = array_filter(
                            array_map(function($a) use ($needle,$needle_rank)
                            {
                                if (isset($a["_taxon"]))
                                {
                                    if (array_search($needle, $a["_taxon"]))
                                    {
                                        $a["_matched_taxon"]=[ "taxon"=>$needle, "rank"=>$needle_rank ];
                                    }
                                }
                                return $a;
                            }, $this->natuurwijzer ),
                            function($a) 
                            {
                                return isset($a["_matched_taxon"]);
                            }
                        );

                        if (!empty($matched))
                        {
                            foreach ($matched as $mKey => $match)
                            {
                                if (isset($this->overallTextOccurrences[$match["id"]]))
                                {
                                    $this->overallTextOccurrences[$match["id"]]++;
                                }
                                else
                                {
                                    $this->overallTextOccurrences[$match["id"]]=1;
                                }
                                $val["natuurwijzer_texts_matches"]["taxa"][$match["id"]]=$match["_matched_taxon"];
                                unset($match["_matched_taxon"]);
                                $val["texts"]["natuurwijzer"][$match["id"]]=$match;
                            }
                        }
                    }
                }

                if ($val["object_data"])
                {
                    foreach ($val["object_data"] as $object)
                    {
                        if (isset($this->exhibitionRoomsTranslations[$object["exhibition_room"]]))
                        {
                            $needle = $this->exhibitionRoomsTranslations[$object["exhibition_room"]];

                            $matched = array_filter(
                                array_map(function($a) use ($needle)
                                {
                                    if (isset($a["_exhibition_rooms"]))
                                    {
                                        if (array_search($needle, $a["_exhibition_rooms"]))
                                        {
                                            $a["_matched_room"]=$needle;
                                        }
                                    }
                                    return $a;
                                }, $this->natuurwijzer ),
                                function($a) 
                                {
                                    return isset($a["_matched_room"]);
                                }
                            );
                            if (!empty($matched))
                            {
                                foreach ($matched as $mKey => $match)
                                {
                                    if (isset($this->overallTextOccurrences[$match["id"]]))
                                    {
                                        $this->overallTextOccurrences[$match["id"]]++;
                                    }
                                    else
                                    {
                                        $this->overallTextOccurrences[$match["id"]]=1;
                                    }
                                    
                                    $val["natuurwijzer_texts_matches"]["rooms"][$match["id"]]=$match["_matched_room"];
                                    unset($match["_matched_room"]);
                                    $val["texts"]["natuurwijzer"][$match["id"]]=$match;
                                }
                            }
                        }
                    }
                }

                $d[]=$val;
            }
            $this->taxonList = $d;
            arsort($this->overallTextOccurrences);

        }

        public function addTopstukkenTextsToTL()
        {
            $d=[];

            foreach ($this->taxonList as $val)
            {
                if ($val["object_data"])
                {
                    foreach ($val["object_data"] as $object)
                    {
                        $key = array_search($object["unitid"], array_column($this->topstukken, 'registrationNumber'));

                        if ($key===false)
                        {
                            $key = array_search(strtolower($val["unitid"]), array_column($this->topstukken, '_registrationNumber_ic'));
                        }

                        if ($key!==false)
                        {
                            $val["topstuk"]=$this->topstukken[$key];
                        }
                    }

                }

                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function effectuateImageSelection()
        {
            $d=[];

            foreach ($this->taxonList as $val)
            {
                if ($val["object_data"])
                {

                    foreach ($val["object_data"] as $key => $object)
                    {
                        if (isset($this->imageSelection[$object["unitid"]]))
                        {
                            $val["object_data"][$key]["images"]=
                                array_intersect($this->imageSelection[$object["unitid"]],$object["images"]);
                        }
                    }    
                }

                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function makeLinksSelection()
        {
            $d=[];
            foreach ($this->taxonList as $val)
            {
                $linked_articles=[];

                if (isset($val["natuurwijzer_texts_matches"]))
                {
                    $unique_taxa_article_keys = [];
                    $unique_rooms_article_keys = [];

                    if (isset($val["natuurwijzer_texts_matches"]["taxa"]))
                    {
                        $unique_taxa_article_keys = array_unique(array_keys($val["natuurwijzer_texts_matches"]["taxa"]));
                    }

                    if (isset($val["natuurwijzer_texts_matches"]["rooms"]))
                    {
                        $unique_rooms_article_keys = array_unique(array_keys($val["natuurwijzer_texts_matches"]["rooms"]));
                        $unique_rooms_article_keys = array_filter(
                            $unique_rooms_article_keys,
                            function($a) use ($unique_rooms_article_keys,$unique_taxa_article_keys)
                            {
                                return !in_array($a, array_intersect($unique_rooms_article_keys,$unique_taxa_article_keys));
                            });
                    }

                    if (count($unique_taxa_article_keys)>0)
                    {
                        $rKeys=[];
                        foreach (array_reverse($val["natuurwijzer_texts_matches"]["taxa"],true) as $key=>$tKey)
                        {
                            $rKeys[$tKey["rank"]][]=$key;
                        }

                        foreach ($rKeys as $rank => $keys)
                        {
                            uasort($keys, function($a,$b)
                            {
                                $aS = isset($this->overallTextUsage[$a]) ? $this->overallTextUsage[$a] : 0;   
                                $bS = isset($this->overallTextUsage[$b]) ? $this->overallTextUsage[$b] : 0;
                                if ($aS == $bS)
                                {
                                    $aS = isset($this->overallTextOccurrences[$a]) ? $this->overallTextOccurrences[$a] : 0;   
                                    $bS = isset($this->overallTextOccurrences[$b]) ? $this->overallTextOccurrences[$b] : 0;
                                    return (($aS==$bS) ? 0 : (($aS < $bS) ? -1 : 1));

                                }
                                return ($aS < $bS) ? -1 : 1;
                            });

                            foreach ($keys as $aKey)
                            {

                                if (count($linked_articles)>=$this->softMaxTaxonArticles)
                                {
                                    if (($this->hardMaxTotalArticles - count($linked_articles)) <= count($unique_rooms_article_keys))
                                    {
                                        break;
                                    }
                                }

                                $linked_articles[] = 
                                    $val["texts"]["natuurwijzer"][$aKey] +
                                    [ "_link_origin"  => "taxon" ];

                                if (isset($this->overallTextUsage[$aKey]))
                                {
                                    $this->overallTextUsage[$aKey]++;
                                }
                                else
                                {
                                    $this->overallTextUsage[$aKey]=1;
                                }
                            }
                        }
                    }

                    if (isset($val["natuurwijzer_texts_matches"]["rooms"]))
                    {
                        uasort($unique_rooms_article_keys, function($a,$b)
                        {
                            $aS = isset($this->overallTextUsage[$a]) ? $this->overallTextUsage[$a] : 0;   
                            $bS = isset($this->overallTextUsage[$b]) ? $this->overallTextUsage[$b] : 0;
                            if ($aS == $bS)
                            {
                                $aS = isset($this->overallTextOccurrences[$a]) ? $this->overallTextOccurrences[$a] : 0;   
                                $bS = isset($this->overallTextOccurrences[$b]) ? $this->overallTextOccurrences[$b] : 0;
                                return (($aS==$bS) ? 0 : (($aS < $bS) ? -1 : 1));

                            }
                            return ($aS < $bS) ? -1 : 1;
                        });

                        foreach ($unique_rooms_article_keys as $aKey)
                        {
                            if (count($linked_articles)>=$this->hardMaxTotalArticles)
                            {
                                break;
                            }

                            $linked_articles[] =
                                $val["texts"]["natuurwijzer"][$aKey] +
                                [ "_link_origin"  => "room" ];

                            if (isset($this->overallTextUsage[$aKey]))
                            {
                                $this->overallTextUsage[$aKey]++;
                            }
                            else
                            {
                                $this->overallTextUsage[$aKey]=1;
                            }
                        }
                        
                    }

                    unset($val["natuurwijzer_texts_matches"]);
                    $val["texts"]["natuurwijzer"] = $linked_articles;
                }
                $d[]=$val;
            }
            $this->taxonList = $d;

        }

        public function generateJsonDocuments()
        {
            $this->dateStamp = date("c");
            $this->deleteAllPreviousJsonFiles( "preview" );

            foreach ($this->taxonList as $val)
            {
                $this->rawDocData = $val;

                $this->_addDocumentMetaData();
                $this->_addDocumentHeaderImage();
                $this->_addDocumentMainInfo();
                $this->_addDocumentNames();
                $this->_addDocumentContent();
                $this->_addDocumentObjects();
                $this->_addDocumentLinks();

                if (!$this->checkMinimumRequirements())
                {
                    $this->log(sprintf("skipping %s",$this->document["names"]["scientific"]),self::DATA_MESSAGE,"generator");
                    continue;
                }

                $filename = $this->_generateUniqueFilename( $this->jsonPath["preview"], $this->rawDocData["taxon"] );

                if (file_put_contents($filename, json_encode($this->document)))
                {
                    $this->log(sprintf("wrote %s",$filename),self::DATA_MESSAGE,"generator");
                }
                else
                {
                    $this->log(sprintf("could not write  %s",$filename),self::DATA_ERROR,"generator");
                }

                $this->document=[];
            }                
        }

        public function getBrahmsUnitIDsFromObjectData()
        {
            foreach ($this->taxonList as $key => $val)
            {
                if (isset($val["object_data"]))
                {
                    foreach ($val["object_data"] as $data)
                    {
                        if (!isset($data["images"]) || count($data["images"])==0)
                        {
                            $b=preg_split('/([^a-zA-Z])/', $data["unitid"]);
                            if (in_array($b[0], $this->brahmsPrefixes))
                            {
                                $this->brahmsUnitIDs[]=$data["unitid"];
                            }
                        }
                    }
                }
            }
            return $this->brahmsUnitIDs;
        }

        public function setDatabaseCredentials( $p )
        {
            $this->db_credentials = $p;
        }

        public function setSQLitePath( $source, $path )
        {
            if (array_key_exists($source, $this->SQLitePath))
            {
                if (file_exists($path))
                {
                    $this->SQLitePath[$source] = $path;
                }
                else
                {
                    throw new Exception(sprintf("SQLite path doesn't exist: %s",$path), 1);                    
                }
            }
            else
            {
                throw new Exception(sprintf("unknown SQLite source: %s",$source), 1);                    
            }
        }

        public function setJsonPath( $state, $path )
        {
            if (array_key_exists($state, $this->jsonPath))
            {
                if (file_exists($path))
                {
                    $this->jsonPath[$state] = rtrim($path,"/") . "/";
                }
                else
                {
                    throw new Exception(sprintf("JSON-path doesn't exist: %s",$path), 1);                    
                }
            }
            else
            {
                throw new Exception(sprintf("unknown JSON state: %s",$state), 1);                    
            }
        }

        private function _connectDatabase()
        {
            $this->db = new mysqli(
                $this->db_credentials["host"],
                $this->db_credentials["user"],
                $this->db_credentials["pass"]
            );

            $this->db->select_db($this->db_credentials["database"]);
            $this->db->set_charset("utf8");
        }

        private function _getMySQLSource( $source )
        {
            $sql = $this->db->query("select * from " . $source);
            $list=[];
            while ($row = $sql->fetch_assoc())
            {
                $list[]=$row;
            }
            return $list;
        }

        private function _checkJsonPaths()
        {
            foreach ($this->jsonPath as $state => $path)
            {
                if (is_null($path))
                {
                    throw new Exception(sprintf("JSON-path not set: %s",$state), 1);                    
                }
            }
        }

        private function _deleteAllPreviousJsonFiles( $state )
        {
            $files = glob($this->jsonPath[$state] . '*.json');
            foreach($files as $file)
            {
                if(is_file($file))
                {
                    unlink($file);
                }
            }
        }

        private function _generateUniqueFilename( $path, $name, $ext = "json" )
        {
            $i=0;
            $filename_base = strtolower(
                str_replace(
                    [" ",".",",","/","\"",")","(","!","?","&"],
                    "_",
                    preg_replace('/[[:^print:]]/', '', $name)));

            $filename = $filename_base . "_" . $i;

            while(file_exists($path . $filename . "." . $ext))
            {
                $filename = $filename_base . "_" . $i++;
            }
            return $path . $filename . "." . $ext;                
        }

        private function _addDocumentMetaData()
        {
            $this->document["id"] = $this->documentId++;
            $this->document["created"] = $this->dateStamp;
            $this->document["language"] = $this->languageDefault;
        }

        private function _addDocumentHeaderImage()
        {
            $block_name="header_image";

            // TODO: STUB
            $this->rawDocData["header_image"] = [ "url" => "http://145.136.242.65:8080/stubs/placeholder.jpg" ];

            if (isset($this->rawDocData["header_image"]))
            {
                $this->document[$block_name] = $this->rawDocData["header_image"];
            }
        }

        private function _addDocumentMainInfo()
        {
            $block_name="main_info";
            $this->document[$block_name]=[];

            $this->document[$block_name][]=
                [ "key" => "Wetenschappelijke naam",
                  "value" =>
                    isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["taxon"]) ? 
                        $this->rawDocData["taxonomy"]["taxon"] : 
                        $this->rawDocData["taxon"]
                ];

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["dutch"]))
            {
                $this->document[$block_name][]=
                    [ "key" => "Nederlandse naam",
                      "value" => $this->rawDocData["taxonomy"]["dutch"]
                    ];
            }

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["english"]))
            {
                $this->document[$block_name][]=
                    [ "key" => "Engelse naam",
                      "value" => $this->rawDocData["taxonomy"]["english"]
                    ];
            }

            if (isset($this->rawDocData["texts"]) && isset($this->rawDocData["texts"]["ttik"]))
            {
                foreach ($this->rawDocData["texts"]["ttik"] as $key => $val)
                {
                    if ($val["title"]=="Leefgebied" || $val["title"]=="Leefperiode")
                    {
                        $this->document[$block_name][]=
                            [ "key" => $val["title"],
                              "value" => $val["body"]
                            ];
                    }
                }
            }

            uasort($this->document[$block_name],function($a,$b)
            {
                $order=[
                    "Wetenschappelijke naam"=>0,
                    "Nederlandse naam"=>1,
                    "Engelse naam"=>2,
                    "Leefgebied"=>3,
                    "Leefperiode"=>4
                ];

                $a = $order[$a["key"]];
                $b = $order[$b["key"]];
                return (($a == $b) ? 0 : (($a < $b) ? -1 : 1));
            });
        }

        private function _addDocumentNames()
        {
            $block_name="names";

            $this->document[$block_name]["scientific"] = 
                isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["taxon"]) ? 
                    $this->rawDocData["taxonomy"]["taxon"] : 
                    $this->rawDocData["taxon"];

            $this->document[$block_name]["scientific_display"] = 
                isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["_nomen"]) && isset($this->rawDocData["taxonomy"]["authorship"]) ? 
                    "<i>" . $this->rawDocData["taxonomy"]["_nomen"] ."</i> " .$this->rawDocData["taxonomy"]["authorship"] : 
                    $this->document["names"]["scientific"];

            $this->document[$block_name]["nomen"] = 
                isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["_nomen"]) ? 
                    $this->rawDocData["taxonomy"]["_nomen"] : 
                    $this->rawDocData["taxon"];

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["dutch"]))
            {
                $this->document[$block_name]["dutch"] = $this->rawDocData["taxonomy"]["dutch"];
            }

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["english"]))
            {
                $this->document[$block_name]["english"] = $this->rawDocData["taxonomy"]["english"];
            }
        }

        private function _addDocumentContent()
        {
            $block_name="content";

            unset($this->document[$block_name]);

            if (isset($this->rawDocData["texts"]) && isset($this->rawDocData["texts"]["ttik"]))
            {
                $t=[];
                foreach ($this->rawDocData["texts"]["ttik"] as $key => $val)
                {
                    if ($val["title"]!="Leefgebied" && $val["title"]!="Leefperiode")
                    {
                        if (isset($val["title"]) && $val["title"]!="Beschrijving")
                        {                                
                            $t[] = [ "type" => "h1", "text" => $val["title"] ]; 
                        }
                        if (isset($val["body"]))
                        {                                
                            $t[] = [ "type" => "paragraph", "text" => $val["body"], "_title" => $val["title"] ];
                        }
                    }                        
                }

                uasort($t,function($a,$b)
                {
                    if ($a["_title"]=="Beschrijving")
                    {
                        return -1;
                    }              
                    return (($a == $b) ? 0 : (($a < $b) ? -1 : 1));
                });

                array_walk($t,function(&$a)
                {
                    $b=[];
                    foreach ($a as $key => $val)
                    {
                        if (substr($key,0,1)=="_")
                        {
                            continue;
                        }
                        $b[$key]=$val;
                    }
                   $a=$b;
                });

                $this->document[$block_name]=$t;
            }
        }

        private function _addDocumentObjects()
        {
            $block_name="objects";

            unset($this->document[$block_name]);

            if (isset($this->rawDocData["object_data"]))
            {
                foreach ($this->rawDocData["object_data"] as $object)
                {
                    $topstuk_image=null;

                    $o=[];
                    $o["id"]=$object["unitid"];

                    if (isset($object["images"]))
                    {
                        foreach ($object["images"] as $image)
                        {
                            $o["images"][]=$image;

                            if (is_null($topstuk_image))
                            {
                                $topstuk_image=$image;
                            }
                        }
                    }

                    $o["data"] = [
                        [ "label" => "Registratienummer", "text" => $object["unitid"] ],
                        [ "label" => "Locatie", "text" => $this->exhibitionRoomsTranslations[$object["exhibition_room"]]]
                    ];

                    if (isset($this->rawDocData["topstuk"]) && $this->rawDocData["topstuk"]["_registrationNumber_ic"]==strtolower($object["unitid"]))
                    {
                        $o["topstuk_link"][ "url" ] = $this->rawDocData["topstuk"]["_full_url"];

                        if (!is_null($topstuk_image))
                        {
                            $o["topstuk_link"][ "image" ] = $topstuk_image["url"];
                        }
                    }
                    $this->document[$block_name][]=$o;
                }
            }
        }

        private function _addDocumentLinks()
        {
            $block_name="links";

            unset($this->document[$block_name]);

            if (isset($this->rawDocData["texts"]) && isset($this->rawDocData["texts"]["natuurwijzer"]))
            {
                $links=[];
                foreach ($this->rawDocData["texts"]["natuurwijzer"] as $val)
                {
                    $links[] = [
                        "title" => $val["title"],
                        "description" => $val["intro_text"],
                        // TODO: take out str_replace
                        "url_image" => str_replace("natuurwijzer-acc.naturalis.nl","natuurwijzer.naturalis.nl",$val["_image_urls"]["original"]),
                        "url_link" => $val["_full_url"],
                        // "_link_origin" => $val["_link_origin"],
                        // "_id" => $val["id"],
                    ];
                }

                $this->document[$block_name]=$links;
            }
        }

        private function checkMinimumRequirements()
        {




            if (
                (!isset($this->document["names"]["scientific"])) ||
                (!isset($this->document["names"]["dutch"])) ||
                (!isset($this->document["content"]) || count($this->document["content"])<1) ||
                (!isset($this->document["objects"]) || count($this->document["objects"])<1) ||
                (!isset($this->document["links"]) || count($this->document["links"])<1)
                )
            {
                return false;
            }
            else
            {
                return true;
            }
        }

    }