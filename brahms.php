<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : null;
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : null;
    $db["pass"] = isset($_ENV["MYSQL_PASSWORD"]) ? $_ENV["MYSQL_PASSWORD"] : null;
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : null;

    include_once('auth.php');
    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');
    include_once('class.dataBrowser.php');
    include_once('class.pipelineJobQueuer.php');

    $d = new PipelineData;

    $d->setDatabaseCredentials( $db );
    $d->connectDatabase();
    $d->setMasterList();
    $d->makeTaxonList();
    $d->addObjectDataToTL();
    $d->saveTaxonList();
   
    $brahmsList = $d->getBrahmsUnitIDsFromObjectData();

    if (!empty($brahmsList))
    {
        foreach ($brahmsList as $val) echo $val,"\n";
    }
