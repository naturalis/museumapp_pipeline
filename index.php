<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : null;
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : null;
    $db["pass"] = isset($_ENV["MYSQL_PASSWORD"]) ? $_ENV["MYSQL_PASSWORD"] : null;
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : null;

    $imgSelectorDbPath = isset($_ENV["IMAGE_SELECTOR_DB_PATH"]) ? $_ENV["IMAGE_SELECTOR_DB_PATH"] : null;
    $imgSquaresDbPath = isset($_ENV["IMAGE_SQUARES_DB_PATH"]) ? $_ENV["IMAGE_SQUARES_DB_PATH"] : null;
    $documentHashesDbPath =
        (isset($_ENV["DOCUMENT_HASHES_PATH"]) ? $_ENV["DOCUMENT_HASHES_PATH"] : null) .
        (isset($_ENV["DOCUMENT_HASHES_DB"]) ? $_ENV["DOCUMENT_HASHES_DB"] : null);
    $managementDataDbPath = 
        (isset($_ENV["MANAGEMENT_DATA_PATH"]) ? $_ENV["MANAGEMENT_DATA_PATH"] : null) .
        (isset($_ENV["MANAGEMENT_DATA_DB"]) ? $_ENV["MANAGEMENT_DATA_DB"] : null);

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : null;
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : null;
    $jobQueuePath = isset($_ENV["JOB_QUEUE_PATH"]) ? $_ENV["JOB_QUEUE_PATH"] : null;

    $urlImagePlaceholder = isset($_ENV["URL_PLACEHOLDER_IMAGE"]) ? $_ENV["URL_PLACEHOLDER_IMAGE"] : null;
    $urlObjectPlaceholder = isset($_ENV["URL_PLACEHOLDER_OBJECT_IMAGE"]) ? $_ENV["URL_PLACEHOLDER_OBJECT_IMAGE"] : null;
    $urlSquaredImageRoot = isset($_ENV["URL_SQUARES_IMAGE_ROOT"]) ? $_ENV["URL_SQUARES_IMAGE_ROOT"] : null;
    $urlLeenImageRoot = isset($_ENV["URL_LEENOBJECTEN_IMAGE_ROOT"]) ? $_ENV["URL_LEENOBJECTEN_IMAGE_ROOT"] : null;

    include_once('auth.php');
    include_once('class.baseClass.php');
    include_once('class.pipelineData.php');
    include_once('class.dataBrowser.php');
    include_once('class.pipelineJobQueuer.php');

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

    $b = new DataBrowser;

    $b->setJsonPath( "preview", $jsonPreviewPath );
    $b->setJsonPath( "publish", $jsonPublishPath );
    $b->setLocalSQLitePath( $documentHashesDbPath );

    $s = new PipelineJobQueuer;

    $s->setPublishPath( $jsonPublishPath );
    $s->setJobQueuePath( $jobQueuePath );

    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="refresh" && isset($_POST["source"]))
    {
        $s->setSource( $_POST["source"] );
        
        try
        {
            $s->queueRefreshJob();
        } 
        catch (Exception $e)
        {
            $queueMessage = [ "source" => $_POST["source"], "message" => $e->getMessage(), "success" => 0  ];
        }
    }
    else
    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="publish")
    {
        try
        {
            $b->publishPreviewFiles();
            // $s->queuePublishJob();
            $publishMessage = [ "message" => "bestanden klaar gezet voor publiceren", "success" => 1 ];
            $d->storeEventTimestamp( "publish" );
        }
        catch (Exception $e)
        {
            $publishMessage = [ "message" => $e->getMessage(), "success" => 0  ];
        }

    }
    else
    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="cancel-refresh")
    {
        $s->setSource( $_POST["source"] );
        $s->setJob( $_POST["job"] );
        
        try
        {
            $s->deleteRefreshJob();
        }
        catch (Exception $e)
        {
            $queueMessage = [ "source" => $_POST["source"], "message" => $e->getMessage(), "success" => 0  ];
        }
    }
    else
    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="delete")
    {
        $b->deletePreviewFiles();
    }
    else
    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="generate")
    {

        set_time_limit(300);

        $d->addCRSImagesToTL();
        $d->addBrahmsToTL();
        $d->addIUCNToTL();
        $d->resolveExhibitionRooms();
        $d->addTTIKTextsToTL();
        $d->addNatuurwijzerTextsToTL();
        $d->addTopstukkenTextsToTL();
        $d->makeLinksSelection();
        $d->addLeenobjectImages();
        $d->effectuateImageSelection();
        $d->addImageSquares();
        $d->addFavourites();
        $d->generateJsonDocuments();
        $d->cleanUp();

        $messages = $d->getMessages();

        $d->storeEventTimestamp( "generate" );
    }

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

    $fPreview = $b->getFileLinks( "preview" );
    // $prevQueuedJobs = $s->findEarlierJobs();
    $events = $d->getEventTimestamps();

