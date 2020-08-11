    <?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : 'mysql';
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : 'root';
    $db["pass"] = isset($_ENV["MYSQL_ROOT_PASSWORD"]) ? $_ENV["MYSQL_ROOT_PASSWORD"] : 'root';
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : 'reaper';

    $imgSquaresDbPath = isset($_ENV["IMAGE_SQUARES_DB_PATH"]) ? $_ENV["IMAGE_SQUARES_DB_PATH"] : null;
    $managementDataDbPath = 
        (isset($_ENV["MANAGEMENT_DATA_PATH"]) ? $_ENV["MANAGEMENT_DATA_PATH"] : null) .
        (isset($_ENV["MANAGEMENT_DATA_DB"]) ? $_ENV["MANAGEMENT_DATA_DB"] : null);

    include_once('auth.php');
    include_once('class.baseClass.php');
    include_once('class.pipelineStats.php');
    include_once('class.pipelineData.php');

    $d = new PipelineStats;

    $d->setSQLitePath( "management", $managementDataDbPath );
    $d->setDatabaseCredentials( $db );
    $d->init();

    $data1 = $d->getObjectsPerRoom();
    // foreach (json_decode($d->getManagementData( "ttik-content-dekking" ),true) as $val)
    // {
    //     $data2[$val["status"]] = isset($data2[$val["status"]]) ? $data2[$val["status"]]+1 : 1;
    // }

    $taxa_per_room = $d->getManagementData( "taxa_per_room" );
    $taxa_overall = $d->getManagementData( "taxa_overall" );

    $e = new PipelineData;
    $e->setSQLitePath( "squares", $imgSquaresDbPath );
    $e->setImageSquares();
    // $data3 = $e->getImageSquares();

?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans" />
<link rel="stylesheet" type="text/css" href="css/main.css" />

<style>
table.zaal tr td {
    width: 200px;
}
table tr.header td, table tr td.header {
    font-weight: bold;
}
table tr td:not(:first-child) {
    text-align: right;
}
table tr.data:hover {
    background-color: #ededed;
}
table tr.sum td {
    border-top: 1px solid #dedede;
    padding-top: 2px;
}
.zaal2 td:not(:first-child) {
    width: 150px;
}
</style>
<body>

    <h3>Overzicht dekkingsgraad content</h3>
    
<?php

    include_once("_menu.php");

    echo "<table class='zaal'>";

    echo sprintf("<tr class='header'><td></td><td>%s</td><td>%s</td></tr>\n","objecten","objecten");
    echo sprintf("<tr class='header'><td>%s</td><td>%s</td><td>%s</td></tr>\n","zaal","met afbeelding(en)","zonder afbeelding");

    $a=0;
    $b=0;

    foreach ($data1 as $key => $val)
    {
        echo sprintf("<tr class='data'><td>%s</td><td>%s (%02d%%)</td><td>%s (% 2d%%)</td></tr>\n",
            $val["zaal"],
            $val["unique_unitids_with_image_URL"],
            
            round($val["unique_unitids_with_image_URL"] / ($val["unique_unitids_with_image_URL"]+$val["unique_unitids_without_image_URL"]) * 100),

            $val["unique_unitids_without_image_URL"],

            round($val["unique_unitids_without_image_URL"] / ($val["unique_unitids_with_image_URL"]+$val["unique_unitids_without_image_URL"]) * 100)

        );

        $a+=$val["unique_unitids_with_image_URL"];
        $b+=$val["unique_unitids_without_image_URL"];
    }

        echo sprintf("<tr class='sum data'><td>%s</td><td>%s</td><td>%s</td></tr>\n","Totaal",$a,$b);


    echo "</table>";

    echo "<br />";

    // $tot_soorten = $data2["no taxon match"] + $data2["no content"] + $data2["got content"];

    echo "<table>";
    echo sprintf("<tr><td class='header'>%s:</td><td>%s</td></tr>\n","totaal aantal soorten",$taxa_overall["taxon_count"]);
    echo sprintf("<tr><td class='header'>%s:</td><td>%s (% 2d%%)</td></tr>\n","soorten met beschrijving",
        $taxa_overall["with_description"],round(($taxa_overall["with_description"] / $taxa_overall["taxon_count"])*100));
    echo sprintf("<tr><td class='header'>%s:</td><td>%s (% 2d%%)</td></tr>\n","soorten met hoofdafbeeldingen",
        $taxa_overall["with_image"],round(($taxa_overall["with_image"] / $taxa_overall["taxon_count"])*100));
    echo sprintf("<tr><td class='header'>%s:</td><td>%s (% 2d%%)</td></tr>\n","soorten met beide",
        $taxa_overall["with_image_and_description"],round(($taxa_overall["with_image_and_description"] / $taxa_overall["taxon_count"])*100));
    echo "</table>";

    echo "<br />";

    echo "<table class='zaal2'>";
    echo sprintf("<tr class='header'><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n","zaal","soorten","hoofdafbeelding",
        "beschrijving (nl)","beide");

    foreach ($taxa_per_room as $key => $val)
    {
        echo sprintf("<tr class='data'><td>%s</td><td>%s</td><td>%s (% 2d%%)</td><td>%s (% 2d%%)</td><td>%s (% 2d%%)</td></tr>\n",
            $val["room"],
            $val["taxon_count"],
            $val["with_image"],
            round(($val["with_image"] / $val["taxon_count"]) * 100),
            $val["with_description"],
            round(($val["with_description"] / $val["taxon_count"]) * 100),
            $val["with_image_and_description"],
            round(($val["with_image_and_description"] / $val["taxon_count"]) * 100)
        );    
    }

    echo "</table>";

?>

</body>
</html>