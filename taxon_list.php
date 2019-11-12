<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : null;
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : null;
    $db["pass"] = isset($_ENV["MYSQL_PASSWORD"]) ? $_ENV["MYSQL_PASSWORD"] : null;
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : null;

    include_once('auth.php');
    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');

    $d = new PipelineData;

    $d->setDatabaseCredentials( $db );
    $d->connectDatabase();
    $d->setMasterList();
    $d->makeTaxonList();

    $variable = $d->getTaxonList();
    foreach ($variable["data"] as $key => $value) {
        $taxonList[]=$key;
    }

    echo json_encode($taxonList);
