<?php

    $db["host"] = isset($_ENV["DB_HOST"]) ? $_ENV["DB_HOST"] : 'mysql';
    $db["user"] = isset($_ENV["DB_USER"]) ? $_ENV["DB_USER"] : 'root';
    $db["pass"] = isset($_ENV["DB_PASS"]) ? $_ENV["DB_PASS"] : 'root';
    $db["database"] = isset($_ENV["DB_DATABASE"]) ? $_ENV["DB_DATABASE"] : 'reaper';

    $imgSelectorDbPath = 
        isset($_ENV["IMAGE_SELECTOR_DB_PATH"]) ? $_ENV["IMAGE_SELECTOR_DB_PATH"] : '/data/image_selector/medialib_url_chooser.db';

    $imgSquaresDbPath = 
        isset($_ENV["IMAGE_SQUARES_DB_PATH"]) ? $_ENV["IMAGE_SQUARES_DB_PATH"] : '/data/image_squares/square_images.db';

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : '/data/documents/preview/';
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : '/data/documents/publish/';

    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');

    $d = new PipelineData;

    $d->setDatabaseCredentials( $db );

    $d->setJsonPath( "preview", $jsonPreviewPath );
    $d->setJsonPath( "publish", $jsonPublishPath );

    $d->setSQLitePath( "selector", $imgSelectorDbPath );
    $d->setSQLitePath( "squares", $imgSquaresDbPath );
    $d->init();

    $d->setMasterList();
    $d->setCRS();
    $d->setIUCN();
    $d->setNatuurwijzer();
    $d->setTopstukken();
    $d->setTTIK();
    $d->setExhibitionRooms();
    $d->setImageSelection();
    $d->setImageSquares();  // STUB

    $d->makeTaxonList();
    $d->addTaxonomyToTL();
    $d->addObjectDataToTL();
    $d->addCRSToTL();
    $d->addIUCNToTL();

    $d->resolveExhibitionRooms();

    $d->addTTIKTextsToTL();
    $d->addNatuurwijzerTextsToTL();
    $d->addTopstukkenTextsToTL();
    $d->makeLinksSelection();

    $d->effectuateImageSelection();
    // $d->addImageSquares();  // STUB



    $d->generateJsonDocuments();






/*

    // banner image
    if (isset($data["header_image"]))
    {
        $d["header_image"] = $data["header_image"];
    }



    $d->addBrahmsDataToTL(); // STUB
    $d->addNBADataToTL();  // STUB



    checks:
    - taxonlist:
        no nomen
        no FSN
        what elese is mandatory?
    - $this->unknownRooms);
- not saved files
- unlilekely taxon names
    
CHECK NOMEN BIJ EEN ONDERSOORT
MORE NBA-DATA
publishable Zaalnamen
controleer topstuk in publish
    $this->getBrahmsUnitIDsFromObjectData();

    use CRS for full sci name if missing?

    use extra topstuk data for object info

    IUCN maps


*/





/*
    $d->setImageSelection();    
    $d->setImageSquares();
*/






/*

    
    select data
    create documents
    write documents(draft)
    serve draft documents
    reject documents
    publish documents
    set elastic busy
    set documents(state=old)
    import documents(state=published)
    delete documents(state=old)
    set elastic ready

    toggle harvesters

*/