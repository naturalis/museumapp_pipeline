<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : 'mysql';
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : 'root';
    $db["pass"] = isset($_ENV["MYSQL_ROOT_PASSWORD"]) ? $_ENV["MYSQL_ROOT_PASSWORD"] : 'root';
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : 'reaper';

    include_once("auth.php");
    include_once('class.baseClass.php');
    include_once('class.pipelineStats.php');

    $d = new PipelineStats;

    $d->setDatabaseCredentials( $db );
    $d->init();
    $d->setNatuurwijzer();
    $articles = $d->getNatuurwijzer();

    usort($articles["data"], function($a,$b)
    {
        return $a["title"] > $b["title"];
    });
?>

<html>
<head>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>
</head>
<style type="text/css">
table tr td {
    vertical-align: top;
}    
table tr:hover {
    background-color: #eee;
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
</style>
<body>

    <h3>Natuurwijzerleerobjecten</h3>

    <table>
<?php

    echo
        "<tr>
            <td class=\"header divider\">#</td>
            <td class=\"header divider\">leerobject</a></td>
            <td class=\"header divider\">gekoppelde taxa</td>
            <td class=\"header divider\">gekoppelde zalen</td>
        </tr>","\n";

    foreach ($articles["data"] as $key => $val)
    {
        echo
            "<tr>
                <td class=divider>", $key, "</td>
                <td class=divider><a href=\"".$val["_full_url"]."\" target=_new>", $val["title"], "</a></td>
                <td class=divider>", implode("<br />",(array)$val["_taxon"]),"</td>
                <td class=divider>", implode("<br />",(array)$val["_exhibition_rooms"]),"</td>
            </tr>","\n";
    }

?>
    </table>
</body>
</html>