<?php

    $managementDataDbPath = 
        isset($_ENV["MANAGEMENT_DATA_DB_PATH"]) ? $_ENV["MANAGEMENT_DATA_DB_PATH"] : '/data/management_data/management_data.db';

    include_once("auth.php");
    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');

    $d = new PipelineData;

    $d->setSQLitePath( "management", $managementDataDbPath );

    if (isset($_GET) && isset($_GET["type"]))
    {
        if ($_GET["type"]=="nw-dekking")
        {
            header('Content-disposition: attachment; filename=natuurwijzer-dekking.json');
            header('Content-type: application/json');
            echo $d->getManagementData($_GET["type"]);
        }
    }
