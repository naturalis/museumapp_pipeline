<?php

    class DataBrowser extends BaseClass
    {
        private $files=[];
        private $state;

        public function init()
        {
            $this->checkJsonPaths();   
        }

        public function getFileLinks( $state, $offset=0, $length=100 )
        {
            $this->state = $state;
            $this->_getFiles();
            if (count($this->files)>0)
            {
                $j = json_decode(file_get_contents($this->files[0]["path"]),true);
            }
            return [ "data" => array_slice($this->files, $offset, $length), "total" => count($this->files), "created" => $j["created"] ];
        }

        public function getFile( $state, $file )
        {
            $this->state = $state;
            $f = file_get_contents($this->jsonPath[$this->state] . $file);
            // $f = json_decode($f,true);
            // $f = print_r($f,true);
            return $f;
        }

        public function deletePreviewFiles()
        {
            $this->deleteAllPreviousJsonFiles( "preview" );
        }

        public function publishPreviewFiles()
        {
            $this->_movePreviewFilesToPublish();
        }

        private function _movePreviewFilesToPublish()
        {
            $this->state = "preview";
            $this->_getFiles();

            if (count($this->files)>0)
            {
                $this->deleteAllPreviousJsonFiles( "publish" );

                foreach ($this->files as $val)
                {
                    rename($val["path"], $this->jsonPath["publish"] . $val["filename"]);
                }
            }
        }

        private function _getFiles()
        {
            $this->files=[];

            $files = glob($this->jsonPath[$this->state] . '*.json');
            foreach($files as $file)
            {
                if(is_file($file))
                {
                    $this->files[]=[ "path" => $file, "filename" => basename($file) ];
                }
            }

        }
    }