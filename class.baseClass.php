<?php


    class BaseClass
    {
        public $jsonPath = [ "preview" => null, "publish" => null ];
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
            // if ($source=="TTIK" || $source=="CRS" || $source=="IUCN") return;
            // echo "ERROR: ",$message,"\n";
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


    }