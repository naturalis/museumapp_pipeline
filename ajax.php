<?php

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : '/data/documents/preview/';
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : '/data/documents/publish/';

    include_once('class.baseClass.php');
    include_once('class.dataBrowser.php');

    $file=isset($_POST["file"]) ? $_POST["file"] : null;

    if (!is_null($file))
    {
        $d = new DataBrowser;

        $d->setJsonPath( "preview", $jsonPreviewPath );
        $d->setJsonPath( "publish", $jsonPublishPath );

        try {
            echo $d->getFile( "preview", $file );
        } catch (Exception $e) {
            echo json_encode( [ "error" => $e->getMessage() ] );
        }
       
    }
