<?php

    include_once('auth.php');

    if (!isset($_POST) || !isset($_POST["data"]))
    {
        return;
    }

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : null;
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : null;
    $db["pass"] = isset($_ENV["MYSQL_PASSWORD"]) ? $_ENV["MYSQL_PASSWORD"] : null;
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : null;

    $imgSelectorDbPath = isset($_ENV["IMAGE_SELECTOR_DB_PATH"]) ? $_ENV["IMAGE_SELECTOR_DB_PATH"] : null;
    $imgSquaresDbPath = isset($_ENV["IMAGE_SQUARES_DB_PATH"]) ? $_ENV["IMAGE_SQUARES_DB_PATH"] : null;

    include_once('class.baseClass.php');
    include_once('class.brahmsData.php');
    include_once('class.imageSquaresNew.php');
    include_once('class.imageSelector.php');

    if (isset($_POST) && isset($_POST["data"]))
    {

        $n = new BrahmsData;
        $n->setDatabaseCredentials( $db );
        $n->connectDatabase();
        $n->emptyTable();

        $m = new imageSquares;
        $m->setDatabaseCredentials( $db );
        $m->setDatabaseFullPath( $imgSquaresDbPath );
        $m->initialize();

        $s = new imageSelector;
        $s->setDatabaseFullPath( $imgSelectorDbPath );
        $s->initialize();


        $lines=explode("||",$_POST["data"]);

        foreach ($lines as $line)
        {
            if (empty($line))
            {
                continue;
            }

            $values=explode(",", $line);

            $n->insertData($values);

            $url = sprintf("https://medialib.naturalis.nl/file/id/%s/format/large",$values[1]);

            try
            {
                echo $m->saveUnitIDNameAndUrl($values[0],$url), "\n";
                echo $s->saveUnitIDAndUrl($values[0],$url),"\n";
            }
            catch(Exception $e)
            {
                // echo $e->getMessage(), "\n";
            }            
        }
    }





