<?php

    if (PHP_SAPI !== 'cli')
    {
        echo "this program must be run from the command line\n";
        exit(0);
    }

    $documentHashesDbPath = isset($_ENV["DOCUMENT_HASHES_DB_PATH"]) ? $_ENV["DOCUMENT_HASHES_DB_PATH"] : null;
    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : null;
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : null;

    include_once('class.baseClass.php');
    include_once('class.dataBrowser.php');

    $b = new DataBrowser;

    try {

        $b->setJsonPath( "preview", $jsonPreviewPath );
        $b->setJsonPath( "publish", $jsonPublishPath );
        $b->setLocalSQLitePath( $documentHashesDbPath );

        $b->setState("preview");        

        if ($b->getNumberOfFiles()==0)
        {
            exit(0);
        }

        echo sprintf("moving files from %s to %s\n",$jsonPreviewPath,$jsonPublishPath);

        $b->publishPreviewFiles();

        echo "done\n";
        
    } catch (Exception $e) {
        
        echo sprintf("error: %s\n",$e->getMessage());

    }


