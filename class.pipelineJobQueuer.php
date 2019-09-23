<?php

    class PipelineJobQueuer {

        private $source;
        private $job;
        private $sources = [
            "tentoonstelling",
            "crs",
            "brahms",
            "iucn",
            "nba",
            "natuurwijzer",
            "topstukken",
            "ttik",
            "image_squares",
            "leenobjecten",
            "favourites",
            "taxa_no_objects",
            "maps"
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

        public function setJob( $job )
        {
            $this->job=$job;
        }

        public function setJobQueuePath( $path )
        {
            $this->queuePath=$path;
        }

        public function setPublishPath( $path )
        {
            $this->publishPath=$path;
        }

        public function queueRefreshJob()
        {
            if (is_null($this->source))
            {
                throw new Exception("unknown source", 1);
            }

            if (!in_array($this->source, $this->sources))
            {
                throw new Exception("unknown source: $this->source", 1);
            }

            if (!$this->_findEarlierRefreshJob())
            {
                $job = "refresh-". $this->source . "-". uniqid();
                $this->jobfilename = $this->queuePath . $job;
                if (!file_put_contents($this->jobfilename,json_encode([ "action" => "refresh", "source" => $this->source, "job" => $job ])))
                {
                    throw new Exception(sprintf("couldn't create job file for %s",$this->source), 1);
                }
                else
                {
                    chmod($this->jobfilename,0777);
                }
            }
            else
            {
                throw new Exception("earlier job file exists", 1);
            }
        }

        public function deleteRefreshJob()
        {
            if (is_null($this->source))
            {
                throw new Exception("unknown source", 1);
            }

            if (!in_array($this->source, $this->sources))
            {
                throw new Exception("unknown source: $this->source", 1);
            }

            $this->jobfilename = $this->queuePath . $this->job;

            if (!file_exists($this->jobfilename))
            {
                throw new Exception(sprintf("couldn't find job file %s",$this->job), 1);
            }
            else
            {
                $file = json_decode(file_get_contents($this->jobfilename),true);

                if ($file["action"]=="refresh")
                {
                    unlink($this->jobfilename);
                }
                else
                {
                    throw new Exception(sprintf("can't delete job (status %s)",$file["action"]), 1);
                }
            }
        }

        public function findEarlierJobs()
        {
            $files = glob($this->queuePath . "refresh-" . '*');
            $d=[];
            foreach ($files as $val)
            {
                $d[] = json_decode(file_get_contents($val),true);
            }
            return $d;
        }

        public function queuePublishJob()
        {
            if (is_null($this->publishPath))
            {
                throw new Exception("publish path not set", 1);                        
            }                    

            $this->_deleteEarlierPublishJobs();

            $this->jobfilename = $this->queuePath . "publish-". uniqid();

            if (!file_put_contents($this->jobfilename,json_encode([ "action" => "publish", "source" => $this->publishPath ])))
            {
                throw new Exception("couldn't create publishing job file", 1);                        
            }
            else
            {
                chmod($this->jobfilename,0777);
            }                    
        }

        private function _findEarlierRefreshJob()
        {
            $files = glob($this->queuePath . "refresh-". $this->source . '*');
            return !empty($files);
        }

        private function _deleteEarlierPublishJobs()
        {
            $files = glob($this->queuePath . "publish-*");
            foreach ($files as $val)
            {
                unlink($val);
            }
        }
    }
