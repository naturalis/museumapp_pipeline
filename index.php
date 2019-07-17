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

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : '/data/documents/preview/';
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : '/data/documents/publish/';
    $messageQueuePath = isset($_ENV["MESSAGE_QUEUE_PATH"]) ? $_ENV["MESSAGE_QUEUE_PATH"] : '/data/queue/';

    include_once("auth.php");
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
    $d->makeTaxonList();
    $d->addTaxonomyToTL();
    $d->addObjectDataToTL();

    $b = new DataBrowser;

    $b->setJsonPath( "preview", $jsonPreviewPath );
    $b->setJsonPath( "publish", $jsonPublishPath );
    $b->setSQLitePath( $documentHashesDbPath );

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
        $d->generateJsonDocuments();

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
td.harvest {
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
        [ "label" => "Tentoonstellingsobjecten", "var" => $masterList, "refreshable" => true, "data-source" => "masterList" ],
        [ "label" => "Taxa", "var" => $taxonList, "refreshable" => false ],
        [ "label" => "CRS", "var" => $crs, "refreshable" => true, "data-source" => "CRS" ],
        [ "label" => "Brahms", "var" => $brahms, "refreshable" => false ],
        [ "label" => "IUCN-status", "var" => $iucn, "refreshable" => true, "data-source" => "IUCN" ],
        [ "label" => "NBA", "var" => $nba, "refreshable" => true, "data-source" => "NBA" ],
        [ "label" => "Natuurwijzer", "var" => $natuurwijzer, "refreshable" => true, "data-source" => "natuurwijzer" ],
        [ "label" => "Topstukken", "var" => $topstukken, "refreshable" => true, "data-source" => "topstukken" ],
        [ "label" => "TTIK", "var" => $ttik, "refreshable" => true, "data-source" => "ttik" ],
        [ "label" => "Afbeeldingselecties", "var" => $imageSelections, "refreshable" => false ],
        [ "label" => "Gegenereerde vierkanten", "var" => $imageSquares, "refreshable" => false ],
        [ "label" => "Leenobjecten", "var" => $leenObjecten, "refreshable" => false ],
    ];

    foreach ($sources as $key => $source)
    {
        echo sprintf(
            '<tr><td>%s:</td><td>%s</td><td class="harvest">%s</td>%s</td></tr>'."\n",
            $source["label"],
            $source["var"]["count"],
            ($source["var"]["harvest_date"] ?? ""),
            (isset($source["data-source"]) ? '<td data-source="' . $source["data-source"] . ' class="clickable refresh">&#128259;' : '<td>')
        );
    }

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


<?php



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
    

    use CRS for full sci name if missing?

    use extra topstuk data for object info

    IUCN maps


*/



