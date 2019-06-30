<?php

    class PipelineJobQueuer {

        private $source;
        private $sources = [
            "masterList",
            "CRS",
            "IUCN",
            "natuurwijzer",
            "topstukken",
            "ttik"
        ];
        private $queuePath = '/data/queue/';
        private $publishPath;

        public function setSource( $source )
        {
            if (in_array($source, $this->sources))
            {
                $this->source=$source;
            }
        }

        public function setPublishPath( $path )
        {
            $this->publishPath=$path;
        }

        public function queueRefreshJob()
        {
            if (!is_null($this->source))
            {
                if (!$this->_findEarlierJob())
                {
                    $this->jobfilename = $this->queuePath . "refresh-". $this->source . "-". uniqid();
                    if (!file_put_contents($this->jobfilename,json_encode([ "action" => "refresh", "source" => $this->source ])))
                    {
                        throw new Exception(sprintf("couldn't create job file for %s",$this->source), 1);                        
                    }                    
                }
                else
                {
                    throw new Exception("earlier job file for $this->source exists", 1);                        
                }
            }
        }

        public function findEarlierJobs()
        {
            $files = glob($this->queuePath . "refresh-" . '*');
            $d=[];
            foreach ($files as $val)
            {
                $f=explode("-",basename($val));
                $d[]=$f[1];
            }
            return array_unique($d);
        }

        public function queuePublishJob()
        {
            if (is_null($this->publishPath))
            {
                throw new Exception("publish path not set", 1);                        
            }                    

            $this->jobfilename = $this->queuePath . "publish-". uniqid();
            if (!file_put_contents($this->jobfilename,json_encode([ "action" => "publish", "source" => $this->publishPath ])))
            {
                throw new Exception("couldn't create publishing job file", 1);                        
            }                    
        }

        private function _findEarlierJob()
        {
            $files = glob($this->queuePath . "refresh-". $this->source . '*');
            return !empty($files);
        }

    }
