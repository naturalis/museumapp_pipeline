<?php

    class PipelineData extends BaseClass
    {
        public $language;
        public $languageName;

        private $masterList;
        private $masterListObjectIndex=[];
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
        private $favourites;
        private $objectlessTaxa;
        private $ttikSpeciesPhotos;
        private $maps;
        private $exhibitionRooms_NW;
        private $exhibitionRooms_ML;
        private $overallTextOccurrences=[];
        private $overallTextUsage=[];
        private $taxonList;
        private $brahmsUnitIDs;
        private $dateStamp;
        private $rawDocData=[];
        private $document=[];
        private $documentId=1;
        private $useVerifiedTranslationsOnly=false;
        private $autoTranslationTexts=[];

        private $maxTaxonArticles=5;
        private $maxTaxonArticlesSpecialCases=8;
        private $taxonArticlesSpecialCases = [ "Tyrannosaurus rex" ];
        private $maxClassificationArticles=5;
        private $maxTotalArticles=5;
        private $maxNbaFields=[ "taxon" => null, "unitid" => null, "fields" => 0 ];
        private $availableLanguages = [ "nl" => "dutch", "en" => "english" ];
        private $debug_masterListSCnames = [];

        private $squaredImagePlaceholderURL;
        private $objectImagePlaceholderURL;
        private $squaredImageURLRoot;
        private $leenobjectImageURLRoot;

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
            "NE" => "Niet geëvalueerd",
            "NA" => "Niet van toepassing",
        ];

        private $IUCN_trendTranslations = [
            "Unknown" => "Onbekend",
            "Stable" => "Stabiel",
            "Decreasing" => "Afnemend",
            "Increasing" => "Toenemend"
        ];

        private $nba_valueTranslations = [       
            "HumanObservation" => "Human observation",
            "PreservedSpecimen" => "Preserved specimen",
            "FossilSpecimen" => "Fossil specimen",
            "OtherSpecimen" => "Other specimen",
            "DrawingOrPhotograph" => "Drawing or photograph",
            "WholeOrganism" => "Whole organism",
            "AnimalPart" => "Animal part",
            "PaleontologicalPart" => "Paleontological part",
            "dry&wet specimen" => "dry & wet specimen",
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
            "Schatkamer 1.0" => "De aarde",
            "Schatkamer 2.0" => "De aarde",
        ];

        private $roomsToMatchLinksOn = [
            "Dinotijd", "De ijstijd", "De aarde", "De vroege mens"
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

        private $iucnURLs = [
            "general_link" => "https://nl.wikipedia.org/wiki/Rode_Lijst_van_de_IUCN",
            "general_link_label" => "Lees meer over de beschermingsstatus",
        ];

        private $ttikCategoryNames = [
            "where" => "Leefgebied",
            "when" => "Leefperiode",
            "description" => "Beschrijving"
        ];

        const TABLE_MASTER = 'tentoonstelling';
        const TABLE_CRS = 'crs';
        const TABLE_BRAHMS = 'brahms';
        const TABLE_IUCN = 'iucn';
        const TABLE_NATUURWIJZER = 'natuurwijzer';
        const TABLE_TOPSTUKKEN = 'topstukken';
        const TABLE_TTIK = 'ttik';
        const TABLE_TTIK_TEXTS = 'ttik_translations';
        const TABLE_NBA = 'nba';
        const TABLE_LEENOBJECTEN = 'leenobjecten';
        const TABLE_FAVOURITES = 'favourites';
        const TABLE_TAXONLIST = 'taxonlist';
        const TABLE_OBJECTLESS_TAXA = 'taxa_no_objects';
        const TABLE_SELECTED_IMAGES = 'selected_urls';
        const TABLE_SQUARED_IMAGES = 'squared_images_new';
        const TABLE_MAPS = 'maps';
        const TABLE_EVENTS = 'events';
        const TABLE_TTIK_PHOTO_SPECIES = 'ttik_photo_species';

        const TABLE_MASTER_NAME_COL = 'SCname controle';
        const PREFIX_LEENOBJECTEN = 'leen.';
        const POSTFIX_SPECIES_PLURALIS = 'sp.';

        public function init()
        {
            $this->checkJsonPaths();
            $this->_checkImageURLs();
            $this->connectDatabase();
        }

        public function setLanguage( $languageCode )
        {
            if (isset($this->availableLanguages[$languageCode]))
            {
                $this->language = $languageCode;
                $this->languageName = $this->availableLanguages[$languageCode];
            }
        }

        public function setSquaredImagePlaceholderURL( $url )
        {
            $this->squaredImagePlaceholderURL = $url;
        }

        public function setObjectImagePlaceholderURL( $url )
        {
            $this->objectImagePlaceholderURL = $url;
        }

        public function setSquaredImageURLRoot( $url )
        {
            $this->squaredImageURLRoot = $url;
        }

        public function setLeenobjectImageURLRoot( $url )
        {
            $this->leenobjectImageURLRoot = $url;
        }

        public function setUseVerifiedTranslationsOnly( $state )
        {
            if (is_bool($state))
            {
                $this->useVerifiedTranslationsOnly = $state;
            }
        }

        public function setAutoTranslationText( $language, $txt )
        {
            if (!empty($txt))
            {
                $this->autoTranslationTexts[$language] = $txt;
            }
        }

        public function setTestTaxa( $testTaxa )
        {
            if (!is_null($testTaxa) && is_array($testTaxa))
            {
                $this->debug_masterListSCnames = $testTaxa;
            }
        }

        public function setMasterList()
        {
            $this->masterList = $this->getMySQLSource(self::TABLE_MASTER);

            $this->masterList =
                array_map(function($a)
                {
                    // $a["_is_leenobject"] =
                    //     substr($a["Registratienummer"],0,strlen(self::PREFIX_LEENOBJECTEN))==self::PREFIX_LEENOBJECTEN;

                    if (strpos($a["Registratienummer"], "  ")>0)
                    {
                        $prefix = @explode("  ",$a["Registratienummer"])[0];
                    }
                    else
                    {
                        $prefix = @explode(".",$a["Registratienummer"])[0];
                    }
                   
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

            foreach ($this->masterList as $key => $val)
            {
                $this->masterListObjectIndex[$val[self::TABLE_MASTER_NAME_COL]][]=$key;
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
            $this->IUCN = $this->getMySQLSource(self::TABLE_IUCN,"inserted desc");
            $d=[];

            // TODO: taking out double-entries: this should be fixed in the reaper
            foreach ($this->IUCN as $key => $val)
            {
                $d[$val["scientific_name"]."_".$val["region"]]=$val;
            }
            $this->IUCN = array_values($d);
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
                // TODO: taking out double-entries: this should be fixed in the reaper
                $d[$val["unitid"]]=[
                    "unitid" => $val["unitid"],
                    "collection"  => $val["collection"],
                    "document"  => json_decode($val["document"],true),
                    "inserted"  => $val["inserted"]
                ];
            }

            $this->NBA = array_values($d);

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
            $ttik_texts = $this->getMySQLSource(self::TABLE_TTIK_TEXTS);

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

                // $val["description"]=json_decode($val["description"],true);

                $descriptions = array_filter($ttik_texts,function($a) use ($val)
                {
                    return (
                        ($val["taxon_id"]==$a["taxon_id"]) && 
                        (
                            ($this->useVerifiedTranslationsOnly && $a["verified"]=="1") || 
                            !$this->useVerifiedTranslationsOnly
                        )
                    );
                });

                $val["descriptions"] = array_map(function($a)
                {
                    unset($a["id"], $a["taxon_id"], $a["inserted"]);
                    $a["description"]=json_decode($a["description"],true);
                    return $a;
                },$descriptions);

                if (preg_match('/(\s){1,}(spec\.|sp\.)$/', $val["taxon"]))
                {
                    $val["_taxon_original"] = $val["taxon"];
                    $val["taxon"] = preg_replace('/(\s){1,}(spec\.|sp\.)$/', ' '. self::POSTFIX_SPECIES_PLURALIS, $val["taxon"]);
                }

                $val["_nomen"] = trim( $val["uninomial"] . " " . $val["specific_epithet"] . " " . $val["infra_specific_epithet"]);
                $val["_nomen_ic"]=strtolower($val["_nomen"]);
                $val["_taxon_ic"]=strtolower($val["taxon"]);

                $alternativeNames=[];

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

                    $prefName=null;

                    if (isset($val[$language]) && $language!="scientific")
                    {
                        foreach ($val[$language] as $anotherName)
                        {
                            if ($anotherName["nametype"]=="isPreferredNameOf")
                            {
                                $prefName = $anotherName["name"];
                                $prefNameArticle = $anotherName["remark"];
                            }
                            else
                            {
                                $alternativeNames[] = [ "name" => $anotherName["name"], "language" => $language ];
                            }
                        }

                        $val["_".$language."_main"] = $prefName ?? $val[$language][0]["name"];

                        if (isset($prefNameArticle))
                        {
                            $val["_".$language."_article"] = $prefNameArticle;
                        }
                        
                    }
                    else
                    {
                        $val["_".$language."_main"] = null;
                    }    
                }

                usort($alternativeNames, function($a,$b)
                    {
                        if ($a["language"]==$b["language"])
                        {
                            return $a["name"]>$b["name"];
                        }
                        else
                        {
                            return $a["language"]>$b["language"];
                        }
                    });

                $val["alternative_names"]=$alternativeNames;

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
            $sql = $db->prepare('SELECT * FROM ' . self::TABLE_SELECTED_IMAGES);
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
            $sql = $db->prepare('SELECT * FROM ' . self::TABLE_SQUARED_IMAGES);
            $results = $sql->execute();
            while($row = $results->fetchArray())
            {
                $this->imageSquares[]=[
                    "scientific_name" => $row["scientific_name"],
                    "_scientific_name_ic" => strtolower($row["scientific_name"]),
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
                            return [ "url" => $this->leenobjectImageURLRoot . trim($a) ];
                        },array_filter((array)json_decode($a["afbeeldingen"])));
                    return $a;
                },$this->leenobjecten);

            $leenobject_nummers = array_column($this->leenobjecten, "_registratienummer_ic");

            $this->masterList =
                array_map(function($a) use ($leenobject_nummers)
                {
                    $a["_is_leenobject"] = in_array(strtolower($a["Registratienummer"]), $leenobject_nummers);
                    return $a;
                },$this->masterList);

            $this->log(sprintf("found %s leenobjecten entries",count($this->leenobjecten)),self::DATA_MESSAGE,"init");
        }

        public function setFavourites()
        {
            $this->favourites = $this->getMySQLSource(self::TABLE_FAVOURITES);

            $this->favourites =
                array_map(function($a)
                {
                    $a["rank"]=(int)$a["rank"];
                    $a["_taxon_ic"]=strtolower($a["taxon"]);
                    $a["_assigned"]=false;
                    return $a;
                },$this->favourites);

            $this->log(sprintf("found %s favourites",count($this->favourites)),self::DATA_MESSAGE,"init");
        }

        public function setObjectlessTaxa()
        {
            $this->objectlessTaxa = $this->getMySQLSource(self::TABLE_OBJECTLESS_TAXA);
            $this->log(sprintf("found %s taxa without objects",count($this->objectlessTaxa)),self::DATA_MESSAGE,"init");
        }

        public function setMaps()
        {
            $this->maps = $this->getMySQLSource(self::TABLE_MAPS);
            $this->log(sprintf("found %s maps",count($this->maps)),self::DATA_MESSAGE,"init");
        }

        public function setTTIKSpeciesPhoto()
        {
            $this->ttikSpeciesPhotos = $this->getMySQLSource(self::TABLE_TTIK_PHOTO_SPECIES);
            $this->log(sprintf("found %s TTIK species photos",count($this->ttikSpeciesPhotos)),self::DATA_MESSAGE,"init");
        }

        public function getMasterList()
        {
            return [
                "data" => $this->masterList,
                "count" => count((array)$this->masterList),
                "harvest_date" => $this->masterList[0]["inserted"]
            ];
        }

        public function getCRS()
        {
            return [
                "data" => $this->CRS,
                "count" => count((array)$this->CRS),
                "harvest_date" => $this->CRS[0]["inserted"]
            ];
        }

        public function getBrahms()
        {
            return [
                "data" => $this->brahms,
                "count" => count((array)$this->brahms),
                "harvest_date" => $this->brahms[0]["inserted"]
            ];
        }

        public function getLeenObjecten()
        {
            return [
                "data" => $this->leenobjecten,
                "count" => count((array)$this->leenobjecten),
                "harvest_date" => $this->leenobjecten[0]["inserted"]
            ];
        }

        public function getFavourites()
        {
            return [
                "data" => $this->favourites,
                "count" => count((array)$this->favourites),
                "harvest_date" => $this->favourites[0]["inserted"]
            ];
        }

        public function getObjectlessTaxa()
        {
            return [
                "data" => $this->objectlessTaxa,
                "count" => count((array)$this->objectlessTaxa),
                "harvest_date" => $this->objectlessTaxa[0]["inserted"]
            ];
        }

        public function getIUCN()
        {
            return [
                "data" => $this->IUCN,
                "count" => count((array)$this->IUCN),
                "harvest_date" => $this->IUCN[0]["inserted"]
            ];
        }

        public function getNBA()
        {
            return [
                "data" => $this->NBA,
                "count" => count((array)$this->NBA),
                "harvest_date" => $this->NBA[0]["inserted"]
            ];
        }

        public function getNatuurwijzer()
        {
            return [
                "data" => $this->natuurwijzer,
                "count" => count((array)$this->natuurwijzer),
                "harvest_date" => $this->natuurwijzer[0]["inserted"]
            ];
        }

        public function getTopstukken()
        {
            return [
                "data" => $this->topstukken,
                "count" => count((array)$this->topstukken),
                "harvest_date" => $this->topstukken[0]["inserted"]
            ];
        }

        public function getTtik()
        {
            return [
                "data" => $this->ttik,
                "count" => count((array)$this->ttik),
                "harvest_date" => $this->ttik[0]["inserted"]
            ];
        }

        public function getImageSelection()
        {
            return [
                "data" => $this->imageSelection,
                "count" => count((array)$this->imageSelection)
            ];
        }

        public function getImageSquares()
        {
            return [
                "data" => $this->imageSquares,
                "count" => count((array)$this->imageSquares)
            ];
        }

        public function getMaps()
        {
            return [
                "data" => $this->maps,
                "count" => count((array)$this->maps)
            ];
        }

        public function getTtikSpeciesPhotos()
        {
            return [
                "data" => $this->ttikSpeciesPhotos,
                "count" => count((array)$this->ttikSpeciesPhotos)
            ];
        }

        public function getTaxonList()
        {
            return [
                "data" => $this->taxonList,
                "count" => count((array)$this->taxonList)
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
                    $this->taxonList[$val[self::TABLE_MASTER_NAME_COL]]["taxon_original"] = $val[self::TABLE_MASTER_NAME_COL];
                    $this->taxonList[$val[self::TABLE_MASTER_NAME_COL]]["taxon"] = 
                        preg_replace('/(\s){1,}(spec\.|sp\.)$/',  ' '. self::POSTFIX_SPECIES_PLURALIS, $val[self::TABLE_MASTER_NAME_COL]);
                }
            }

            foreach ((array)$this->objectlessTaxa as $val)
            {
                $this->taxonList[$val["taxon"]]= [ "taxon" => $val["taxon"] ];
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
                            "english_article" => $this->ttik[$key]["_english_article"],
                            "dutch_article" => $this->ttik[$key]["_dutch_article"],
                            "synonyms" => $this->ttik[$key]["synonyms"],
                            "alternative_names" => $this->ttik[$key]["alternative_names"],
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

            $stmt = $this->db->prepare("insert into ".self::TABLE_TAXONLIST." (taxon,collection,synonyms) values (?,?,?)");

            $this->db->query("START TRANSACTION");

            foreach($this->taxonList as $key => $val)
            {
                if (isset($val["object_data"]))
                {
                    $key = array_search($val["object_data"][0]["unitid"],array_column((array)$this->NBA, "unitid"));
                    if ($key!==false)
                    {
                        $collection = $this->NBA[$key]["collection"];
                    }
                    else
                    {
                        $collection = "?";                        
                    }
                }

                if (!empty($val["taxonomy"]["synonyms"]))
                {
                    $synonyms=json_encode($val["taxonomy"]["synonyms"]);
                }
                else
                {
                    $synonyms=null;
                }

                $stmt->bind_param('sss', $val["taxon"], $collection, $synonyms);
                $stmt->execute();
            }

            $this->db->query("COMMIT");

            $this->log(sprintf("saved taxonlist (%s records)",count($this->taxonList)),self::DATA_MESSAGE,"taxonlist");
        }

        public function addObjectDataToTL()
        {
            $d=[];

            foreach ((array)$this->taxonList as $val)
            {
                $matches=[];

                foreach((array)$this->masterListObjectIndex[isset($val["taxon_original"]) ? $val["taxon_original"] : $val["taxon"]] as $objIndex)
                {
                    $match = $this->masterList[$objIndex];

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

                if (empty($val["object_data"]))
                {
                    if (array_search($val["taxon"], array_column($this->objectlessTaxa, "taxon"))===false)
                    {
                        $this->log(sprintf("no masterList match found for taxon %s (!?)",$val["taxon"]),self::DATA_ERROR,"objects");    
                    }
                }

                $d[]=$val;
            }

            $this->taxonList = $d;
            $this->exhibitionRooms_ML=array_filter(array_unique((array)$this->exhibitionRooms_ML));
        }
            

        public function addCRSImagesToTL()
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
                $IUCN = array_values(array_filter($this->IUCN,
                    function($a) use ($val)
                    {
                        return 
                            $a["scientific_name"]==$val["taxon"] ||
                            (isset($val["taxonomy"]) && isset($val["taxonomy"]["_nomen"]) && $a["scientific_name"]==$val["taxonomy"]["_nomen"]);
                    }));

                if (empty($IUCN))
                {
                    $this->log(sprintf("no IUCN match found for taxon %s",$val["taxon"]),self::DATA_ERROR,"IUCN");
                }
                else
                {   
                    if (count($IUCN)>1)
                    {
                        $key = array_search("Global", array_column($IUCN, "region"));

                        if ($key===false)
                        {
                            $key = array_search("Europe", array_column($IUCN, "region"));
                        }

                        if ($key===false)
                        {
                            $key=0;
                        }
                    }
                    else
                    {
                        $key=0;
                    }

                    // NA, NE en DD worden niet weergegeven in de app (maar wel geleverd en door Q42 geaccepteerd)
                    // if (!in_array($IUCN[$key]["category"],["NA","NE","DD"]))
                    // {
                    $val["IUCN"] =
                        [
                            "name" => "Beschermingsstatus",
                            "name_addendum" => ($IUCN[$key]["region"]!="Global" ? " (".$IUCN[$key]["region"].")" : ""),
                            "category" => $IUCN[$key]["category"],
                            "population_trend" => $IUCN[$key]["population_trend"],
                            "category_label" => $this->IUCN_statusTranslations[$IUCN[$key]["category"]],
                            "trend_label" => $this->IUCN_trendTranslations[$population_trend],
                            "assessment_date" => $IUCN[$key]["assessment_date"],
                        ];
                    // }
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
            $log=[];
            foreach ($this->taxonList as $val)
            {
                /*
                    taxonomies are matched on taxon, nomen, synonym and classification
                    if no match was found there's no point trying again for the content
                */
                if (!isset($val["taxonomy"]))
                {
                    $this->log(sprintf("no TTIK content for %s (no match)",$val["taxon"]),self::DATA_ERROR,"TTIK content");
                    $log[] = [ "taxon" => $val["taxon"], "status" => "no taxon match" ];
                    $d[]=$val;
                    continue;
                }

                if (isset($val["taxonomy"]["_match"]) && isset($val["taxonomy"]["_match"]["_local_key"]))
                {
                    $key = $val["taxonomy"]["_match"]["_local_key"];
                }

                if (!empty($this->ttik[$key]["descriptions"]))
                {
                    $val["texts"]["ttik"]=$this->ttik[$key]["descriptions"];
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

                        if (!empty($this->ttik[$key]["descriptions"]))
                        {
                            $val["texts"]["ttik"]=$this->ttik[$key]["descriptions"];
                        }
                    }
                }

                if (!isset($val["texts"]["ttik"]) || empty($val["texts"]["ttik"]))
                {
                    $this->log(sprintf("no TTIK content for %s (no content)",$val["taxon"]),self::DATA_ERROR,"TTIK content");
                    $log[] = [ "taxon" => $val["taxon"], "status" => "no content" ];
                }
                else
                {
                    // foreach ($val["texts"]["ttik"] as $tKey => $tVal)
                    // {
                    //     $val["texts"]["ttik"][$tKey]["body"] = trim($this->_reformatFormatting($tVal["body"]));
                    // }
                    
                    $log[] = [ "taxon" => $val["taxon"], "status" => "got content" ];
                }

                $d[]=$val;
            }
            $this->taxonList = $d;

            $this->_storeTtikContentDekking($log);
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

                        if (array_search($needle_taxon, $a["_taxon"])!==false)
                        {
                            return true;
                        }

                        return array_search($needle_nomen, $a["_taxon"])!==false;
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

                $maxTaxonArticles = $this->maxTaxonArticles;

                if (in_array($val["taxon"], $this->taxonArticlesSpecialCases))
                {
                    $maxTaxonArticles = $this->maxTaxonArticlesSpecialCases;
                }

                // matching on higher classification
                if ((count($matched_on_taxon)+count($matched_on_synonym))<$maxTaxonArticles && isset($val["taxonomy"]["classification"]))
                {

                    foreach (array_slice(array_reverse($val["taxonomy"]["classification"]), 1, null) as $hKey=>$hVal)
                    {
                        $needle_nomen = $hVal["taxon"];
                        $needle_taxon = trim($hVal["taxon"] . ' ' . $hVal["authorship"]);

                        $matched_on_classification[$hKey] = array_filter(
                            $this->natuurwijzer,
                            function($a) use ($needle_nomen,$needle_taxon,&$used_ids)
                            {
                                if (!isset($a["_taxon"]))
                                {
                                    return false;
                                }

                                $key = array_search($needle_nomen, $a["_taxon"]);

                                if ($key===false && ($needle_nomen==$needle_taxon))
                                {
                                    return false;
                                }

                                $key = array_search($needle_taxon, $a["_taxon"]);

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

                            if (!in_array($needle, $this->roomsToMatchLinksOn))
                            {
                                continue;
                            }

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

                                        $key = array_search($needle, $a["_exhibition_rooms"])!==false;
                                        
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
                // die();

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
                            $this->log(sprintf("added topstukken content to '%s'",$val["taxon"]),self::DATA_MESSAGE,"topstukken");
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
                            $lKey = array_search($object["unitid"], array_column($this->leenobjecten, "registratienummer"));

                            if ($lKey!==false && !empty($this->leenobjecten[$lKey]["_afbeeldingen"]))
                            {
                                $val["object_data"][$key]["images"] = $this->leenobjecten[$lKey]["_afbeeldingen"];
                            }
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
                                $val["object_data"][$key]["images"] = array_intersect($this->imageSelection[$object["unitid"]],$object["images"]);
                            }
                        }
                        else
                        {
                            $val["object_data"][$key]["images"] = null;
                            // $this->log(sprintf("images of taxon %s, unitid %s skipped because object is not present in image selection",$val["taxon"],$object["unitid"]),self::DATA_ERROR,"IMAGES");
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
            $added=0;

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
                    $needle_nomen = null;
                }

                $key = array_search($needle_taxon, array_column($this->imageSquares, "_scientific_name_ic"));

                if ($key===false && isset($needle_nomen))
                {
                    $key = array_search($needle_nomen, array_column($this->imageSquares, "_scientific_name_ic"));
                }

                if ($key!==false)
                {
                    $val["image_square"] = $this->imageSquares[$key];
                    $added++;
                }
                else
                {
                    // $key=array_search($val["taxon"], array_column((array)$this->objectlessTaxa, "taxon"));
                    $key=array_search($val["taxon"], array_column((array)$this->ttikSpeciesPhotos, "taxon"));

                    if ($key!==false)
                    {
                        // $val["image_square"]["url"] =  $this->objectlessTaxa[$key]["main_image"];
                        $val["image_square"]["url"] =  $this->ttikSpeciesPhotos[$key]["main_image"];
                        $added++;
                    }
                }
    
                $d[]=$val;

            }

            $this->taxonList = $d;

            $this->log(sprintf("matched %s square images to a masterlist entry",$added),self::DATA_MESSAGE,"squares");
        }

        public function addFavourites()
        {
            $d=[];

            foreach ($this->taxonList as $val)
            {
                if (isset($val["taxonomy"]))
                {
                    $needle = $val["taxonomy"]["_nomen_ic"];
                }
                else
                {
                    $needle = strtolower($val["taxon"]);
                }

                $key = array_search($needle, array_column($this->favourites, "_taxon_ic"));

                if ($key!==false)
                {
                    $val["favourite"] = [ "rank" => $this->favourites[$key]["rank"] ];
                    $this->favourites[$key]["_assigned"]=true;
                    $this->log(sprintf("added favourite rank %s for '%s'",$this->favourites[$key]["rank"],$val["taxon"]),self::DATA_MESSAGE,"favourites");
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
                $classification_articles=0;

                $maxTaxonArticles = $this->maxTaxonArticles;
                $maxTotalArticles = $this->maxTotalArticles;

                if (in_array($val["taxon"], $this->taxonArticlesSpecialCases))
                {
                    $maxTaxonArticles = $this->maxTaxonArticlesSpecialCases;
                    $maxTotalArticles = $maxTaxonArticles > $maxTotalArticles ? $maxTaxonArticles : $maxTotalArticles;
                }

                foreach ($val["natuurwijzer_texts_matches"] as $match)
                {
                    if (count($linked_articles) >= $maxTotalArticles)
                    {
                        break;
                    }

                    if ($taxon_articles >= $maxTaxonArticles)
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

                if ($taxon_articles < $maxTaxonArticles)
                {
                    foreach ($val["natuurwijzer_texts_matches"] as $match)
                    {
                        if (count($linked_articles) >= $maxTotalArticles)
                        {
                            break;
                        }

                        if (
                            $classification_articles >= $this->maxClassificationArticles || 
                            ($classification_articles + $taxon_articles) >= $maxTaxonArticles
                        )
                        {
                            break;
                        }

                        if ($match["source"]=="classification")
                        {
                            $linked_articles[] = 
                                $val["texts"]["natuurwijzer"][$match["id"]] +
                                [ "_link_origin"  => $match["source"] ];
                            $classification_articles++;
                        }
                    }
                }


                foreach ($val["natuurwijzer_texts_matches"] as $match)
                {
                    if (count($linked_articles) >= $maxTotalArticles)
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

            $this->_storeNatuurwijzerDekking();
        }

        public function getArticleSettings()
        {
            return [
                "maxTaxonArticles" => $this->maxTaxonArticles,
                "maxTaxonArticlesSpecialCases" => $this->maxTaxonArticlesSpecialCases,
                "taxonArticlesSpecialCases" => json_encode($this->taxonArticlesSpecialCases),
                "maxClassificationArticles" => $this->maxClassificationArticles,
                "maxTotalArticles" => $this->maxTotalArticles,
                "roomsToMatchLinksOn" => $this->roomsToMatchLinksOn
            ];
        }

        public function generateJsonDocuments()
        {
            $this->dateStamp = date("c");
            $this->deleteAllPreviousJsonFiles( "preview" );

            $wrote=0;

            foreach ($this->taxonList as $val)
            {
                if (!isset($val["taxon"]) || empty($val["taxon"]))
                {
                    $this->log("skipping taxonList-item without taxon-value",self::DATA_ERROR,"generator");
                    continue;
                }

                $this->rawDocData = $val;

                foreach($this->availableLanguages as $languageCode => $languageName)
                {
                    $this->setLanguage($languageCode,$languageName);
                    $this->_addDocumentMetaData();
                    $this->_addDocumentFavouriteRank();
                    $this->_addDocumentHeaderImage();
                    $this->_addDocumentTitles();
                    $this->_addDocumentDefinitionsBlock();
                    $this->_addDocumentIUCN();
                    $this->_addDocumentContent();
                    $this->_addDocumentObjects();
                    $this->_addDocumentLinks();
                    $this->_addDocumentDistributionMap();
                    $this->_addDocumentReciprocalLinkText();

                    if (!$this->_checkMinimumRequirements())
                    {
                        continue;
                    }

                    $filename = $this->_generateUniqueFilename( $this->jsonPath["preview"], $this->rawDocData["taxon"] );

                    if (file_put_contents($filename, json_encode($this->document)))
                    {
                        // $this->log(sprintf("wrote %s",$filename),self::DATA_MESSAGE,"generator");
                        $wrote++;
                    }
                    else
                    {
                        $this->log(sprintf("could not write %s",$filename),self::DATA_ERROR,"generator");
                    }

                    $this->document=[];
                }
            }

            $this->log(sprintf("wrote %s files",$wrote),self::DATA_MESSAGE,"generator");

            $this->log(
                sprintf(
                    "taxon %s, object %s has %s NBA data fields",
                    $this->maxNbaFields["taxon"],
                    $this->maxNbaFields["unitid"],
                    $this->maxNbaFields["fields"]
                ),self::DATA_MESSAGE,"generator");

            $this->_storeStatistics();

        }

        public function cleanUp()
        {
            foreach ($this->favourites as $key => $val)
            {
                if ($val["_assigned"]==false)
                {
                    $this->log(sprintf("favourite '%s' with rank %s remained unassigned",$val["taxon"],$val["rank"]),self::DATA_ERROR,"clean-up");
                }
            }
        }

        public function storeEventTimestamp( $event )
        {
            $db = new SQLite3($this->SQLitePath["management"], SQLITE3_OPEN_READWRITE);

            $sql = $db->prepare('delete from ' . self::TABLE_EVENTS . ' where event = ?');
            $sql->bindValue(1, $event);
            $sql->execute();

            $sql = $db->prepare('insert into ' . self::TABLE_EVENTS .' (event) values (?)');
            $sql->bindValue(1, $event);
            $sql->execute();
        }

        public function getEventTimestamps()
        {
            $db = new SQLite3($this->SQLitePath["management"], SQLITE3_OPEN_READWRITE);

            $events=[];
            $sql = $db->prepare('SELECT * FROM ' . self::TABLE_EVENTS);
            $results = $sql->execute();
            while($row = $results->fetchArray())
            {
                $events[$row["event"]]=$row["event_timestamp"];
            }
            $db->close();
            return $events;
        }

        private function _addDocumentMetaData()
        {
            $key = str_replace([" ","."], "_", strtolower($this->rawDocData["taxon"]));

            $this->document["id"] = sprintf("%s-%s",$key,$this->language);
            $this->document["created"] = $this->dateStamp;
            $this->document["last_modified"] = $this->dateStamp;
            $this->document["language"] = $this->language;
            $this->document["_key"] = $key;
        }

        private function _addDocumentHeaderImage()
        {
            $block_name="header_image";

            $this->document[$block_name] = [ "url" => $this->squaredImagePlaceholderURL ];

            if (isset($this->rawDocData["image_square"]))
            {
                if (isset($this->rawDocData["image_square"]["url"]))
                {
                    $this->document[$block_name] = [ "url" => $this->rawDocData["image_square"]["url"] ];
                }
                else
                if (isset($this->rawDocData["image_square"]["filename"]))
                {
                    $this->document[$block_name] =
                        [ "url" => $this->squaredImageURLRoot . $this->rawDocData["image_square"]["filename"] ];
                }
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
                    isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["_nomen"]) ? 
                        $this->rawDocData["taxonomy"]["_nomen"] : 
                        $this->rawDocData["taxon"];

                // $dutchName  = $this->rawDocData["taxonomy"]["dutch"] ?? null;;
                $commonName  = $this->rawDocData["taxonomy"][$this->languageName] ?? null;;

                $this->document[$block_name]["page"] = $commonName ?? $sciName;
                $this->document[$block_name]["main"] = $commonName ?? $sciName;
                $this->document[$block_name]["sub"] = is_null($commonName) ? "" : $sciName;

                if ($this->document[$block_name]["main"]==$this->document[$block_name]["sub"])
                {
                    $this->document[$block_name]["sub"]="";
                }

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

            $scientificNameDisplay = 
                isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["_nomen"]) ?
                    $this->rawDocData["taxonomy"]["_nomen"] : 
                    $this->rawDocData["taxon"];

            /*
                if the scientific name is identical to either the dutch or the english one,
                don't show the scientitfic name (to avoid repetition, for instanc in in the 
                case of aardolie, aardolie, petroleum)
            */
            if (
                !(
                    (
                        isset($this->rawDocData["taxonomy"]) && 
                        isset($this->rawDocData["taxonomy"]["dutch"]) && 
                        $this->rawDocData["taxonomy"]["dutch"] == $scientificNameDisplay
                    )
                    ||
                    (
                        isset($this->rawDocData["taxonomy"]) && 
                        isset($this->rawDocData["taxonomy"]["english"]) &&
                        $this->rawDocData["taxonomy"]["english"] == $scientificNameDisplay
                    )
                )
            )
            {
                $this->document[$block_name]["items"][]=
                    [ "label" => $this->translate("Wetenschappelijke naam"),
                      "order_label" => "wetenschappelijke_naam",
                      "text" =>$scientificNameDisplay
                    ];
            }

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["dutch"]))
            {
                $this->document[$block_name]["items"][]=
                    [ "label" => $this->translate("Nederlandse naam"),
                      "order_label" => "nederlandse_naam",
                      "text" => $this->rawDocData["taxonomy"]["dutch"]
                    ];
            }

            if (isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["english"]))
            {
                $this->document[$block_name]["items"][]=
                    [ "label" => $this->translate("Engelse naam"),
                      "order_label" => "engelse_naam",
                      "text" => $this->rawDocData["taxonomy"]["english"]
                    ];
            }

            if (isset($this->rawDocData["texts"]) && isset($this->rawDocData["texts"]["ttik"]))
            {
                foreach ($this->rawDocData["texts"]["ttik"] as $tKey => $translation)
                {
                    if (isset($translation["description"]) && $translation["language_code"]==$this->language)
                    {
                        foreach ($translation["description"] as $key => $val)
                        {
                            if (
                                $val["title"]==$this->ttikCategoryNames["where"] || 
                                $val["title"]==$this->ttikCategoryNames["when"]
                            )
                            {
                                $this->document[$block_name]["items"][] = 
                                [
                                    "label" => $this->translate($val["title"]==$this->ttikCategoryNames["where"] ? "Waar" : "Wanneer"),
                                    "order_label" => ($val["title"]==$this->ttikCategoryNames["where"] ? "waar" : "wanneer"),
                                    "text" => $this->_reformatFormatting($val["body"])
                                ];
                            }
                        }
                    }

                }
            }

            // if (isset($this->rawDocData["taxonomy"]) && !empty($this->rawDocData["taxonomy"]["synonyms"]))
            // {
            //     $this->document[$block_name]["items"][]=
            //         [ "label" => count($this->rawDocData["taxonomy"]["synonyms"])>1 ? "Synoniemen" : "Synoniem",
            //           "order_label" => "synoniemen",
            //           "text" => implode("; ",$this->rawDocData["taxonomy"]["synonyms"])
            //         ];
            // }

            if (isset($this->rawDocData["taxonomy"]) && !empty($this->rawDocData["taxonomy"]["alternative_names"]))
            {
                $this->document[$block_name]["items"][]=
                    [ "label" => $this->translate("Ook bekend als"),
                      "order_label" => "ook_bekend_als",
                      "text" => implode("; ",array_map(function($a)
                        {
                            return sprintf("%s (%s)",$a["name"],$this->translate($a["language"]));
                        }, $this->rawDocData["taxonomy"]["alternative_names"]))
                    ];
            }

            usort($this->document[$block_name]["items"],function($a,$b)
            {
                $order=[
                    "wetenschappelijke_naam" => 0,
                    "nederlandse_naam" =>  ($this->language=='nl' ? 1 : 2),
                    "engelse_naam" => ($this->language=='nl' ? 2 : 1),
                    "synoniemen" => 3,
                    "ook_bekend_als" => 4,
                    "waar" => 5,
                    "wanneer" => 6
                ];

                $a = $order[$a["order_label"]];
                $b = $order[$b["order_label"]];
                return (($a == $b) ? 0 : (($a < $b) ? -1 : 1));
            });

            $this->document[$block_name]["items"] = array_map(function($a)
            { 
                unset($a["order_label"]);
                $a["text"]=trim($a["text"]);
                return $a;
            },
            $this->document[$block_name]["items"]);
        }

        private function _addDocumentContent()
        {

            $block_name="content";

            unset($this->document[$block_name]);

            if (isset($this->rawDocData["texts"]) && isset($this->rawDocData["texts"]["ttik"]))
            {

                $ttik_texts = array_filter($this->rawDocData["texts"]["ttik"],
                function($a)
                {
                    return $a["language_code"]==$this->language;
                });

                $t=[];
                // foreach ($this->rawDocData["texts"]["ttik"] as $key => $val)
                foreach ($ttik_texts as $key => $val)
                {
                    foreach ($val["description"] as $dKey => $dVal)
                    {
                        if (
                            $dVal["title"]!=$this->ttikCategoryNames["where"] && 
                            $dVal["title"]!=$this->ttikCategoryNames["when"])
                        {
                            if (isset($dVal["title"]) && $dVal["title"]!=$this->ttikCategoryNames["description"])
                            {                                
                                $t[] = [ "type" => "h1", "text" => $dVal["title"] ]; 
                            }
                            if (isset($dVal["body"]))
                            {                                
                                $t[] = [
                                    "type" => "paragraph",
                                    "text" => trim($this->_reformatFormatting($dVal["body"])),
                                    "_title" => $dVal["title"],
                                    "_verified" => $dVal["verified"]
                                    
                                ];
                            }
                        }
                    }                 
                }

                uasort($t,function($a,$b)
                {
                    if ($a["_title"]==$this->ttikCategoryNames["description"])
                    {
                        return -1;
                    }              
                    return (($a == $b) ? 0 : (($a < $b) ? -1 : 1));
                });

                if (isset($this->autoTranslationTexts[$this->language]))
                {
                    foreach(array_reverse($t,true) as $key => $val)
                    {
                        if ($val["type"]=="paragraph")
                        {
                            if ($val["_verified"]!="1")
                            {
                                $t[$key]["text"] .= "\n\n" . $this->autoTranslationTexts[$this->language];    
                            }
                            break;
                        }
                    }
                }

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
                    if (empty($object["unitid"]))
                    {
                        $this->log(sprintf("no unitid for object of %s",$this->rawDocData["taxon"]),self::DATA_ERROR,"generator");
                        continue;
                    }

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

                    if (!isset($o["images"]))
                    {
                        $o["images"][] = [ "url" => $this->objectImagePlaceholderURL, "is_placeholder" => true ];
                        $potential_topstuk_image = [ "url" => $this->objectImagePlaceholderURL ];
                    }

                    $room = $this->exhibitionRoomsTranslations[$object["exhibition_room"]] ?? $object["exhibition_room"];
                    $room = $this->exhibitionRoomsPublic[$room] ?? $room;

                    $o["location"] = $this->translate($room);

                    $o["data"] = [
                        [ "label" => $this->translate("Registratienummer"), "text" => $object["unitid"] ],
                        [ "label" => $this->translate("Museumzaal"), "text" => $this->translate($room) ]
                    ];

                    $key = array_search($object["unitid"], array_column((array)$this->NBA, 'unitid'));

                    if ($key!==false)
                    {
                        $nbaData=$this->_distillNBAData($this->NBA[$key]["document"]);
                        $o["data"] = array_merge($o["data"],$nbaData);
                        if (count($nbaData)>$this->maxNbaFields["fields"])
                        {
                            $this->maxNbaFields = [
                                "taxon" => $this->rawDocData["taxon"], 
                                "unitid" => $object["unitid"], 
                                "fields" => count($nbaData)
                            ];
                        }
                    }

                    $key = array_search($object["unitid"], array_column($this->leenobjecten, 'registratienummer'));

                    if ($key!==false)
                    {
                        $o["data"] = array_merge(
                            $o["data"],
                            [[
                                "label" => $this->translate("Dit object is een bruikleen van"),
                                "text" =>  $this->leenobjecten[$key]["geleend_van"]
                            ]]
                        );
                    }

                    $o["data"] = array_values(array_filter($o["data"], function($a) { return !empty($a["text"]) && strtolower($a["text"])!="not applicable"; }));

                    if (isset($this->rawDocData["topstukken"]))
                    {
                        $key = array_search($object["unitid"], array_column($this->rawDocData["topstukken"], "registrationNumber"));

                        if ($key!==false)
                        {
                            if (!is_null($potential_topstuk_image))
                            {
                                $topstuk = $this->rawDocData["topstukken"][$key];

                                $o["topstuk_link"] = [
                                    "url" => $topstuk["_full_url"],
                                    "text" => $this->_conjureUpTopstukLinkText(
                                        $topstuk["title"],
                                        // $this->rawDocData["taxonomy"]["dutch"] ?? $this->rawDocData["taxon"]
                                        $this->rawDocData["taxonomy"][$this->languageName] ?? $this->rawDocData["taxon"]
                                    )
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

            if (isset($this->document[$block_name]))
            {
                usort($this->document[$block_name], function($a,$b)
                {
                    if (!is_null($a["topstuk_link"]) && is_null($b["topstuk_link"]))
                    {
                        return -1;
                    }

                    if (is_null($a["topstuk_link"]) && !is_null($b["topstuk_link"]))
                    {
                        return 1;
                    }

                    if ((!isset($a["images"]) || !$a["images"][0]["is_placeholder"]) && (isset($b["images"]) && $b["images"][0]["is_placeholder"]))
                    {
                        return -1;
                    }

                    if ((isset($a["images"]) && $a["images"][0]["is_placeholder"]) && (!isset($b["images"]) || !$b["images"][0]["is_placeholder"]))
                    {
                        return 1;
                    }

                    return $a["id"] < $b["id"] ? -1 : 1;
                });
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
                    $imageUrl = (
                        isset($val["_image_urls"]["header_tablet"]) ?
                            $val["_image_urls"]["header_tablet"] : (
                                isset($val["_image_urls"]["header_mobiel"]) ?
                                    $val["_image_urls"]["header_mobiel"] : $val["_image_urls"]["original"]
                            )
                    );

                    $links[] = [
                        "title" => $val["title"],
                        "description" => $this->_reformatFormatting($val["intro_text"]),
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
                    "name" => $this->translate($this->rawDocData["IUCN"]["name"]) . $this->rawDocData["IUCN"]["name_addendum"],
                    "category" => $this->rawDocData["IUCN"]["category"],
                    "label" => $this->translate($this->rawDocData["IUCN"]["category_label"]),
                    // "url_link" => $this->iucnURLs["general_link"],
                    // "url_label" => $this->translate($this->iucnURLs["general_link_label"]),
                    "credit" => sprintf(
                        $this->translate("Bron: IUCN (beoordelingsdatum: %s)"),
                        $this->rawDocData["IUCN"]["assessment_date"]
                    )
                ];
            }
        }

        private function _addDocumentDistributionMap()
        {
            $block_name="distribution_map";

            unset($this->document[$block_name]);

            $key = array_search($this->rawDocData["taxon"], array_column($this->maps, "taxon"));

            if ($key!==false)
            {
                $l = $this->availableLanguages[$this->language];

                $this->document[$block_name]= [
                    "image_url" => $this->maps[$key]["url"],
                    "label" => @$this->maps[$key]["text_".$l],
                    "credit" => sprintf($this->translate("Bron: %s"),$this->maps[$key]["citation"])
                ];

                $this->log(sprintf("added map for '%s'",$this->rawDocData["taxon"]),self::DATA_MESSAGE,"maps");
            }
        }

        private function _addDocumentReciprocalLinkText()
        {
            $block_name="link_description";

            $l = $this->availableLanguages[$this->language];

            if (isset($this->rawDocData["taxonomy"][$l."_article"]) && isset($this->rawDocData["taxonomy"][$l]))
            {
                $name = sprintf("%s %s",$this->rawDocData["taxonomy"][$l."_article"],$this->rawDocData["taxonomy"][$l]);
            }
            else
            {
                $name = 
                    isset($this->rawDocData["taxonomy"]) && isset($this->rawDocData["taxonomy"]["_nomen"]) ? 
                        $this->rawDocData["taxonomy"]["_nomen"] : 
                        $this->rawDocData["taxon"];
            }
            
            $this->document[$block_name] = sprintf($this->translate("Lees meer over %s"),$name);
        }

        private function _addDocumentFavouriteRank()
        {
            $block_name="favourites_rank";

            if (isset($this->rawDocData["favourite"]))
            {
                $this->document[$block_name] = $this->rawDocData["favourite"]["rank"];
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

        private function _generateUniqueFilename( $path, $name, $ext = "json" )
        {
            $i=0;
            $filename_base = strtolower(
                str_replace(
                    [" ",".",",","/","\"",")","(","!","?","&"],
                    "_",
                    preg_replace('/[[:^print:]]/', '', $name)));

            return $path . $filename_base . "-" . $this->language . "." . $ext;              
        }

        private function _distillNBAData( $document )
        {
            $d=[];

            $event = $document["gatheringEvent"];

            $str1 = trim(implode(", ",array_filter(
                [
                    @$event["locality"],
                    @$event["city"],
                    @$event["island"],
                    @$event["provinceState"],
                    @$event["country"],
                    @$event["continent"],
                    @$event["worldRegion"]
                 ]   
                ,function($a) { return !empty($a); })
            ));

            $str1 = implode(", ",array_unique(array_map(function($a) { return trim($a); },explode(",",str_replace(";", ",", $str1)))));
            $str2 = trim($event["localityText"]);

            similar_text(strtolower(preg_replace('/(\W)*/', "", $str1)),strtolower(preg_replace('/(\W)*/', "", $str2)),$pct);

            if (empty($str1))
            {
                $vindplaats = $str2;
            }
            else
            if (empty($str2))
            {
                $vindplaats = $str1;
            }
            else
            if ($pct>=45)
            {
                $vindplaats = $str2;
            }
            else
            {
                $vindplaats = $str1 . ", " . $str2;
            }

            $d[] = [
                "label" => $this->translate("Vindplaats"),
                "order_label" => "vindplaats",
                "text" =>  $vindplaats
            ];

            if (isset($event["gatheringPersons"]))
            {
                $p=[];
                foreach ($event["gatheringPersons"] as $val)
                {
                    $p[]=$val["fullName"];
                }

                $d[] = [
                    "label" => $this->translate("Verzamelaar(s)"),
                    "order_label" => "verzamelaars",
                    "text" =>  implode("; ", $p)
                ];
            }

            if (isset($event["dateTimeBegin"]))
            {
                $d[] = [
                    "label" => $this->translate("Verzameld"),
                    "order_label" => "verzameld",
                    "text" =>  (isset($event["dateTimeBegin"]) ? 
                        date("d-m-Y", strtotime($event["dateTimeBegin"])) :
                        (isset($event["dateText"]) ? $event["dateText"] : "" ))
                ];
            }

            $d[] = [
                "label" => $this->translate("Expeditie"),
                "order_label" => "expeditie",
                "text" => $event["projectTitle"]
            ];

            $d[] = [
                "label" => $this->translate("Verzamelmethode"),
                "order_label" => "verzamelmethode",
                "text" => $event["method"]
            ];

            $d[] = [
                "label" => $this->translate("Verzameld op hoogte"),
                "order_label" => "verzameld_op_hoogte",
                "text" => $event["altitude"]
            ];

            $d[] = [
                "label" => $this->translate("Verzameld op diepte"),
                "order_label" => "verzameld_op_diepte",
                "text" => $event["depth"]
            ];    
    

            $d[] = [
                "label" => $this->translate("Verzameld in biotoop"),
                "order_label" => "verzameld_in_biotoop",
                "text" => @$event["biotopeText"]
            ];

            $d[] = [
                "label" => $this->translate("Type object"),
                "order_label" => "type_object",
                "text" =>  trim(
                    implode("; ",
                        array_filter(
                            [
                                $this->_nbaTranslateValue(@$document["recordBasis"]),
                                $this->_nbaTranslateValue(@$document["kindOfUnit"]),
                                $this->_nbaTranslateValue(@$document["preparationType"])
                            ],
                            function($a)
                            { 
                                return !empty($a) && !in_array(strtolower($a), ["not applicable"]);
                            }
                        )))
            ];

            $d[] = [
                "label" => $this->translate("Aantal"),
                "order_label" => "aantal",
                "text" =>  isset($document["numberOfSpecimen"]) && $document["numberOfSpecimen"] > 1 ? strval($document["numberOfSpecimen"]) : null
            ];

            $d[] = [
                "label" => $this->translate("Sekse"),
                "order_label" => "sekse",
                "text" =>  @$document["sex"]
            ];

            $d[] = [
                "label" => $this->translate("Collectienaam"),
                "order_label" => "collectienaam",
                "text" =>  @$document["collectionType"]
            ];

            $d[] = [
                "label" => $this->translate("Levensfase"),
                "order_label" => "levensfase",
                "text" =>  @$document["phaseOrStage"]
            ];

            $d[] = [
                "label" => $this->translate("Lithostratigrafische formatie"),
                "order_label" => "lithostratigrafische_formatie",
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
                    "label" => $this->translate("Typestatus"),
                    "order_label" => "typestatus",
                    "text" =>  $typeStatus
                ];

                $d[] = [
                    "label" => $this->translate("Steentype"),
                    "order_label" => "steentype",
                    "text" =>  $rockType
                ];

                $d[] = [
                    "label" => $this->translate("Geassocieerd mineraal"),
                    "order_label" => "geassocieerd_mineraal",
                    "text" =>  $associatedMineralName
                ];
            }

            $d = array_values(array_filter($d, function($a) { return !empty($a["text"]) && strtolower($a["text"])!="not applicable"; }));

            uasort($d,function($a,$b)
            {
                $order=[
                    "vindplaats"=>0,
                    "verzamelaar(s)"=>1,
                    "verzameld"=>2,
                    "sekse"=>3,
                    "levensfase"=>4,
                    "steentype"=>5,
                    "geassocieerd_mineraal"=>6,
                    "type_object"=>7,
                    "typestatus"=>9,
                    "collectienaam"=>10,
                    "aantal"=>8,
                ];

                $a = $order[$a["order_label"]] ?? 99;
                $b = $order[$b["order_label"]] ?? 99;
                return (($a == $b) ? 0 : (($a < $b) ? -1 : 1));
            });

            $d = array_map(function($a)
            { 
                unset($a["order_label"]);
                return $a;
            },
            $d);

            return $d;
        }

        private function _nbaTranslateValue( $str )
        {
            if (empty($str))
            {
                return;
            }

            return $this->nba_valueTranslations[$str] ?? $str;
        }

        private function _reformatFormatting( $content )
        {
            return str_replace(
                ['<em>','</em>','<strong>','</strong>'], 
                ['<i>','</i>','<b>','</b>'], 
                strip_tags($content,'<strong><b><em><i><u>')
            );
        }

        private function _conjureUpTopstukLinkText( $topstuk_title, $species_name )
        {
            similar_text(strtolower($topstuk_title),strtolower($species_name),$pct);

            $generic = $this->translate("Het verhaal achter dit topstuk");

            if ($pct>80)
            {
                // return sprintf("%s (%s) %s", $generic, $pct, $species_name);
                return $generic;
            }
            else
            {
                // return sprintf("+ %s. %s (%s) %s", $topstuk_title, $generic, $pct, $species_name);
                return sprintf("%s: %s", $topstuk_title, lcfirst($generic));
            }
        }

        private function _storeNatuurwijzerDekking()
        {
            $db = new SQLite3($this->SQLitePath["management"], SQLITE3_OPEN_READWRITE);
            $sql = $db->prepare('delete from natuurwijzer_dekking');
            $sql->execute();

            $sql = $db->prepare('insert into natuurwijzer_dekking (taxon,links) values (?,?)');

            $db->exec( 'BEGIN;' );

            foreach ($this->taxonList as $key => $val)
            {
                $links = array_map(function($a)
                    {
                        return [
                            "title" => $a["title"],
                            "_full_url" => $a["_full_url"],
                            "_link_origin" => $a["_link_origin"]
                        ];
                    }, (array)$val["texts"]["natuurwijzer"]);

                $sql->bindValue(1, $val["taxon"]);
                $sql->bindValue(2, json_encode($links));
                $sql->execute();
            }

            $db->exec( 'COMMIT;' );
        }

        private function _storeTtikContentDekking( $log )
        {
            $db = new SQLite3($this->SQLitePath["management"], SQLITE3_OPEN_READWRITE);
            $sql = $db->prepare('delete from ttik_content_dekking');
            $sql->execute();

            $sql = $db->prepare('insert into ttik_content_dekking (taxon,status) values (?,?)');

            $db->exec( 'BEGIN;' );

            foreach ($log as $key => $val)
            {
                $sql->bindValue(1, $val["taxon"]);
                $sql->bindValue(2, $val["status"]);
                $sql->execute();
            }

            $db->exec( 'COMMIT;' );
        }

        private function _storeStatistics()
        {
            $per_room=[];
            $per_taxon=[];

            foreach ($this->taxonList as $val)
            {
                // $val["taxon"]

                if (isset($val["image_square"]) && (isset($val["image_square"]["url"]) || isset($val["image_square"]["filename"])))
                {
                    $have_image=true;
                }
                else
                {
                    $have_image=false;
                }

                $have_description=false;

                foreach ((array)$val["texts"]["ttik"] as $key => $tVal)
                {
                    if ($tVal["language_code"]=="nl")
                    {
                        foreach ($tVal["description"] as $uKey => $uVal)
                        {
                            if (isset($uVal["title"]) && $uVal["title"]==$this->ttikCategoryNames["description"])
                            {                                
                                $have_description=true;
                            }
                        }
                    }
                }

                $per_taxon["taxon"]=isset($per_taxon["taxon"]) ? $per_taxon["taxon"]+1 : 1;

                if ($have_image)
                {
                    $per_taxon["with_image"]=isset($per_taxon["with_image"]) ? $per_taxon["with_image"]+1 : 1;
                }

                if ($have_description)
                {
                    $per_taxon["with_description"]=isset($per_taxon["with_description"]) ? $per_taxon["with_description"]+1 : 1;
                }

                if ($have_image && $have_description)
                {
                    $per_taxon["with_image_and_description"]=isset($per_taxon["with_image_and_description"]) ? $per_taxon["with_image_and_description"]+1 : 1;
                }


                if (count((array)$val["object_data"])>0)
                {
                    foreach ((array)$val["object_data"] as $object)
                    {
                        $room = $this->exhibitionRoomsTranslations[$object["exhibition_room"]];
                        $room =  $this->exhibitionRoomsPublic[$room] ?? $room;

                        if ($seen_room_for_this_taxon[$room]===true)
                        {
                            continue;
                        }

                        $seen_room_for_this_taxon[$room] = true;

                        $per_room[$room]["taxon"]=isset($per_room[$room]["taxon"]) ? $per_room[$room]["taxon"]+1 : 1;

                        if ($have_image)
                        {
                            $per_room[$room]["with_image"]=
                                isset($per_room[$room]["with_image"]) ? $per_room[$room]["with_image"]+1 : 1;
                        }

                        if ($have_description)
                        {
                            $per_room[$room]["with_description"]=
                                isset($per_room[$room]["with_description"]) ? $per_room[$room]["with_description"]+1 : 1;
                        }

                        if ($have_image && $have_description)
                        {
                            $per_room[$room]["with_image_and_description"]=
                                isset($per_room[$room]["with_image_and_description"]) ? $per_room[$room]["with_image_and_description"]+1 : 1;
                        }

                    }
                }
                else
                {
                    $room = "(taxa w/o objects)";
                    $per_room[$room]["taxon"]=isset($per_room[$room]["taxon"]) ? $per_room[$room]["taxon"]+1 : 1;

                    if ($have_image)
                    {
                        $per_room[$room]["with_image"]=isset($per_room[$room]["with_image"]) ? $per_room[$room]["with_image"]+1 : 1;
                    }

                    if ($have_description)
                    {
                        $per_room[$room]["with_description"]=isset($per_room[$room]["with_description"]) ? $per_room[$room]["with_description"]+1 : 1;
                    }

                    if ($have_image && $have_description)
                    {
                        $per_room[$room]["with_image_and_description"]=
                            isset($per_room[$room]["with_image_and_description"]) ? $per_room[$room]["with_image_and_description"]+1 : 1;
                    }

                }

                unset($seen_room_for_this_taxon);

            }

            $db = new SQLite3($this->SQLitePath["management"], SQLITE3_OPEN_READWRITE);
            $sql = $db->prepare('delete from taxa_per_room');
            $sql->execute();

            $sql = $db->prepare('insert into taxa_per_room (room,taxon_count,with_image,with_description,with_image_and_description) values (?,?,?,?,?)');


            $db->exec( 'BEGIN;' );

            foreach ($per_room as $room => $val)
            {
                $sql->bindValue(1, empty($room) ? "?" : $room);
                $sql->bindValue(2, $val["taxon"] ?? 0);
                $sql->bindValue(3, $val["with_image"] ?? 0);
                $sql->bindValue(4, $val["with_description"] ?? 0);
                $sql->bindValue(5, $val["with_image_and_description"] ?? 0);
                $sql->execute();
            }            

            $db->exec( 'COMMIT;' );


            $sql = $db->prepare('delete from taxa_overall');
            $sql->execute();

            $sql = $db->prepare('insert into taxa_overall (taxon_count,with_image,with_description,with_image_and_description) values (?,?,?,?)');

            $sql->bindValue(1, $per_taxon["taxon"] ?? 0);
            $sql->bindValue(2, $per_taxon["with_image"] ?? 0);
            $sql->bindValue(3, $per_taxon["with_description"] ?? 0);
            $sql->bindValue(4, $per_taxon["with_image_and_description"] ?? 0);
            $sql->execute();
        }

        private function _checkMinimumRequirements()
        {
            try {
                if (!isset($this->document["titles"]["main"])) throw new Exception("no main name", 1);
                // if (!isset($this->document["objects"]) || count($this->document["objects"])<1) throw new Exception("no objects", 1);
            }
            catch (Exception $e)
            {
                $this->log(sprintf("skipping %s: %s",$this->rawDocData["taxon"],$e->getMessage()),self::DATA_MESSAGE,"generator");
                return false;
            }

            return true;
        }

    }