?>
<html>
<head>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>

<script type="text/javascript" src="js/main.js"></script>
<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans" />
<style>
body {
    font-family: Open Sans;
}
.title {
    display: inline-block;
    padding: 0;
    margin: 0 12px 0 2px;
}
a.refresh {
    font-size: 10px;
}
#numbers table tr:hover {
    background-color: #eee;
}
#numbers table tr td:nth-child(2) {
    text-align: right;
}
div {
    margin-bottom: 10px;
}
.clickable {
    cursor: pointer;
}
.queued {
    color: green;
    font-size: 10px;
}
tr td {
    height: 30px;
    padding: 0 5px 0 5px;
}
tr.titles td {
    font-weight: bold;
    background-color: #eee;
}
td.harvest, td.explain {
    font-size: 10px;
}
div.section {
    display:none;
}
span.section-head {
    display:block;
    font-weight: bold;
    cursor: pointer;
}
hr {
    width: 350px;
    text-align: left;
}
span.refresh-legend {
    display: block;
    font-size: 10px;
}
span.icon {
    display: inline-block;
    font-size: 12px;
    width: 20px;
}
</style>
</head>
<body>
    <div>
        <h2 class='title'>Museumapp Pipeline</h2><a class="refresh" href="index.php">reload</a>
    </div>

<?php

    include_once("_menu.php");

?>
    <div id="numbers">
        <table>
<?php

    echo '<tr class="titles">
            <td>bron</td>
            <td>#</td>
            <td class="harvest">harvest date</td>
            <td class="harvest">refresh</td>
            <td class="harvest"></td>
        </tr>',"\n";

    $sources = [
        [ "label" => "Tentoonstellingsobjecten", "var" => $masterList, "refreshable" => true, "automatically_refreshable" => false, "data-source" => "tentoonstelling", "explain" => "unieke objecten in de masterlist; <a href=\"https://docs.google.com/spreadsheets/d/1hUZkP50gziO7fTCDnENJHbI4j-DLVxIjWcgTVUs1WkA/export?format=csv\">download csv</a> (vereist google-login & rechten)"],
        [ "label" => "Taxa", "var" => $taxonList, "explain" => "unieke taxa in de tentoonstellingsobjecten-lijst" ],
        [ "label" => "CRS", "var" => $crs, "data-source" => "crs", "refreshable" => true, "automatically_refreshable" => true, "explain" => "afbeeldingen uit het CRS" ],
        [ "label" => "Brahms", "var" => $brahms, "data-source" => "brahms", "refreshable" => false, "explain" => "afbeeldingen uit Brahms" ],
        [ "label" => "IUCN-status", "var" => $iucn, "refreshable" => true, "automatically_refreshable" => true, "data-source" => "iucn", "explain" => "IUCN statussen (klein aantal soorten heeft meer dan één status)" ],
        [ "label" => "NBA", "var" => $nba, "refreshable" => true, "automatically_refreshable" => true, "data-source" => "nba", "explain" => "NBA-records, één per object" ],
        [ "label" => "Natuurwijzer", "var" => $natuurwijzer, "refreshable" => true, "automatically_refreshable" => true, "data-source" => "natuurwijzer", "explain" => "unieke natuurwijzer-artikelen getagged met zaal en/of taxon" ],
        [ "label" => "Topstukken", "var" => $topstukken, "refreshable" => true, "automatically_refreshable" => true, "data-source" => "topstukken", "explain" => "topstuk-objecten" ],
        [ "label" => "Linnaeus (TTIK)", "var" => $ttik, "refreshable" => true, "automatically_refreshable" => true, "data-source" => "ttik", "explain" => "ttik-records, één per (hoger) taxon" ],
        [ "label" => "Afbeeldingselecties", "var" => $imageSelections, "refreshable" => false, "explain" => "objecten met geordende afbeeldingselecties", "data-source" => "image_selector" ],
        [ "label" => "Gegenereerde vierkanten", "var" => $imageSquares, "data-source" => "image_squares", "refreshable" => true,  "automatically_refreshable" => true, "explain" => "objecten met gegenereerde vierkante 'soortsfoto'" ],
        [ "label" => "Leenobjecten", "var" => $leenObjecten, "data-source" => "leenobjecten", "refreshable" => true, "automatically_refreshable" => false, "explain" => "aantal leenobjecten (hopelijk met afbeeldingen)" ],
        [ "label" => "Favourites", "var" => $favourites, "data-source" => "favourites", "refreshable" => true, "automatically_refreshable" => false, "explain" => "favoriete objecten, default bij leeg zoekscherm" ],
        [ "label" => "Taxa w/o objects", "var" => $objectlessTaxa, "data-source" => "taxa_no_objects", "refreshable" => true, "automatically_refreshable" => false, "explain" => "taxa zonder objecten" ],
        [ "label" => "Maps", "var" => $maps, "data-source" => "maps", "refreshable" => true,  "automatically_refreshable" => false, "explain" => "verspreidingskaarten" ],
    ];

    foreach ($sources as $key => $source)
    {
        echo sprintf(
            '<tr>
                <td>%s:</td>
                <td class="numbers" data-source="%s">%s</td>
                <td class="harvest_date harvest" data-source="%s">%s</td>
                <td class="refresh clickable refresh-trigger" data-source="%s">%s</td>
                <td class="refresh-state clickable queued" data-source="%s" style="display:none">%s</td>
                <td class="harvest">%s</td></tr>'."\n",
            $source["label"],
            $source["data-source"],$source["var"]["count"],
            $source["data-source"],($source["var"]["harvest_date"] ?? ""),
            $source["data-source"],
            ($source["refreshable"] && $source["automatically_refreshable"]) ? '&nbsp;&#128259;' : ($source["refreshable"] ? '(&#128259;)' : ''),
            $source["data-source"],
            "",
            $source["explain"]
        );
    }

    
