<?php

    if (PHP_SAPI !== 'cli')
    {
        echo "this program must be run from the command line\n";
        exit(0);
    }
      
    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : 'mysql';
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : 'root';
    $db["pass"] = isset($_ENV["MYSQL_ROOT_PASSWORD"]) ? $_ENV["MYSQL_ROOT_PASSWORD"] : 'root';
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : 'reaper';

    $imgSelectorDbPath = 
        isset($_ENV["IMAGE_SELECTOR_DB_PATH"]) ? $_ENV["IMAGE_SELECTOR_DB_PATH"] : '/data/image_selector/medialib_url_chooser.db';

    $imgSquaresDbPath = 
        isset($_ENV["IMAGE_SQUARES_DB_PATH"]) ? $_ENV["IMAGE_SQUARES_DB_PATH"] : '/data/image_squares/square_images.db';

    $documentHashesDbPath = 
        isset($_ENV["DOCUMENT_HASHES_DB_PATH"]) ? $_ENV["DOCUMENT_HASHES_DB_PATH"] : '/data/document_hashes/document_hashes.db';

    $managementDataDbPath = 
        isset($_ENV["MANAGEMENT_DATA_DB_PATH"]) ? $_ENV["MANAGEMENT_DATA_DB_PATH"] : '/data/management_data/management_data.db';

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : '/data/documents/preview/';
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : '/data/documents/publish/';
    $messageQueuePath = isset($_ENV["MESSAGE_QUEUE_PATH"]) ? $_ENV["MESSAGE_QUEUE_PATH"] : '/data/queue/';

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
    $d->setSquaredImagePlaceholderURL( "http://145.136.242.65:8080/stubs/placeholder.jpg" );
    $d->setSquaredImageURLRoot( "http://145.136.242.65:8080/squared_images/" );
    $d->setLeenobjectImageURLRoot( "http://145.136.242.65:8080/leenobject_images/" );

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
    $d->makeTaxonList();
    $d->addTaxonomyToTL();
    $d->addObjectDataToTL();
    $d->saveTaxonList();

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
