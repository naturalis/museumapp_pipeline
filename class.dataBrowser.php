<?php

/*

CREATE TABLE IF NOT EXISTS "document_hashes" (
    "key" string not null,
    "language" string not null,
    "filename" string not null,
    "hash" string not null,
    "last_modified" DATETIME not null,
    "record_created" DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(key,language)
);

*/

    class DataBrowser extends BaseClass
    {
        private $db;
        private $files=[];
        private $state;
        private $SQLitePath;

        public function init()
        {
            $this->checkJsonPaths();   
        }

        public function setSQLitePath( $path )
        {
            $this->SQLitePath = $path;
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
            $this->_initializeSQLite();

            $this->state = "preview";

            $this->_getFiles();
            $this->_calculateHashes();
            $this->_movePreviewFilesToPublish();
        }

        private function _calculateHashes()
        {
            if (count($this->files)>0)
            {
                $to_save=[];

                foreach ($this->files as $file)
                {
                    // get new file ande decode
                    $doc = json_decode(file_get_contents($file["path"]));

                    // strip transient properties and calculate hash
                    $stripped = clone $doc;
                    unset($stripped->id);
                    unset($stripped->created);
                    unset($stripped->last_modified);
                    $new_hash = md5(json_encode($stripped));

                    // get last saved hash; will be empty for new taxa (key)
                    $current = $this->_getCurrentRecord($doc->_key,$doc->language);

                    // if new hash differs from old hash, the file is new
                    if (!isset($current["hash"]) || $current["hash"] != $new_hash)
                    {
                        // remember file to save, keeping the value of the last_modified-property
                        $to_save[]=[
                            "key"=>$doc->_key,
                            "language"=>$doc->language,
                            "hash"=>$new_hash,
                            "last_modified"=>$doc->last_modified,
                            "filename"=>$file["filename"]
                        ];
                    }
                    // if the new and old hash are the same, we want to update the value of last_modified to the one in the database
                    else
                    {
                        // if the last_modified values of new and saved files already are the same, we don't have to do anything (same review session)
                        if ($doc->last_modified==$current["last_modified"])
                        {
                            // pass
                        }
                        // if they differ, we set the value of last_modified to the one in the database, and re-save the doc to file
                        else
                        {
                            $doc->last_modified = $current["last_modified"];
                            file_put_contents($file["path"], json_encode($doc));
                        }
                    }
                }

                // save the new hashes
                $this->setDbTransaction();

                foreach ($to_save as $item)
                {
                    $this->_saveDocumentHash(
                        $item["key"],
                        $item["language"],
                        $item["hash"],
                        $item["last_modified"],
                        $item["filename"]
                    );
                }

                $this->setDbTransaction(true);
            }
        }

        private function _movePreviewFilesToPublish()
        {
            // $this->state = "preview";
            // $this->_getFiles();

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

        public function setDbTransaction( $commit=false )
        {
            $this->db->exec( $commit ? 'COMMIT;' : 'BEGIN;' );
        }

        private function _initializeSQLite()
        {
            $this->db = new SQLite3($this->SQLitePath, SQLITE3_OPEN_READWRITE);
        }

        private function _getCurrentRecord( $key, $language )
        {
            $sql = $this->db->prepare('SELECT * FROM document_hashes where key = ? and language = ?');
            $sql->bindValue(1, $key);
            $sql->bindValue(2, $language);
            $results = $sql->execute();
            return $results->fetchArray();
        }

        private function _saveDocumentHash( $key, $language, $hash, $last_modified, $filename )
        {
            $sql = $this->db->prepare('INSERT OR REPLACE INTO document_hashes (key,language,filename,hash,last_modified) VALUES (?,?,?,?,?)');

            $sql->bindValue(1, $key);
            $sql->bindValue(2, $language);
            $sql->bindValue(3, $filename);
            $sql->bindValue(4, $hash);
            $sql->bindValue(5, $last_modified);
            $sql->execute();
        }

    }