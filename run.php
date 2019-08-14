<?php

    if (PHP_SAPI !== 'cli')
    {
        echo "this program must be run from the command line\n";
        exit(0);
    }
      
    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : null;
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : null;
    $db["pass"] = isset($_ENV["MYSQL_PASSWORD"]) ? $_ENV["MYSQL_PASSWORD"] : null;
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : null;

    $imgSelectorDbPath = isset($_ENV["IMAGE_SELECTOR_DB_PATH"]) ? $_ENV["IMAGE_SELECTOR_DB_PATH"] : null;
    $imgSquaresDbPath = isset($_ENV["IMAGE_SQUARES_DB_PATH"]) ? $_ENV["IMAGE_SQUARES_DB_PATH"] : null;
    $documentHashesDbPath = isset($_ENV["DOCUMENT_HASHES_DB_PATH"]) ? $_ENV["DOCUMENT_HASHES_DB_PATH"] : null;
    $managementDataDbPath = isset($_ENV["MANAGEMENT_DATA_DB_PATH"]) ? $_ENV["MANAGEMENT_DATA_DB_PATH"] : null;

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : null;
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : null;
    $messageQueuePath = isset($_ENV["MESSAGE_QUEUE_PATH"]) ? $_ENV["MESSAGE_QUEUE_PATH"] : null;

    $urlImagePlaceholder = isset($_ENV["URL_PLACEHOLDER_IMAGE"]) ? $_ENV["URL_PLACEHOLDER_IMAGE"] : null;
    $urlObjectPlaceholder = isset($_ENV["URL_PLACEHOLDER_OBJECT_IMAGE"]) ? $_ENV["URL_PLACEHOLDER_OBJECT_IMAGE"] : null;
    $urlSquaredImageRoot = isset($_ENV["URL_SQUARES_IMAGE_ROOT"]) ? $_ENV["URL_SQUARES_IMAGE_ROOT"] : null;
    $urlLeenImageRoot = isset($_ENV["URL_LEENOBJECTEN_IMAGE_ROOT"]) ? $_ENV["URL_LEENOBJECTEN_IMAGE_ROOT"] : null;

    $generateFiles=true;
    
    if (getopt("",["generate-files:"]))
    {
        $generateFiles = getopt("",["generate-files:"])["generate-files"]!='0';
        // to just create the taxon list: php run.php --generate-files=0 
    }

    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');

    set_time_limit(3000);

    $d = new PipelineData;

    $d->setDatabaseCredentials( $db );
    $d->setJsonPath( "preview", $jsonPreviewPath );
    $d->setJsonPath( "publish", $jsonPublishPath );
    $d->setSQLitePath( "selector", $imgSelectorDbPath );
    $d->setSQLitePath( "squares", $imgSquaresDbPath );
    $d->setSQLitePath( "management", $managementDataDbPath );

    $d->setSquaredImagePlaceholderURL( $urlImagePlaceholder );
    $d->setObjectImagePlaceholderURL( $urlObjectPlaceholder );
    $d->setSquaredImageURLRoot( $urlSquaredImageRoot );
    $d->setLeenobjectImageURLRoot( $urlLeenImageRoot );

    $d->init();

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
    $d->addCRSToTL();
    $d->addBrahmsToTL();
    $d->addIUCNToTL();

    if ($generateFiles)
    {
        $d->addCRSToTL();
        $d->addBrahmsToTL();
        $d->addIUCNToTL();
        $d->resolveExhibitionRooms();
        $d->addTTIKTextsToTL();
        $d->addNatuurwijzerTextsToTL();
        $d->addTopstukkenTextsToTL();
        $d->makeLinksSelection();
        $d->effectuateImageSelection();
        $d->addLeenobjectImages();
        $d->addImageSquares();
        $d->addFavourites();
        $d->generateJsonDocuments();
        $d->cleanUp();
    }
    else
    {
        echo "skipping generating files\n";
    }

    foreach ($d->getMessages() as $val)
    {
        echo $val["timestamp"], " - ", $val["source"], " - ", $val["message"],"\n";
    }

    echo "done\n";

    // $masterList = $d->getMasterList();
    // $crs = $d->getCRS();
    // $iucn = $d->getIUCN();
    // $nba = $d->getNBA();
    // $brahms = $d->getBrahms();
    // $natuurwijzer = $d->getNatuurwijzer();
    // $topstukken = $d->getTopstukken();
    // $ttik = $d->getTtik();
    // $imageSelections = $d->getImageSelection();
    // $imageSquares = $d->getImageSquares();
    // $taxonList = $d->getTaxonList();
    // $leenObjecten = $d->getLeenObjecten();
    // $favourites = $d->getFavourites();
    // // $brahmsList = $d->getBrahmsUnitIDsFromObjectData();
