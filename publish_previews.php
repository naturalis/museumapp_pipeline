<?php

    if (PHP_SAPI !== 'cli')
    {
        echo "this program must be run from the command line\n";
        exit(0);
    }

    $documentHashesDbPath = 
        isset($_ENV["DOCUMENT_HASHES_DB_PATH"]) ? $_ENV["DOCUMENT_HASHES_DB_PATH"] : '/data/document_hashes/document_hashes.db';

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : '/data/documents/preview/';
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : '/data/documents/publish/';

    include_once('class.baseClass.php');
    include_once('class.dataBrowser.php');

    $b = new DataBrowser;

    $b->setJsonPath( "preview", $jsonPreviewPath );
    $b->setJsonPath( "publish", $jsonPublishPath );
    $b->setLocalSQLitePath( $documentHashesDbPath );
    $b->publishPreviewFiles();

    echo "moved files to $jsonPublishPath\n";

