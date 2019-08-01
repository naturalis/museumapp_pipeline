<?php

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

    $d->setSquaredImagePlaceholderURL( "http://145.136.242.65:8080/stubs/placeholder.jpg" );
    $d->setObjectImagePlaceholderURL( "http://145.136.242.65:8080/stubs/object-placeholder.jpg" );
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
    $d->addCRSToTL();
    $d->addBrahmsToTL();
    $d->addIUCNToTL();

    $b = new DataBrowser;

    $b->setJsonPath( "preview", $jsonPreviewPath );
    $b->setJsonPath( "publish", $jsonPublishPath );
    $b->setLocalSQLitePath( $documentHashesDbPath );

    $s = new PipelineJobQueuer;

    $s->setPublishPath( $jsonPublishPath );
    $s->setQueuePath( $messageQueuePath );

    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="refresh" && isset($_POST["source"]))
    {
        $s->setSource( $_POST["source"] );
        
        try {
            $s->queueRefreshJob();
            $queueMessage = [ "source" => $_POST["source"], "message" => "refresh-job ingepland", "success" => 1 ];
        } catch (Exception $e) {
            $queueMessage = [ "source" => $_POST["source"], "message" => $e->getMessage(), "success" => 0  ];
        }
    }
    else
    if (isset($_POST) && isset($_POST["action"]) && $_POST["action"]=="publish")
    {
        try {
            $b->publishPreviewFiles();
            $s->queuePublishJob();
            $publishMessage = [ "message" => "bestanden klaar gezet voor publiceren", "success" => 1 ];
        } catch (Exception $e) {
            $publishMessage = [ "message" => $e->getMessage(), "success" => 0  ];
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

        $messages = $d->getMessages();
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

    // $brahmsList = $d->getBrahmsUnitIDsFromObjectData();

    $fPreview = $b->getFileLinks( "preview" );
    $prevQueuedJobs = $s->findEarlierJobs();

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
</style>
</head>
<body>
    <div id="numbers">
        <h2>Museumapp Pipeline</h2>
        <table>
<?php

    echo '<tr class="titles"><td>bron</td><td>#</td><td class="harvest" colspan=2>harvest date</td></tr>',"\n";

    $sources = [
        [ "label" => "Tentoonstellingsobjecten", "var" => $masterList, "refreshable" => true, "data-source" => "masterList", "explain" => "unieke objecten in de masterlist" ],
        [ "label" => "Taxa", "var" => $taxonList, "refreshable" => false, "explain" => "unieke taxa in de masterlist" ],
        [ "label" => "CRS", "var" => $crs, "refreshable" => true, "data-source" => "CRS", "explain" => "afbeeldingen uit het CRS" ],
        [ "label" => "Brahms", "var" => $brahms, "refreshable" => false, "explain" => "afbeeldingen uit Brahms"  ],
        [ "label" => "IUCN-status", "var" => $iucn, "refreshable" => true, "data-source" => "IUCN", "explain" => "IUCN statussen (klein aantal soorten heeft meer dan één status)" ],
        [ "label" => "NBA", "var" => $nba, "refreshable" => true, "data-source" => "NBA", "explain" => "NBA-records, één per object" ],
        [ "label" => "Natuurwijzer", "var" => $natuurwijzer, "refreshable" => true, "data-source" => "natuurwijzer", "explain" => "unieke natuurwijzer-artikelen getagged met zaal en/of taxon" ],
        [ "label" => "Topstukken", "var" => $topstukken, "refreshable" => true, "data-source" => "topstukken", "explain" => "topstuk-objecten" ],
        [ "label" => "TTIK", "var" => $ttik, "refreshable" => true, "data-source" => "ttik", "explain" => "ttik-records, één per (hoger) taxon" ],
        [ "label" => "Afbeeldingselecties", "var" => $imageSelections, "refreshable" => false, "explain" => "objecten met geordende afbeeldingselecties" ],
        [ "label" => "Gegenereerde vierkanten", "var" => $imageSquares, "refreshable" => false, "explain" => "objecten met gegenereerde vierkante 'soortsfoto'" ],
        [ "label" => "Leenobjecten", "var" => $leenObjecten, "refreshable" => false, "explain" => "aantal leenobjecten (hopelijk met afbeeldingen)" ],
        [ "label" => "Favourites", "var" => $favourites, "refreshable" => false, "explain" => "favoriete objecten, default bij leeg zoekscherm" ],
        [ "label" => "Taxa w/o objects", "var" => $objectlessTaxa, "refreshable" => false, "explain" => "taxa zonder objecten" ],
    ];



    foreach ($sources as $key => $source)
    {
        echo sprintf(
//            '<tr><td>%s:</td><td>%s</td><td class="harvest">%s</td>%s</td><td class="harvest">%s</td></tr>'."\n",
            '<tr><td>%s:</td><td>%s</td><td class="harvest">%s</td><td class="harvest">%s</td></tr>'."\n",
            $source["label"],
            $source["var"]["count"],
            ($source["var"]["harvest_date"] ?? ""),
//            (isset($source["data-source"]) ? '<td data-source="' . $source["data-source"] . ' class="clickable refresh">&#128259;' : '<td>'),
            $source["explain"]
        );
    }
    echo '<tr><td colspan="3">&nbsp;</td></tr>',"\n";
    echo '<tr><td>Gegenereerde JSON-bestanden:</td><td>',$fPreview["total"],'</td><td><a target="_files" href="browse.php">browse</a></td></tr>',"\n";
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
<?php
    if (isset($queueMessage))
    {
        echo 'queueMessage = { source : "'.$queueMessage["source"].'" , message : "'.$queueMessage["message"].'", success: '.$queueMessage["success"].'  };';
    }
    if (isset($prevQueuedJobs))
    {
        foreach ($prevQueuedJobs as $val)
        {
            echo "prevQueuedJobs.push('".$val."');\n";
        }   
    }
?>

$( document ).ready(function()
{
    $('.refresh').on('click',function()
    {
        queuePipelineSourceRefresh($(this).attr('data-source'));
    })

    printPreviousQueuedJobs();
    printQueueMessage();

});
</script>
<pre>
<?php

    if (!empty($brahmsList))
    {
        foreach ($brahmsList as $val)
        {
            echo $val,"\n";
        };
    }


    // foreach ($taxonList as $val)
    // {
    //     echo $val["taxon"],"\n";
    // };

?>
</pre>
</html>




