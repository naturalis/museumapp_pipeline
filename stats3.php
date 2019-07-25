<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : 'mysql';
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : 'root';
    $db["pass"] = isset($_ENV["MYSQL_ROOT_PASSWORD"]) ? $_ENV["MYSQL_ROOT_PASSWORD"] : 'root';
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : 'reaper';

    $managementDataDbPath = 
        isset($_ENV["MANAGEMENT_DATA_DB_PATH"]) ? $_ENV["MANAGEMENT_DATA_DB_PATH"] : '/data/management_data/management_data.db';

    include_once("auth.php");
    include_once('class.baseClass.php');
    include_once('class.pipelineStats.php');
    include_once('class.pipelineData.php');

    $d = new PipelineStats;

    $d->setSQLitePath( "management", $managementDataDbPath );
    $data = json_decode($d->getManagementData( "ttik-content-dekking" ),true);
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
        $('tr.finder[data-status!="got content"]').toggle(true);
    }
}
</script>

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

    <h3>Taxa content dekking (TTIK)</h3>
    
<?php

    echo "<table class=main>
        <tr>
            <td colspan=2 class=\"header divider\">
                taxa 
                <input type=text id=finder onkeyup=\"doFinder()\"/>
                <span style=\"cursor:pointer\" onclick=\"clearFinder()\">x</span>
            </td>
            <td class=\"header divider\">
                status
                (<input type=checkbox id=filter onchange=\"doFilter()\"/><label for=filter> toon alleen taxa zonder</label>)
            </td>
        </tr>","\n";

    foreach ($data as $key => $val)
    {
        echo
            "<tr class=\"finder\" data-finder=\"",strtolower($val["taxon"]), "\" data-status=\"". $val["status"]."\">
                <td>".$key."</td>
                <td class=taxon>", $val["taxon"], "</td>
                <td class=taxon>", $val["status"], "</td>
            </tr>","\n";
    }

    echo "</table>";

?>
</body>
</html>