?>
</table>
    <span class="refresh-legend"><span class="icon">&#128259;</span> volautomatische refresh</span>
    <span class="refresh-legend"><span class="icon">(&#128259;)</span> refresh van handmatig geplaatst databestand</span>
    <span class="refresh-legend"><span class="icon" title="current server time">&#128339;</span> <?php echo date('Y-m-d H:i:s');  ?></span>
    <span class="refresh-legend">generated last: <?php echo $events["generate"]; ?></span>
    <span class="refresh-legend">published last: <?php echo $events["publish"]; ?></span>
</div>
<div>
    <input type="button" value="Genereer nieuwe JSON-bestanden" id="generate_button" onclick="generatePreviewFiles();"> 
    <input type="button" value="Bestanden publiceren" onclick="publishPreviewFiles();">   
    <p>
        Gegenereerde JSON-bestanden: <?php echo $fPreview["total"]; ?> <a href="browse.php">browse</a>
    </p>
</div>
<div id="messages">
<?php

    if (isset($messages))
    {
        echo "<i>meldingen tijdens genereren bestanden:</i><br />";

        $section=null;

        foreach ($messages as $val)
        {
            if (is_null($section) || ($section!=$val["source"]))
            {
                if (!is_null($section))
                {
                    echo "</div>\n";
                }
                $section = $val["source"];
                $js_section = str_replace(" ", "_", $section);
                echo '<span class="section-head '.$js_section.'" onclick="$(\'div.' . $js_section . '\').toggle();">' . $section . '</span>';
                echo '<div class="section '.$js_section.'"><ul>'."\n";
            }
            
            // if ($val["level"]!=BaseClass::DATA_MESSAGE)
            {
                echo '<li class="'.$js_section.'">',$val["message"],"</li>\n";    
            }
        }

        echo "</div>\n";
    }

    if (isset($publishMessage))
    {
        echo "<h3>melding tijdens publiceren:</h3>";
        echo $publishMessage["message"],"\n";
    }

?>
</div>

<form method="post" id="theForm">
    <input type="hidden" id="action" name="action" value="generate">
</form>


</body>
<script>
$( document ).ready(function()
{
    $('.refresh').on('click',function()
    {
        if ($(this).attr('data-source').length==0)
        {
            return;
        }
        queuePipelineSourceRefresh($(this).attr('data-source'));
    })

    $('.refresh-state').on('click',function()
    {
        if ($(this).attr('data-source').length==0)
        {
            return;
        }
        unqueuePipelineSourceRefresh($(this));
    })


    // printPreviousQueuedJobs();
    // printQueueMessage();

    runQueueMonitor();
    setInterval(runQueueMonitor, 5000);

});
</script>
</html>
