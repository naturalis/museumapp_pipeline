<?php


    class BaseClass
    {
        public $db_credentials;
        public $db;
        public $jsonPath = [ "preview" => null, "publish" => null ];
        public $SQLitePath = [ "selector" => null, "squares" => null, "management" => null  ];
        public $language;
        public $languageDefault="nl";
        private $messages=[];
        private $translations =
            [
                "nl" =>
                    [ 
                        "english" => "Engels",
                        "dutch" => "Nederlands",
                    ],
                "en" =>
                    [
                        // algemeen
                        "Wetenschappelijke naam" => "Scientific name",
                        "Ook bekend als" => "also known as",
                        "Nederlandse naam" => "Dutch name",
                        "Engelse naam" => "English name",
                        "Registratienummer" => "Registration number",
                        "Museumzaal" => "Gallery",
                        "Lees meer over %s" => "Read more about %s",
                        "Waar" => "Where",
                        "Wanneer" => "When",
                        "english" => "English",
                        "dutch" => "Dutch",

                        // objecten
                        "Dit object is een bruikleen van" => "This object is on loan from",
                        "Het verhaal achter dit topstuk" => "The story behind this highlight (in Dutch)",

                        // nba velden
                        "Vindplaats" => "Site",
                        "Verzamelaar(s)" => "Collector(s)",
                        "Verzameld" => "Collected",
                        "Expeditie" => "Expedition",
                        "Verzamelmethode" => "Collection method",
                        "Verzameld op hoogte" => "Collected at height",
                        "Verzameld op diepte" => "Collected at depth",
                        "Verzameld in biotoop" => "Collected in biotope",
                        "Type object" => "Object type",
                        "Aantal" => "Number",
                        "Sekse" => "Sex",
                        "Collectienaam" => "Collection name",
                        "Levensfase" => "Life stage",
                        "Lithostratigrafische formatie" => "Lithostratigraphic formation",
                        "Typestatus" => "Type status",
                        "Steentype" => "Stone type",
                        "Geassocieerd mineraal" => "Associated mineral",

                        // IUCN
                        "Lees meer over de beschermingsstatus" => "Read more about the protection status",
                        "Beschermingsstatus" => "Conservation status",
                        "Bron: IUCN (beoordelingsdatum: %s)" => "Source: IUCN (assessment date: %s)",
                        "Uitgestorven" => "Extinct",
                        "Uitgestorven in het wild" => "Extinct in the wild",
                        "Ernstig bedreigd (kritiek)" => "Critically endangered",
                        "Bedreigd" => "Endangered",
                        "Kwetsbaar" => "Vulnerable",
                        "Gevoelig" => "Near threatened",
                        "Van bescherming afhankelijk" => "Conservation dependent",
                        "Niet bedreigd (veilig)" => "Least concern",
                        "Onzeker" => "Data deficient",
                        "Niet geëvalueerd" => "Not evaluated",
                        "Niet van toepassing" => "Not applicable",
                        "Bron: %s" => "Source: %s",

                        // museumzalen
                        "De dood" => "Death",
                        "Dinotijd" => "Dinosaur era",
                        "De vroege mens" => "Early humans",
                        "De aarde" => "Earth",
                        "De ijstijd" => "Ice age",
                        "Leven" => "Life",
                        "Live science" => "Live science",
                        "De verleiding" => "Seduction",
                    ]
            ];

        const DATA_MESSAGE = 1;        
        const DATA_ERROR = 2;
        const SYSTEM_ERROR = 3;

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

        public function checkJsonPaths()
        {
            foreach ($this->jsonPath as $state => $path)
            {
                if (is_null($path))
                {
                    throw new Exception(sprintf("JSON-path not set: %s",$state), 1);                    
                }
            }
        }

        public function log( $message, $level=self::DATA_MESSAGE, $source=null )
        {
            $this->messages[]=[ "timestamp" => date('d-M-Y H:i:s'), "message" => $message, "level" => $level, "source" => $source ];
        }

        public function getMessages()
        {
            return $this->messages;
        }

        public function deleteAllPreviousJsonFiles( $state )
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

        public function setDatabaseCredentials( $p )
        {
            $this->db_credentials = $p;
        }

        public function connectDatabase()
        {
            $this->db = new mysqli(
                $this->db_credentials["host"],
                $this->db_credentials["user"],
                $this->db_credentials["pass"]
            );

            $this->db->select_db($this->db_credentials["database"]);
            $this->db->set_charset("utf8");
        }

        public function getMySQLSource( $source, $order_by=null )
        {
            $list=[];

            try {
                $sql = $this->db->query("select * from " . $source . ( !empty($sort) ? " order by " . $order_by : "" ));
                $list=[];
                while ($row = $sql->fetch_assoc())
                {
                    $list[]=$row;
                }
            } catch (Exception $e) {
                $this->log(sprintf("could not read table %s",$source),self::SYSTEM_ERROR,"collector");
            }

            return $list;
        }

        public function getMySQLQuery( $query )
        {
            $list=[];

            try {
                $sql = $this->db->query($query);
                $list=[];
                while ($row = $sql->fetch_assoc())
                {
                    $list[]=$row;
                }
            } catch (Exception $e) {
                $this->log($e->getMessage(),self::SYSTEM_ERROR,"collector");
            }

            return $list;
        }

        public function translate($txt)
        {
            return $this->translations[$this->language][$txt] ?? $txt;
        }

    }