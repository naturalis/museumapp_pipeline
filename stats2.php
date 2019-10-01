<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : 'mysql';
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : 'root';
    $db["pass"] = isset($_ENV["MYSQL_ROOT_PASSWORD"]) ? $_ENV["MYSQL_ROOT_PASSWORD"] : 'root';
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : 'reaper';

    $managementDataDbPath = 
        (isset($_ENV["MANAGEMENT_DATA_PATH"]) ? $_ENV["MANAGEMENT_DATA_PATH"] : null) .
        (isset($_ENV["MANAGEMENT_DATA_DB"]) ? $_ENV["MANAGEMENT_DATA_DB"] : null);


    include_once("auth.php");
    include_once('class.baseClass.php');
    include_once('class.pipelineStats.php');
    include_once('class.pipelineData.php');

    $d = new PipelineStats;

    $d->setDatabaseCredentials( $db );
    $d->init();
    $d->setSQLitePath( "management", $managementDataDbPath );

    $data = $d->getTaxa();

    $p = new PipelineData;
    $settings = $p->getArticleSettings();

?>

<html>
<head>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous">
</script>

<script>
function clearFinder()
{
    $('#finder').val("");
    $('#finder').trigger("onkeyup");
}

function doFinder()
{
    var x=$('#finder').val().toLowerCase();

    if (x.length==0)
    {
        $('tr.finder').toggle(true);
    }
    else
    {
        $('tr.finder').toggle(false);
        $('tr.finder[data-finder*="'+x+'"]').toggle(true);
    }
}

function doFilter()
{
    if ($('#filter').prop('checked')==false)
    {
        $('tr.finder').toggle(true);
    }
    else
    {
        $('tr.finder').toggle(false);
        $('tr.finder[data-objects=0]').toggle(true);
    }
}
</script>

<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans" />
<link rel="stylesheet" type="text/css" href="css/main.css" />

</head>
<style type="text/css">
table tr td {
    vertical-align: top;
}    
table tr td.header {
    background-color: #ddd;
    height: 40px !important;
    vertical-align: middle;
}
table tr td.divider {
    height: 25px;
    border-bottom: 1px solid #eee;
}
td.taxon {
    width: 350px;
}
td.origin {
    width: 200px;
    text-align: right;
}
</style>
<body>
    <h3>Natuurwijzerleerobjecten</h3>

<?php

    include_once("_menu.php");

?>

    <span>
        logica:
        <ul>
            <li>maximaal <?php echo $settings["maxTaxonArticles"]; ?> artikelen gematcht op taxon of synoniem;</li>
            <li>als die er niet of deels zijn, aangevuld met maximaal <?php echo $settings["maxClassificationArticles"]; ?> artikelen gematcht op classificatie;</li>
            <li>uiteindelijk uitgevuld tot totaal maximaal <?php echo $settings["maxTotalArticles"]; ?> met artikelen gematcht op zaal,</li>
            <li>zaal-matches worden alleen gemaakt voor de zalen: <?php echo implode("; ",$settings["roomsToMatchLinksOn"]); ?></li>
        </ul>
    </span>
    
<?php

    echo "<table class=main>
        <tr>
            <td colspan=2 class=\"header divider\">
                taxa 
                <input type=text id=finder onkeyup=\"doFinder()\"/>
                <span style=\"cursor:pointer\" onclick=\"clearFinder()\">x</span>
            </td>
            <td class=\"header divider\">
                leerobjecten
                (<input type=checkbox id=filter onchange=\"doFilter()\"/><label for=filter> toon alleen soorten zonder</label>)
            </td>
        </tr>","\n";

    foreach ($data as $key => $val)
    {
        echo
            "<tr class=\"finder\" data-finder=\"",strtolower($val["taxon"]), "\" data-objects=\"". count((array)$val["links"])."\">
                <td>".$key."</td>
                <td class=taxon>", $val["taxon"], "</td>
                <td class=links>
                    <table>";

                        foreach ((array)$val["links"] as $link)
                        {
                            echo "<tr>
                                    <td class=\"origin links\">
                                        ", $link["_link_origin"], ":
                                    </td>
                                    <td class=links>
                                        <a href=\"".$link["_full_url"]."\" target=_new>", $link["title"], "</a>
                                    </td>
                                </tr>","\n";
                        }

                    echo "</table>
                </td>
            </tr>
            <tr class=\"finder\" data-finder=\"",strtolower($val["taxon"]), "\" data-objects=\"". count((array)$val["links"])."\">
                <td colspan=4><hr/></td>
            </tr>","\n";


    }

    echo "</table>";

?>
</body>
</html>