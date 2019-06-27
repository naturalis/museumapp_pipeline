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

    include_once("auth.php");
    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');
    include_once('class.dataBrowser.php');

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

    $b = new DataBrowser;

    $b->setJsonPath( "preview", $jsonPreviewPath );
    $b->setJsonPath( "publish", $jsonPublishPath );

    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="publish")
    {
        $b->publishPreviewFiles();
    }
    else
    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="delete")
    {
        $b->deletePreviewFiles();
    }
    else
    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="generate")
    {
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

        $messages = $d->getMessages();
    }

    $masterList = $d->getMasterList();
    $crs = $d->getCRS();
    $iucn = $d->getIUCN();
    $natuurwijzer = $d->getNatuurwijzer();
    $topstukken = $d->getTopstukken();
    $ttik = $d->getTtik();
    $imageSelections = $d->getImageSelection();
    $imageSquares = $d->getImageSquares();


    $fPreview = $b->getFileLinks( "preview" );

?>
<html>
<head>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>

<script type="text/javascript" src="js/main.js"></script>

<style>
#numbers table tr:hover {
    background-color: #eee;
}
#numbers table tr td:nth-child(2) {
    text-align: right;
}
div {
    margin-bottom: 10px;
}
</style>
</head>
<body>
    <div id="numbers">
        <table>
<?php

    echo '<tr><td>Masterlijst:</td><td>',count($masterList),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td>CRS:</td><td>',count($crs),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td>IUCN:</td><td>',count($iucn),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td>Natuurwijzer:</td><td>',count($natuurwijzer),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td>Topstukken:</td><td>',count($topstukken),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td>TTIK:</td><td>',count($ttik),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td>Afbeeldingselecties:</td><td>',count($imageSelections),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td>Gegenereerde vierkanten:</td><td>',count($imageSquares),'</td><td class="refresh">&#128259;</td></tr>',"\n";
    echo '<tr><td colspan="3">&nbsp;</td></tr>',"\n";
    echo '<tr><td>Gegenereerde JSON-bestanden:</td><td>',$fPreview["total"],'</td><td><a href="browse.php">browse</a></td></tr>',"\n";
?>
</table>
</div>
<div>
    <input type="button" value="Genereer nieuwe JSON-bestanden" onclick="generatePreviewFiles();"> 
    <input type="button" value="Bestanden publiceren" onclick="publishPreviewFiles();">   
</div>
<div id="messages">
<?php

    if (isset($messages))
    {
        echo "<h3>meldingen tijdens genereren bestanden:</h3>";
        echo "<ul>";
        foreach ($messages as $val)
        {
            echo "<li>",$val["message"]," (",$val["source"],")","<br />\n";
        }
        echo "</ul>";
    }

?>
</div>

<form method="post" id="theForm">
    <input type="hidden" id="action" name="action" value="generate">
</form>


</body>
</html>


<?php

    exit(0);

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