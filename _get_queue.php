<?php

    $messageQueuePath = isset($_ENV["MESSAGE_QUEUE_PATH"]) ? $_ENV["MESSAGE_QUEUE_PATH"] : null;

    // include_once('auth.php');
    include_once('class.baseClass.php');
    include_once('class.pipelineJobQueuer.php');

    $s = new PipelineJobQueuer;

    $s->setQueuePath( $messageQueuePath );
    $prevQueuedJobs = $s->findEarlierJobs();

    echo json_encode($prevQueuedJobs);
