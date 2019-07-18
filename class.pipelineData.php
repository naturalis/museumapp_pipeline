<?php

    class PipelineData extends BaseClass
    {
        private $SQLitePath = [ "selector" => null, "squares" => null  ];
        private $masterList;
        private $CRS;
        private $brahms;
        private $IUCN;
        private $NBA;
        private $natuurwijzer;
        private $topstukken;
        private $ttik;
        private $imageSelection;
        private $imageSquares;
        private $leenobjecten;
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

        private $softMaxTaxonArticles=5;
        private $hardMaxTotalArticles=8;

        private $debug_masterListSCnames = [
            // "Giraffa reticulata",
            // "Puma concolor",
            // "Abrus precatorius",
            // "Aschiphasma annulipes Westwood, 1834",
            // "Amazilia fimbriata",
            // "Chrysolampis mosquitus",
            // "Colibri coruscans",
            // "Pelophylax klepton esculentus",
            // "Pelophylax spec.",
            // "Accipiter nisus",
            // "Ursus maritimus",
            // "Canis lupus"
        ];

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

        // natuurwijzer => publiek
        private $exhibitionRoomsPublic = [
            "Leven (de Ontmoeting)" => "Leven",
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

        private $squaredImagePlaceholderURL;
        private $squaredImageURLRoot;
        private $leenobjectImageURLRoot;

        const TABLE_MASTER = 'tentoonstelling';
        const TABLE_CRS = 'crs';
        const TABLE_BRAHMS = 'brahms';
        const TABLE_IUCN = 'iucn';
        const TABLE_NATUURWIJZER = 'natuurwijzer';
        const TABLE_TOPSTUKKEN = 'topstukken';
        const TABLE_TTIK = 'ttik';
        const TABLE_NBA = 'nba';
        const TABLE_LEENOBJECTEN = 'leenobjecten';
        const TABLE_TAXONLIST = 'taxonlist';

        // const TABLE_MASTER_NAME_COL = 'SCname';
        const TABLE_MASTER_NAME_COL = 'SCname controle';
        const PREFIX_LEENOBJECTEN = 'leen.';

        public function init()
        {
            $this->checkJsonPaths();
            $this->_checkImageURLs();
            $this->connectDatabase();
        }

        public function setSquaredImagePlaceholderURL( $url )
        {
            $this->squaredImagePlaceholderURL = $url;
        }

        public function setSquaredImageURLRoot( $url )
        {
            $this->squaredImageURLRoot = $url;
        }

        public function setLeenobjectImageURLRoot( $url )
        {
            $this->leenobjectImageURLRoot = $url;
        }

        public function setMasterList()
        {
            $this->masterList = $this->getMySQLSource(self::TABLE_MASTER);

            $this->masterList=
                array_map(function($a)
                {
                    $a["_is_leenobject"] =
                        substr($a["Registratienummer"],0,strlen(self::PREFIX_LEENOBJECTEN))==self::PREFIX_LEENOBJECTEN;

                    $prefix = @explode(".",$a["Registratienummer"])[0];

                    $a["_is_brahms"] = !empty($prefix) && in_array($prefix, $this->brahmsPrefixes);

                    return $a;
                },$this->masterList);

            if (!empty($this->debug_masterListSCnames))
            {                
                $b=$this->debug_masterListSCnames;
                $this->masterList = array_filter($this->masterList,
                function($a) use ($b)
                {
                    return in_array($a[self::TABLE_MASTER_NAME_COL],$b);
                });
            }

            $this->log(sprintf("read %s masterlist entries",count($this->masterList)),self::DATA_MESSAGE,"init");
        }

        public function setCRS()
        {
            $this->CRS = $this->getMySQLSource(self::TABLE_CRS);

            $d=[];
            foreach($this->CRS as $val)
            {
                $val["URL"]=str_replace("http://", "https://", $val["URL"]);
                $d[]=$val;
            }
            $this->CRS = $d;
            $this->log(sprintf("read %s CRS entries",count($this->CRS)),self::DATA_MESSAGE,"init");
        }

        public function setBrahms()
        {
            $this->brahms = $this->getMySQLSource(self::TABLE_BRAHMS);
            $this->log(sprintf("read %s Brahms entries",count($this->brahms)),self::DATA_MESSAGE,"init");
        }

        public function setIUCN()
        {
            $this->IUCN = $this->getMySQLSource(self::TABLE_IUCN);
            $this->log(sprintf("read %s IUCN entries",count($this->IUCN)),self::DATA_MESSAGE,"init");
        }

        public function setNBA()
        {
            $this->NBA = $this->getMySQLSource(self::TABLE_NBA);

            $d=[];
            foreach ($this->NBA as $key => $val)
            {
                if (empty($val["document"]))
                {
                    continue;
                }
                $d[]=[ "unitid" => $val["unitid"], "document"  => json_decode($val["document"],true) ];
            }

            $this->NBA = $d;
            $this->log(sprintf("read %s NBA entries",count($this->NBA)),self::DATA_MESSAGE,"init");
        }

        public function setNatuurwijzer()
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
                    $val["_".$key]=array_map(function($a)
                        {
                            return trim($a);
                        }, (array)json_decode($val[$key],true));
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
            $this->log(sprintf("read %s natuurwijzer entries",count($this->natuurwijzer)),self::DATA_MESSAGE,"init");
        }

        public function setTopstukken()
        {
            $this->topstukken = $this->getMySQLSource(self::TABLE_TOPSTUKKEN);

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
            $this->log(sprintf("read %s topstukken entries",count($this->topstukken)),self::DATA_MESSAGE,"init");
        }

        public function setTTIK()
        {
            $this->ttik = $this->getMySQLSource(self::TABLE_TTIK);

            $d=[];
            foreach($this->ttik as $val)
            {
                $val["classification"]=
                    array_map(function($a)
                    {
                        $a["_taxon_ic"]=strtolower($a["taxon"]);
                        return $a;
                    },(array)json_decode($val["classification"],true));

                if (!empty($val["synonyms"]))
                {
                    $val["synonyms"]=
                        array_map(function($a)
                        {
                            return
                                trim(
                                    ($a["uninomial"] ?? "" ) . " " .
                                    ($a["specific_epithet"] ?? "" ) . " " .
                                    ($a["infra_specific_epithet"] ?? "" )
                                );
                        },(array)json_decode($val["synonyms"],true));                    
                }

                $val["description"]=json_decode($val["description"],true);

                // $key=array_search(strtolower($val["rank"]), array_column($val["classification"], 'rank'));
                // $val["_nomen"]=$val["classification"][$key]["taxon"];

                $val["_nomen"] = trim( $val["uninomial"] . " " . $val["specific_epithet"] . " " . $val["infra_specific_epithet"]);
                $val["_nomen_ic"]=strtolower($val["_nomen"]);
                $val["_taxon_ic"]=strtolower($val["taxon"]);

                foreach(["english","dutch","scientific"] as $language)
                {
                    $val[$language]=json_decode($val[$language],true);

                    if (!empty($val[$language]))
                    {
                        $val[$language]=
                            array_map(function($a)
                            {
                                $a["_name_ic"]=strtolower($a["name"]);
                                return $a;
                            },$val[$language]);
                    }

                    if (isset($val[$language]) && $language!="scientific")
                    {
                        $pKey = array_search("isPreferredNameOf", array_column($val[$language], "nametype"));
                        $val["_".$language."_main"]=$pKey ? $val[$language][$pKey]["name"] : $val[$language][0]["name"];
                    }
                    else
                    {
                        $val["_".$language."_main"]=null;
                    }    
                }

                $d[]=$val;
            }
            $this->ttik = $d;
            $this->log(sprintf("read %s TTIK entries",count($this->ttik)),self::DATA_MESSAGE,"init");
        }

        public function setExhibitionRooms()
        {
            $this->exhibitionRooms_NW=[];

            foreach ($this->natuurwijzer as $val)
            {
                if (!isset($val["_exhibition_rooms"]))
                {
                    continue;
                }

                foreach($val["_exhibition_rooms"] as $room)
                {
                    $this->exhibitionRooms_NW[$room]=$room;
                }
            }

            $this->exhibitionRooms_NW = array_values($this->exhibitionRooms_NW);
            $this->log(sprintf("found %s exhibition rooms",count($this->exhibitionRooms_NW)),self::DATA_MESSAGE,"init");
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
            $this->log(sprintf("found %s image selector sets",count($this->imageSelection)),self::DATA_MESSAGE,"init");
        }

        public function setImageSquares()
        {
            $this->imageSquares=[];
            $db = new SQLite3($this->SQLitePath["squares"], SQLITE3_OPEN_READWRITE);
            $sql = $db->prepare('SELECT * FROM squared_images');
            $results = $sql->execute();
            while($row = $results->fetchArray())
            {
                $this->imageSquares[]=[
                    "scientific_name" => $row["scientific_name"],
                    "_scientific_name_ic" => strtolower($row["scientific_name"]),
                    "unitid" => $row["unitid"],
                    "filename" => $row["filename"]
                ];
            }
            $db->close();
            $this->log(sprintf("found %s squared image entries",count($this->imageSquares)),self::DATA_MESSAGE,"init");
        }

        public function setLeenObjecten()
        {
            $this->leenobjecten = $this->getMySQLSource(self::TABLE_LEENOBJECTEN);
            $this->leenobjecten =
                array_map(function($a)
                {
                    $a["_registratienummer_ic"]=strtolower($a["registratienummer"]);
                    $a["_afbeeldingen"]=array_map(function($a)
                        {
                            return [ "url" => $this->leenobjectImageURLRoot . $a ];
                        },array_filter((array)json_decode($a["afbeeldingen"])));
                    return $a;
                },$this->leenobjecten);

            $this->log(sprintf("found %s leenobjecten entries",count($this->leenobjecten)),self::DATA_MESSAGE,"init");
        }


        public function getMasterList()
        {
            return [
                "data" => $this->masterList,
                "count" => count($this->masterList),
                "harvest_date" => $this->masterList[0]["inserted"]
            ];
        }

        public function getCRS()
        {
            return [
                "data" => $this->CRS,
                "count" => count($this->CRS),
                "harvest_date" => $this->CRS[0]["inserted"]
            ];
        }

        public function getBrahms()
        {
            return [
                "data" => $this->brahms,
                "count" => count($this->brahms),
                "harvest_date" => $this->brahms[0]["inserted"]
            ];
        }

        public function getLeenObjecten()
        {
            return [
                "data" => $this->leenobjecten,
                "count" => count($this->leenobjecten),
                "harvest_date" => $this->leenobjecten[0]["inserted"]
            ];
        }

        public function getIUCN()
        {
            return [
                "data" => $this->IUCN,
                "count" => count($this->IUCN),
                "harvest_date" => $this->IUCN[0]["inserted"]
            ];
        }

        public function getNBA()
        {
            return [
                "data" => $this->NBA,
                "count" => count($this->NBA),
                "harvest_date" => $this->NBA[0]["inserted"]
            ];
        }

        public function getNatuurwijzer()
        {
            return [
                "data" => $this->natuurwijzer,
                "count" => count($this->natuurwijzer),
                "harvest_date" => $this->natuurwijzer[0]["inserted"]
            ];
        }

        public function getTopstukken()
        {
            return [
                "data" => $this->topstukken,
                "count" => count($this->topstukken),
                "harvest_date" => $this->topstukken[0]["inserted"]
            ];
        }

        public function getTtik()
        {
            return [
                "data" => $this->ttik,
                "count" => count($this->ttik),
                "harvest_date" => $this->ttik[0]["inserted"]
            ];
        }

        public function getImageSelection()
        {
            return [
                "data" => $this->imageSelection,
                "count" => count($this->imageSelection)
            ];
        }

        public function getImageSquares()
        {
            return [
                "data" => $this->imageSquares,
                "count" => count($this->imageSquares)
            ];
        }

        public function getTaxonList()
        {
            return [
                "data" => $this->taxonList,
                "count" => count($this->taxonList)
            ];
        }

        public function makeTaxonList()
        {
            $this->taxonList=[];
            foreach ($this->masterList as $key => $val)
            {
                $val[self::TABLE_MASTER_NAME_COL]=trim($val[self::TABLE_MASTER_NAME_COL]);
                if (empty($val[self::TABLE_MASTER_NAME_COL]))
                {
                    continue;
                }
                $this->taxonList[$val[self::TABLE_MASTER_NAME_COL]]["taxon"] = $val[self::TABLE_MASTER_NAME_COL];

                if (preg_match('/(\s){1,}(spec\.|sp\.)$/', $val[self::TABLE_MASTER_NAME_COL]))
                {
                    $this->taxonList[$val[self::TABLE_MASTER_NAME_COL]]["taxon"] = preg_replace('/(\s){1,}(spec\.|sp\.)$/', '', $val[self::TABLE_MASTER_NAME_COL]);
                    $this->taxonList[$val[self::TABLE_MASTER_NAME_COL]]["taxon_original"] = $val[self::TABLE_MASTER_NAME_COL];
                }
            }

            uasort($this->taxonList,function($a,$b)
            {
                $a=strtolower($a["taxon"]);
                $b=strtolower($b["taxon"]);
                return ($a==$b ? 0 : (($a<$b) ? -1 : 1));
            });

            $this->log(sprintf("distilled %s taxa from masterlist",count($this->taxonList)),self::DATA_MESSAGE,"init");
        }

        public function addTaxonomyToTL()
        {
            $d=[];
            $matched=0;

            foreach ($this->taxonList as $val)
            {
                $key = array_search(strtolower($val["taxon"]), array_column($this->ttik, "_taxon_ic"));
                $match=[ "matched_on" => "taxon" ];

                if ($key===false)
                {
                    $key = array_search(strtolower($val["taxon"]), array_column($this->ttik, "_nomen_ic"));
                    $match=[ "matched_on" => "nomen" ];
                }

                if ($key===false)
                {
                    foreach ($this->ttik as $tVal)
                    {
                        if(isset($tVal["scientific"]))
                        {
                            $key = array_search(strtolower($val["taxon"]),array_column($tVal["scientific"], "_name_ic"));
                            if ($key!==false)
                            {
                                $match=[ "matched_on" => "synonym", "value" => $tVal["taxon"] ];
                                break;
                            }
                        }
                    }
                }

                if ($key===false)
                {
                    foreach ($this->ttik as $tVal)
                    {
                        if(isset($tVal["classification"]))
                        {                            
                            $key = array_search(strtolower($val["taxon"]),array_column($tVal["classification"], '_taxon_ic'));
                            if ($key!==false)
                            {
                                $match=[
                                    "matched_on" => "higher_taxon",
                                    "value" => $tVal["taxon"],
                                    "rank" => $tVal["classification"][$key]["rank"]
                                ];
                                break;
                            }
                        }
                    }
                }

                if ($key===false)
                {
                    $this->log(sprintf("no TTIK match found for taxon %s",$val["taxon"]),self::DATA_ERROR,"TTIK taxonomy");
                    $match=null;
                }
                else
                {
                    $val["taxonomy"]=
                        [
                            "classification" => $this->ttik[$key]["classification"],
                            "uninomial" => $this->ttik[$key]["uninomial"],
                            "infra_specific_epithet" => $this->ttik[$key]["infra_specific_epithet"],
                            "authorship" => $this->ttik[$key]["authorship"],
                            "taxon" => $this->ttik[$key]["taxon"],
                            "rank" => $this->ttik[$key]["rank"],
                            "english" => $this->ttik[$key]["_english_main"],
                            "dutch" => $this->ttik[$key]["_dutch_main"],
                            "synonyms" => $this->ttik[$key]["synonyms"],
                            "_nomen" => $this->ttik[$key]["_nomen"],
                            "_nomen_ic" => $this->ttik[$key]["_nomen_ic"],
                            "_taxon_ic" => $this->ttik[$key]["_taxon_ic"],
                            "taxon_id" => $this->ttik[$key]["taxon_id"],
                            "ttik_id" => $this->ttik[$key]["id"],
                            "_match"  => array_merge($match,[ "_local_key" => $key ])
                        ];

                    $matched++;
                }

                $d[]=$val;
            }

            $this->taxonList = $d;
            $this->log(sprintf("matched %s masterlist records to a TTIK record",$matched),self::DATA_MESSAGE,"TTIK taxonomy");
        }

        public function saveTaxonList()
        {

            $this->db->query("truncate " . self::TABLE_TAXONLIST);

            $stmt = $this->db->prepare("insert into ".self::TABLE_TAXONLIST." (taxon,taxonomy) values (?,?)");

            foreach($this->taxonList as $val)
            {
                $stmt->bind_param('ss', $val["taxon"], json_encode($val["taxonomy"]));
                $stmt->execute();
            }

            $this->log("saved taxonlist",self::DATA_MESSAGE,"TTIK taxonomy");
        }

        public function addObjectDataToTL()
        {
            $d=[];
            foreach ($this->taxonList as $val)
            {
                $prefixes=[];

                $matches = array_filter($this->masterList,function($a) use ($val)
                {
                    if (isset($val["taxon_original"]))
                    {
                        return $val["taxon_original"]==$a[self::TABLE_MASTER_NAME_COL];
                    }
                    else
                    {
                        return $val["taxon"]==$a[self::TABLE_MASTER_NAME_COL];
                    }
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
                                "is_leenobject"=>$match["_is_leenobject"],
                                "is_brahms"=>$match["_is_brahms"]
                            ];
                        $this->exhibitionRooms_ML[]=$match["Zaal"];
                    }
                }

                $d[]=$val;
            }

            $this->taxonList = $d;
            $this->exhibitionRooms_ML=array_filter(array_unique($this->exhibitionRooms_ML));
        }

        public function addCRSToTL()
        {
            $m=[];
            foreach ($this->taxonList as $key => $val)
            {
                $d=[];
                foreach ((array)$val["object_data"] as $object)
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
                                $val["_crs_match"]=$match["FULLSCIENTIFICNAME"];
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

        public function addBrahmsToTL()
        {
            $m=[];
            foreach ($this->taxonList as $key => $val)
            {
                $d=[];
                foreach ((array)$val["object_data"] as $object)
                {
                    if ($object["is_brahms"])
                    {
                        $matches = array_filter($this->brahms,function($a) use ($object)
                        {
                            return $object["unitid"]==$a["unitid"];
                        });
                    }
                    else
                    {
                        $matches=false;
                    }

                    if (!empty($matches))
                    {
                        $this->log(sprintf("matched object %s to Brahms record",$object["unitid"]),self::DATA_ERROR,"Brahms");

                        foreach ($matches as $match)
                        {
                            if (!empty($match["URL"]))
                            {
                                if (!filter_var($match["URL"], FILTER_VALIDATE_URL))
                                {
                                    $this->log(sprintf("invalid image URL for Brahms record %s: %s",$object["id"],$match["URL"]),self::DATA_ERROR,"Brahms");
                                }
                                else
                                {
                                    $object["images"][]=
                                        [
                                            "url"=>$match["URL"]
                                        ];
                                }
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
                $IUCN = array_filter($this->IUCN,
                function($a) use ($val)
                {
                    return 
                        $a["scientific_name"]==$val["taxon"] ||
                        (isset($val["taxonomy"]) && isset($val["taxonomy"]["_nomen"]) && $a["scientific_name"]==$val["taxonomy"]["_nomen"]);
                });

                if (empty($IUCN))
                {
                    $this->log(sprintf("no IUCN match found for taxon %s",$val["taxon"]),self::DATA_ERROR,"IUCN");
                }
                else
                {   

                    //  . ($IUCN[0]["region"]!="Global" ? sprintf(" (%s)",$IUCN[0]["region"]) : "" )
                    
                    $val["IUCN"] =
                        [
                            "category" => $IUCN[0]["category"],
                            "population_trend" => $IUCN[0]["population_trend"],
                            "_category_label" => $this->IUCN_statusTranslations[$IUCN[0]["category"]],
                            "_trend_label" => $this->IUCN_trendTranslations[$IUCN[0]["population_trend"]]
                        ];
                }
                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function resolveExhibitionRooms()
        {
            foreach ((array)$this->exhibitionRooms_ML as $val)
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

            foreach ([
                "unknown room in masterlist" => $this->unknownRooms["unknown_room_in_masterlist"],
                "masterlist room not in natuurwijzer" => $this->unknownRooms["masterlist_room_not_in_natuurwijzer"],
                "natuurwijzer room not in masterlist" => $this->unknownRooms["natuurwijzer_room_not_in_masterlist"],
            ] as $key => $value)
            {
                if (count((array)$value)>0)
                {
                    $this->log(sprintf("$key: %s",implode("; ",$value)),self::DATA_MESSAGE,"rooms");
                }
            }
        }

        public function addTTIKTextsToTL()
        {
            $d=[];
            foreach ($this->taxonList as $val)
            {
                /*
                    taxonomies are matched on taxon, nomen, synonym and classification
                    if no match was found there's no point trying again for the content
                */
                if (!isset($val["taxonomy"]))
                {
                    $this->log(sprintf("no TTIK content for %s",$val["taxon"]),self::DATA_ERROR,"TTIK content");
                    $d[]=$val;
                    continue;
                }

                if (isset($val["taxonomy"]["_match"]) && isset($val["taxonomy"]["_match"]["_local_key"]))
                {
                    $key = $val["taxonomy"]["_match"]["_local_key"];
                }

                if (!empty($this->ttik[$key]["description"]))
                {
                    $val["texts"]["ttik"]=$this->ttik[$key]["description"];
                }
                else
                {
                    foreach(array_reverse($val["taxonomy"]["classification"]) as $cKey => $cVal)
                    {
                        if ($cKey==0)
                        {
                            continue;
                        }
                        $key = array_search(strtolower($cVal["taxon"]), array_column($this->ttik, "_nomen_ic"));

                        if (!empty($this->ttik[$key]["description"]))
                        {
                            $val["texts"]["ttik"]=$this->ttik[$key]["description"];
                        }
                    }
                }

                if (!isset($val["texts"]["ttik"]) || empty($val["texts"]["ttik"]))
                {
                    $this->log(sprintf("no TTIK content for %s",$val["taxon"]),self::DATA_ERROR,"TTIK content");
                }

                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function addNatuurwijzerTextsToTL()
        {
            /*
                $matched_on_taxon
                    articles that have a tag that matches the taxon's name (or nomen).

                $matched_on_classification
                    articles that have a tag that matches an element in the taxon's classification.
                    the array's keys will also function as ordering mechanism, so that lower keys,
                    which match lower taxa, will be ranked higher, as they cover more specific
                    subjects.
                    
                $matched_on_object
                    articles that have a tag that matches the exhibition room of one of the taxon's 
                    objects.

            */

            $d=[];

            foreach ($this->taxonList as $val)
            {
                $used_ids=[];

                $matched_on_taxon=[];
                $matched_on_synonym=[];
                $matched_on_classification=[];
                $matched_on_object=[];

                // matching on taxon
                $needle_nomen = $val["taxon"];
                $needle_taxon = $val["taxonomy"]["taxon"];

                $matched_on_taxon = array_filter(
                    $this->natuurwijzer,
                    function($a) use ($needle_nomen,$needle_taxon)
                    {
                        if (!isset($a["_taxon"]))
                        {
                            return false;
                        }

                        if (array_search($needle_nomen, $a["_taxon"])!==false)
                        {
                            return true;
                        }

                        return array_search($needle_taxon, $a["_taxon"])!==false;
                    }
                );

                $used_ids = array_values(array_map(function($a) { return $a["id"];} , $matched_on_taxon));


                if (!empty($val["taxonomy"]["synonyms"]))
                {
                    foreach ($val["taxonomy"]["synonyms"] as $hKey=>$needle_synonym)
                    {
                        $matched_on_synonym = array_filter(
                            $this->natuurwijzer,
                            function($a) use ($needle_synonym,&$used_ids)
                            {
                                if (!isset($a["_taxon"]))
                                {
                                    return false;
                                }

                                $key = array_search($needle_synonym, $a["_taxon"]);

                                if ($key===false)
                                {
                                    return false;
                                }

                                if (in_array($a["id"], $used_ids))
                                {
                                    return false;
                                }

                                $used_ids[]=$a["id"];
                                return true;
                            }
                        );
                    }
                }

                // matching on higher classification
                if ((count($matched_on_taxon)+count($matched_on_synonym))<$this->softMaxTaxonArticles && isset($val["taxonomy"]["classification"]))
                {
                    foreach (array_slice(array_reverse($val["taxonomy"]["classification"]), 1, null) as $hKey=>$hVal)
                    {
                        $needle_nomen = $hVal["taxon"];

                        $matched_on_classification[$hKey] = array_filter(
                            $this->natuurwijzer,
                            function($a) use ($needle_nomen,&$used_ids)
                            {
                                if (!isset($a["_taxon"]))
                                {
                                    return false;
                                }

                                $key = array_search($needle_nomen, $a["_taxon"]);

                                if ($key===false)
                                {
                                    return false;
                                }

                                if (in_array($a["id"], $used_ids))
                                {
                                    return false;
                                }

                                $used_ids[]=$a["id"];
                                return true;
                            }
                        );
                    }
                }
                
                // matching on exhibition rooms
                if ($val["object_data"])
                {
                    $rooms=array_unique(array_values(array_column($val["object_data"],"exhibition_room")));

                    foreach ($rooms as $room)
                    {
                        if (isset($this->exhibitionRoomsTranslations[$room]))
                        {
                            $needle = $this->exhibitionRoomsTranslations[$room];

                            $matched_on_object = array_merge(
                                $matched_on_object,
                                array_filter(
                                    $this->natuurwijzer,
                                    function($a) use ($needle,&$used_ids)
                                    {
                                        if (!isset($a["_exhibition_rooms"]))
                                        {
                                            return false;
                                        }

                                        $key = array_search($needle, $a["_exhibition_rooms"])!=false;
                                        
                                        if ($key===false)
                                        {
                                            return false;
                                        }
                                        if (in_array($a["id"], $used_ids))
                                        {
                                            return false;
                                        }

                                        $used_ids[]=$a["id"];
                                        return true;
                                    }
                                )
                            );
                        }
                    }
                }

                // print_r($matched_on_taxon);
                // print_r($matched_on_synonym);
                // print_r($matched_on_classification);
                // print_r($matched_on_object);

                foreach ($matched_on_taxon as $match)
                {
                    $this->overallTextOccurrences[$match["id"]] = 
                        isset($this->overallTextOccurrences[$match["id"]]) ?
                            $this->overallTextOccurrences[$match["id"]]+1 : 1;

                    $val["natuurwijzer_texts_matches"][]=
                        [
                            "sort" => "1", 
                            "id" => $match["id"],
                            "source" => "taxon" 
                        ];
                    $val["texts"]["natuurwijzer"][$match["id"]]=$match;
                }

                foreach ($matched_on_synonym as $match)
                {
                    $this->overallTextOccurrences[$match["id"]] = 
                        isset($this->overallTextOccurrences[$match["id"]]) ?
                            $this->overallTextOccurrences[$match["id"]]+1 : 1;

                    $val["natuurwijzer_texts_matches"][]=
                        [
                            "sort" => "1", 
                            "id" => $match["id"],
                            "source" => "synonym" 
                        ];
                    $val["texts"]["natuurwijzer"][$match["id"]]=$match;
                }

                foreach ($matched_on_classification as $classKey => $match)
                {
                    foreach ($match as $classMatch)
                    {
                        $this->overallTextOccurrences[$classMatch["id"]] = 
                            isset($this->overallTextOccurrences[$classMatch["id"]]) ?
                                $this->overallTextOccurrences[$classMatch["id"]]+1 : 1;

                        $val["natuurwijzer_texts_matches"][]=
                            [
                                "sort" => "10.".$classKey,
                                "id" => $classMatch["id"],
                                "source" => "classification"
                            ];

                        $val["texts"]["natuurwijzer"][$classMatch["id"]]=$classMatch;
                    }
                }

                foreach ($matched_on_object as $match)
                {
                    $this->overallTextOccurrences[$match["id"]] = 
                        isset($this->overallTextOccurrences[$match["id"]]) ?
                            $this->overallTextOccurrences[$match["id"]]+1 : 1;

                    $val["natuurwijzer_texts_matches"][]=
                        [
                            "sort" => "100",
                            "id" => $match["id"],
                            "source" => "room" 
                        ];
                    $val["texts"]["natuurwijzer"][$match["id"]]=$match;
                }

                $d[]=$val;
            }

            // arsort($this->overallTextOccurrences);

            foreach($d as $key => $val)
            {
                if ($val["natuurwijzer_texts_matches"])
                {
                    foreach ($val["natuurwijzer_texts_matches"] as $sKey=>$sVal)
                    {
                        $d[$key]["natuurwijzer_texts_matches"][$sKey]["sort"] .= "." . $this->overallTextOccurrences[$sVal["id"]];
                    }
                }
            }

            $this->taxonList = $d;

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
                            $val["topstukken"][]=$this->topstukken[$key];
                            $this->log(sprintf("added topstukken content to %s",$val["taxon"]),self::DATA_MESSAGE,"topstukken");
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
                            if (!isset($object["images"]))
                            {
                                $this->log(sprintf("taxon %s, unitid %s is present in image_selection but object has no images (!?)",$val["taxon"],$object["unitid"]),self::DATA_ERROR,"IMAGES");
                            }
                            else
                            {
                                $val["object_data"][$key]["images"]=
                                    array_intersect($this->imageSelection[$object["unitid"]],$object["images"]);
                            }
                        }
                    }    
                }

                $d[]=$val;
            }
            $this->taxonList = $d;
        }

        public function addLeenobjectImages()
        {
            $d=[];

            foreach ($this->taxonList as $val)
            {
                if ($val["object_data"])
                {
                    foreach ($val["object_data"] as $key => $object)
                    {
                        if ($object["is_leenobject"])
                        {
                            $key = array_search($object["unitid"], array_column($this->leenobjecten, "registratienummer"));

                            if ($key!==false)
                            {
                                $val["object_data"][$key]["images"]=
                                    $this->leenobjecten[$key]["_afbeeldingen"];

                                // $val["object_data"][$key]["images"]=
                                //     array_intersect($this->imageSelection[$object["unitid"]],$object["images"]);

                            }
                        }
                    }    
                }

                $d[]=$val;
            }

            $this->taxonList = $d;
        }

        public function addImageSquares()
        {
            $d=[];

            foreach ($this->taxonList as $val)
            {

                if (isset($val["taxonomy"]))
                {
                    $needle_taxon = $val["taxonomy"]["_taxon_ic"];
                    $needle_nomen = $val["taxonomy"]["_nomen_ic"];
                }
                else
                {
                    $needle_taxon = strtolower($val["taxon"]);
                }


                $key = array_search($needle_taxon, array_column($this->imageSquares, "_scientific_name_ic"));

                if ($key===false && isset($needle_nomen))
                {
                    $key = array_search($needle_nomen, array_column($this->imageSquares, "_scientific_name_ic"));
                }

                if ($key)
                {
                    $val["image_square"] = $this->imageSquares[$key];
                }
    
                $d[]=$val;

            }

            $this->taxonList = $d;

        }

        public function makeLinksSelection()
        {
            foreach ($this->taxonList as $key => $val)
            {
                if (!isset($val["natuurwijzer_texts_matches"]))
                {
                    continue;
                }

                usort($val["natuurwijzer_texts_matches"],function($a,$b)
                {
                    if ($a["sort"]==$b["sort"])
                    {
                        return $a["id"]>$b["id"];
                    }
                    else
                    {
                        return $a["sort"]>$b["sort"];
                    }
                });

                $linked_articles=[];
                $taxon_articles=0;

                foreach ($val["natuurwijzer_texts_matches"] as $match)
                {
                    if (count($linked_articles) >= $this->hardMaxTotalArticles)
                    {
                        break;
                    }

                    if ($taxon_articles >= $this->softMaxTaxonArticles)
                    {
                        break;
                    }

                    if ($match["source"]=="taxon" || $match["source"]=="synonym")
                    {
                        $linked_articles[] = 
                            $val["texts"]["natuurwijzer"][$match["id"]] +
                            [ "_link_origin"  => $match["source"] ];
                        $taxon_articles++;
                    }
                }

                if ($taxon_articles < $this->softMaxTaxonArticles)
                {
                    foreach ($val["natuurwijzer_texts_matches"] as $match)
                    {
                        if (count($linked_articles) >= $this->hardMaxTotalArticles)
                        {
                            break;
                        }

                        if ($taxon_articles >= $this->softMaxTaxonArticles)
                        {
                            break;
                        }

                        if ($match["source"]=="classification")
                        {
                            $linked_articles[] = 
                                $val["texts"]["natuurwijzer"][$match["id"]] +
                                [ "_link_origin"  => $match["source"] ];
                            $taxon_articles++;
                        }
                    }
                }


                foreach ($val["natuurwijzer_texts_matches"] as $match)
                {
                    if (count($linked_articles) >= $this->hardMaxTotalArticles)
                    {
                        break;
                    }

                    if ($match["source"]=="room")
                    {
                        $linked_articles[] = 
                            $val["texts"]["natuurwijzer"][$match["id"]] +
                            [ "_link_origin"  => $match["source"] ];
                    }
                }

                unset($val["natuurwijzer_texts_matches"]);
                $this->taxonList[$key]["texts"]["natuurwijzer"] = $linked_articles;
            }
        }

        public function generateJsonDocuments()
        {
            $this->dateStamp = date("c");
            $this->deleteAllPreviousJsonFiles( "preview" );

            foreach ($this->taxonList as $val)
            {
                if (!isset($val["taxon"]) || empty($val["taxon"]))
                {
                    $this->log("skipping taxonList-item without taxon-value ",self::DATA_ERROR,"generator");
                    continue;
                }

                $this->rawDocData = $val;

                $this->_addDocumentMetaData();
                $this->_addDocumentHeaderImage();
                $this->_addDocumentTitles();
                $this->_addDocumentDefinitionsBlock();
                $this->_addDocumentIUCN();
                $this->_addDocumentContent();
                $this->_addDocumentObjects();
                $this->_addDocumentLinks();
                $this->_addDocumentDistributionMap();

                if (!$this->_checkMinimumRequirements())
                {
                    continue;
                }

                $filename = $this->_generateUniqueFilename( $this->jsonPath["preview"], $this->rawDocData["taxon"] );

                if (file_put_contents($filename, json_encode($this->document)))
                {
                    $this->log(sprintf("wrote %s",$filename),self::DATA_MESSAGE,"generator");
                }
                else
                {
                    $this->log(sprintf("could not write %s",$filename),self::DATA_ERROR,"generator");
                }

                $this->document=[];
            }                
        }

        private function _addDocumentMetaData()
        {
            $this->document["id"] = $this->documentId++;
            $this->document["created"] = $this->dateStamp;
            $this->document["last_modified"] = $this->dateStamp;
            $this->document["language"] = $this->languageDefault;
            $this->document["_key"] = str_replace(" ", "_", strtolower($this->rawDocData["taxon"]));
        }

        private function _addDocumentHeaderImage()
        {
            $block_name="header_image";

            $val["image_square"] = $this->imageSquares[$key];

            if (isset($this->rawDocData["image_square"]))
            {
                $this->document[$block_name] = [ "url" => $this->squaredImageURLRoot . $this->rawDocData["image_square"]["filename"] ];
            }
            else
            {
                $this->document[$block_name] = [ "url" => $this->squaredImagePlaceholderURL ];
            }
        }

        private function _correctUcFirst( $name, $language=null )
        {
            $language = $language ?? $this->document["language"];

            if ($language!="nl")
            {
                return ucfirst($name);
            }
            else
            {
                foreach (["ij"] as $digraph)
                {
                    if (substr(strtolower($name), 0, strlen($digraph))==$digraph)
                    { 
                       return strtoupper($digraph) . substr($name, strlen($digraph));
                    }
                }

                return ucfirst($name);
            }
        }

        private function _addDocumentTitles()
        {
            $block_name="titles";

            try {
                $sciName = 
                    isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["nomen"]) ? 
                        $this->rawDocData["taxonomy"]["nomen"] : 
                        $this->rawDocData["taxon"];

                $dutchName  = $this->rawDocData["taxonomy"]["dutch"] ?? null;;

                $this->document[$block_name]["page"] = $dutchName ?? $sciName;
                $this->document[$block_name]["main"] = $dutchName ?? $sciName;
                $this->document[$block_name]["sub"] = is_null($dutchName) ? "" : $sciName;

                foreach (["page","main","sub"] as $type)
                {
                    $this->document[$block_name][$type] = $this->_correctUcFirst($this->document[$block_name][$type]);
                }
            } 
            catch (Exception $e)
            {
                // gets caught in _checkMinimumRequirements
            }
        }

        private function _addDocumentDefinitionsBlock()
        {
            $block_name="definitions";

            $this->document[$block_name]=[];

            $this->document[$block_name]["items"][]=
                [ "label" => "Wetenschappelijke naam",
                  "text" =>
                    isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["nomen"]) ? 
                        $this->rawDocData["taxonomy"]["nomen"] : 
                        $this->rawDocData["taxon"]
                ];

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["dutch"]))
            {
                $this->document[$block_name]["items"][]=
                    [ "label" => "Nederlandse naam",
                      "text" => $this->rawDocData["taxonomy"]["dutch"]
                    ];
            }

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["english"]))
            {
                $this->document[$block_name]["items"][]=
                    [ "label" => "Engelse naam",
                      "text" => $this->rawDocData["taxonomy"]["english"]
                    ];
            }

            if (isset($this->rawDocData["texts"]) && isset($this->rawDocData["texts"]["ttik"]))
            {
                foreach ($this->rawDocData["texts"]["ttik"] as $key => $val)
                {
                    if ($val["title"]=="Leefgebied" || $val["title"]=="Leefperiode")
                    {
                        $this->document[$block_name]["items"][]=
                            [ "label" => ($val["title"]=="Leefgebied" ? "Waar" : "Wanneer"),
                              "text" => $val["body"]
                            ];
                    }
                }
            }

            uasort($this->document[$block_name]["items"],function($a,$b)
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
                    $potential_topstuk_image=null;

                    $o=[];
                    $o["id"]=$object["unitid"];
                    $o["title"]=$object["unitid"];

                    if (isset($object["images"]))
                    {
                        foreach ($object["images"] as $image)
                        {
                            $o["images"][]=$image;

                            if (is_null($potential_topstuk_image))
                            {
                                $potential_topstuk_image=$image;
                            }
                        }
                    }

                    $room = $this->exhibitionRoomsTranslations[$object["exhibition_room"]];
                    $room =  $this->exhibitionRoomsPublic[$room] ?? $room;

                    $o["location"]=$room;

                    $o["data"] = [
                        [ "label" => "Registratienummer", "text" => $object["unitid"] ],
                        [ "label" => "Locatie", "text" => $room ]
                    ];

                    $key = array_search($object["unitid"], array_column($this->NBA, 'unitid'));

                    if ($key!==false)
                    {
                        $o["data"] = array_merge(
                            $o["data"],
                            $this->_distillNBAData($this->NBA[$key]["document"])
                        );
                    }

                    $key = array_search($object["unitid"], array_column($this->leenobjecten, 'registratienummer'));

                    if ($key!==false)
                    {
                        $o["data"] = array_merge(
                            $o["data"],
                            [[
                                "label" => "Dit object is een bruikleen van",
                                "text" =>  $this->leenobjecten[$key]["geleend_van"]
                            ]]
                        );
                    }

                    $o["data"] = array_values(array_filter($o["data"], function($a) { return !empty($a["text"]) && $a["text"]!="Not applicable"; }));

                    if (isset($this->rawDocData["topstukken"]))
                    {

                        $key = array_search($object["unitid"], array_column($this->rawDocData["topstukken"], "registrationNumber"));

                        if($key!==false)
                        {
                            if (!is_null($potential_topstuk_image))
                            {
                                $topstuk = $this->rawDocData["topstukken"][$key];

                                $o["topstuk_link"] = [
                                    "url" => $topstuk["_full_url"],
                                    "text" => $topstuk["title"]
                                ];

                                $o["topstuk_link"]["image"] = $potential_topstuk_image["url"];
                            }
                            else
                            {
                                $this->log(
                                    sprintf("no image for topstukken object-link: %s / %s",
                                        $this->document["titles"]["main"],$object["unitid"]),self::DATA_ERROR,"generator");
                            }
                        }
                    }

                    $this->document[$block_name][]=$o;
                }
            }

            usort($this->document[$block_name], function($a,$b)
            {
                $aa = $a["topstuk_link"] ?? null;
                $bb = $b["topstuk_link"] ?? null;

                if ((is_null($aa) && is_null($bb)) || (!is_null($aa) && !is_null($bb)))
                {
                    return strtolower($a["id"]) > strtolower($b["id"]);
                }

                return is_null($aa) ? 1 : -1;
            });
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
                    $imageUrl = (
                        isset($val["_image_urls"]["header_tablet"]) ?
                            $val["_image_urls"]["header_tablet"] : (
                                isset($val["_image_urls"]["header_mobiel"]) ?
                                    $val["_image_urls"]["header_mobiel"] : $val["_image_urls"]["original"]
                            )
                    );

                    $links[] = [
                        "title" => $val["title"],
                        "description" => $val["intro_text"],
                        "url_image" => trim($imageUrl),
                        "url_link" => trim($val["_full_url"]),
                        "_origin" => $val["_link_origin"],
                        // "_id" => $val["id"],
                    ];
                }

                $this->document[$block_name]=$links;
            }
        }

        private function _addDocumentIUCN()
        {
            $block_name="iucn_status";

            unset($this->document[$block_name]);

            if (isset($this->rawDocData["IUCN"]))
            {
                $this->document[$block_name]= [
                    "category" => $this->rawDocData["IUCN"]["category"],
                    "label" => $this->rawDocData["IUCN"]["_category_label"],
                    "url_link" => "https://nl.wikipedia.org/wiki/Rode_Lijst_van_de_IUCN",
                    "url_label" => "Lees meer over de beschermingstatus",
                ];
            }
        }

        private function _addDocumentDistributionMap()
        {
            return;

            $block_name="distribution_map";

            unset($this->document[$block_name]);

            // if (isset($this->rawDocData["distribution_map"]))
            {
                $this->document[$block_name]= [
                    "image_url" => "someurl",
                    "label" => $this->rawDocData["IUCN"]["_category_label"]
                ];
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

        private function _checkImageURLs()
        {
            foreach ([
                "squaredImagePlaceholderURL" => $this->squaredImagePlaceholderURL,
                "squaredImageURLRoot" => $this->squaredImageURLRoot,
                "leenobjectImageURLRoot" => $this->leenobjectImageURLRoot
            ] as $key => $url)
            {
                if (is_null($url))
                {
                    throw new Exception(sprintf("URL not set: %s",$key), 1);
                }
                else
                if (!filter_var($url, FILTER_VALIDATE_URL))
                {
                    throw new Exception(sprintf("invalid URL: %s (%s)",$url,$key), 1);
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

            return $path . $filename_base . "." . $ext;

            // $filename = $filename_base . "_" . $i;

            // while(file_exists($path . $filename . "." . $ext))
            // {
            //     $filename = $filename_base . "_" . $i++;
            // }
            // return $path . $filename . "." . $ext;                
        }

        private function _distillNBAData( $document )
        {
            $d=[];

            $event = $document["gatheringEvent"];

            $d[] = [
                "label" => "Vindplaats",
                "text" =>  
                    isset($event["localityText"]) ?
                        $event["localityText"] : 
                        trim(implode(", ",array_filter(
                            array_reverse(
                                [
                                    @$event["worldRegion"],
                                    @$event["continent"],
                                    @$event["country"],
                                    @$event["provinceState"],
                                    @$event["island"],
                                    @$event["city"],
                                    @$event["locality"]
                                 ]   
                            ),
                            function($a) { return !empty($a); })
                        ))
                ];

            if (isset($event["gatheringPersons"]))
            {
                $p=[];
                foreach ($event["gatheringPersons"] as $val)
                {
                    $p[]=$val["fullName"];
                }

                $d[] = [
                    "label" => "Verzamelaar(s)",
                    "text" =>  implode("; ", $p)
                ];
            }

            if (isset($event["dateTimeBegin"]))
            {
                $d[] = [
                    "label" => "Verzameld",
                    "text" =>  (isset($event["dateTimeBegin"]) ? 
                        date("d-m-Y", strtotime($event["dateTimeBegin"])) :
                        (isset($event["dateText"]) ? $event["dateText"] : "" ))
                ];
            }

            $d[] = [
                "label" => "Expeditie",
                "text" => $event["projectTitle"]
            ];

            $d[] = [
                "label" => "Verzamelmethode",
                "text" => $event["method"]
            ];

            $d[] = [
                "label" => "Verzameld op hoogte",
                "text" => $event["altitude"]
            ];

            $d[] = [
                "label" => "Verzameld op diepte",
                "text" => $event["depth"]
            ];    
    

            $d[] = [
                "label" => "Verzameld in biotoop",
                "text" => @$event["biotopeText"]
            ];

            $d[] = [
                "label" => "Onderdeel/materiaaltype",
                "text" =>  trim(implode(", ",array_filter([@$event["recordBasis"],@$event["kindOfUnit"],@$event["preparationType"]],function($a) { return !empty($a); })))
            ];

            $d[] = [
                "label" => "Sexe",
                "text" =>  @$document["sex"]
            ];

            $d[] = [
                "label" => "Collectienaam",
                "text" =>  @$document["collectionType"]
            ];

            $d[] = [
                "label" => "Levensfase",
                "text" =>  @$document["phaseOrStage"]
            ];

            $d[] = [
                "label" => "Lithostratigrafische formatie",
                "text" =>  @$event["lithoStratigraphy"]["formation"]
            ];

            if (isset($document["identifications"]))
            {
                $typeStatus=null;
                $rockType=null;
                $associatedMineralName=null;

                foreach ($document["identifications"] as $identification)
                {
                    if (isset($identification["typeStatus"]))
                    {
                        $typeStatus=$identification["typeStatus"];
                        break;
                    }
                    if (isset($identification["rockType"]))
                    {
                        $rockType=$identification["rockType"];
                        break;
                    }
                    if (isset($identification["associatedMineralName"]))
                    {
                        $associatedMineralName=$identification["associatedMineralName"];
                        break;
                    }
                }

                $d[] = [
                    "label" => "Typestatus",
                    "text" =>  $typeStatus
                ];

                $d[] = [
                    "label" => "Steentype",
                    "text" =>  $rockType
                ];

                $d[] = [
                    "label" => "Geassocieerd mineraal",
                    "text" =>  $associatedMineralName
                ];
            }

            uasort($d,function($a,$b)
            {
                $order=[
                    "Vindplaats"=>0,
                    "Verzamelaar(s)"=>1,
                    "Verzameld"=>2,
                    "Sexe"=>3,
                    "Levensfase"=>4,
                    "Steentype"=>5,
                    "Geassocieerd mineraal"=>6,
                    "Onderdeel/materiaaltype"=>7,
                    "Typestatus"=>8,
                    "Collectienaam"=>9,
                ];

                $a = $order[$a["label"]] ?? 99;
                $b = $order[$b["label"]] ?? 99;
                return (($a == $b) ? 0 : (($a < $b) ? -1 : 1));
            });

            return $d;
        }

        private function _checkMinimumRequirements()
        {
            try {
                if (!isset($this->document["titles"]["main"])) throw new Exception("no main name", 1); //  = $dutchName ?? $sciName;
                if (!isset($this->document["objects"]) || count($this->document["objects"])<1) throw new Exception("no objects", 1);
            }
            catch (Exception $e)
            {
                $this->log(sprintf("skipping %s: %s",$this->rawDocData["taxon"],$e->getMessage()),self::DATA_MESSAGE,"generator");
                return false;
            }

            return true;
        }
    }