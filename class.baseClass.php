<?php


    class BaseClass
    {
        public $db_credentials;
        public $db;
        public $jsonPath = [ "preview" => null, "publish" => null ];
        public $SQLitePath = [ "selector" => null, "squares" => null, "management" => null  ];
        private $messages=[];

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

        public function log( $message, $level, $source=null )
        {
            $this->messages[]=[ "message" => $message, "level" => $level, "source" => $source ];
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

        public function getMySQLSource( $source )
        {
            $list=[];

            try {
                $sql = $this->db->query("select * from " . $source);
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



    }