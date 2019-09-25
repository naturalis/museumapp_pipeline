<?php

    $jobQueuePath = isset($_ENV["JOB_QUEUE_PATH"]) ? $_ENV["JOB_QUEUE_PATH"] : null;

    // include_once('auth.php');
    include_once('class.baseClass.php');
    include_once('class.pipelineJobQueuer.php');

    $s = new PipelineJobQueuer;

    $s->setJobQueuePath( $jobQueuePath );
    $prevQueuedJobs = $s->findEarlierJobs();

    echo json_encode($prevQueuedJobs);
