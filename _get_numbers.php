<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : null;
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : null;
    $db["pass"] = isset($_ENV["MYSQL_PASSWORD"]) ? $_ENV["MYSQL_PASSWORD"] : null;
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : null;

    $imgSelectorDbPath = isset($_ENV["IMAGE_SELECTOR_DB_PATH"]) ? $_ENV["IMAGE_SELECTOR_DB_PATH"] : null;
    $imgSquaresDbPath = isset($_ENV["IMAGE_SQUARES_DB_PATH"]) ? $_ENV["IMAGE_SQUARES_DB_PATH"] : null;
    $managementDataDbPath = isset($_ENV["MANAGEMENT_DATA_DB_PATH"]) ? $_ENV["MANAGEMENT_DATA_DB_PATH"] : null;

    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');


    $d = new PipelineData;

    $d->setDatabaseCredentials( $db );

    $d->setSQLitePath( "selector", $imgSelectorDbPath );
    $d->setSQLitePath( "squares", $imgSquaresDbPath );
    $d->setSQLitePath( "management", $managementDataDbPath );

    $d->connectDatabase();

    $d->setMasterList();
    $d->setCRS();
    $d->setBrahms();
    $d->setIUCN();
    $d->setNatuurwijzer();
    $d->setTopstukken();
    $d->setTTIK();
    $d->setNBA();
    $d->setExhibitionRooms();
    $d->setImageSelection();
    $d->setImageSquares();
    $d->setLeenObjecten();
    $d->setFavourites();
    $d->setObjectlessTaxa();
    $d->setMaps();
    $d->makeTaxonList();
    $d->addTaxonomyToTL();
    $d->addObjectDataToTL();
    $d->saveTaxonList();

    $masterList = $d->getMasterList();
    $crs = $d->getCRS();
    $iucn = $d->getIUCN();
    $nba = $d->getNBA();
    $brahms = $d->getBrahms();
    $natuurwijzer = $d->getNatuurwijzer();
    $topstukken = $d->getTopstukken();
    $ttik = $d->getTtik();
    $imageSelections = $d->getImageSelection();
    $imageSquares = $d->getImageSquares();
    $taxonList = $d->getTaxonList();
    $leenObjecten = $d->getLeenObjecten();
    $favourites = $d->getFavourites();
    $objectlessTaxa = $d->getObjectlessTaxa();
    $maps = $d->getMaps();

    echo
        json_encode( [
            'tentoonstelling' => [ "count" => $masterList["count"], "date" => $masterList["harvest_date"] ],
            'crs' => [ "count" => $crs["count"], "date" => $crs["harvest_date"] ],
            'iucn' => [ "count" => $iucn["count"], "date" => $iucn["harvest_date"] ],
            'nba' => [ "count" => $nba["count"], "date" => $nba["harvest_date"] ],
            'brahms' => [ "count" => $brahms["count"], "date" => $brahms["harvest_date"] ],
            'natuurwijzer' => [ "count" => $natuurwijzer["count"], "date" => $natuurwijzer["harvest_date"] ],
            'topstukken' => [ "count" => $topstukken["count"], "date" => $topstukken["harvest_date"] ],
            'ttik' => [ "count" => $ttik["count"], "date" => $ttik["harvest_date"] ],
            'image_selector' => [ "count" => $imageSelections["count"], "date" => $imageSelections["harvest_date"] ],
            'image_squares' => [ "count" => $imageSquares["count"], "date" => $imageSquares["harvest_date"] ],
            'taxonList' => [ "count" => $taxonList["count"], "date" => $taxonList["harvest_date"] ],
            'leenobjecten' => [ "count" => $leenObjecten["count"], "date" => $leenObjecten["harvest_date"] ],
            'favourites' => [ "count" => $favourites["count"], "date" => $favourites["harvest_date"] ],
            'taxa_no_objects' => [ "count" => $objectlessTaxa["count"], "date" => $objectlessTaxa["harvest_date"] ],
            'maps' => [ "count" => $maps["count"], "date" => $maps["harvest_date"] ],
        ] );

