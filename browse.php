<?php

    $jsonPreviewPath = isset($_ENV["JSON_PREVIEW_PATH"]) ? $_ENV["JSON_PREVIEW_PATH"] : '/data/documents/preview/';
    $jsonPublishPath = isset($_ENV["JSON_PUBLISH_PATH"]) ? $_ENV["JSON_PUBLISH_PATH"] : '/data/documents/publish/';

    include_once("auth.php");
    include_once('class.baseClass.php');
    include_once('class.dataBrowser.php');

    $perpage=100;
    $offset=isset($_GET["offset"]) ? $_GET["offset"] : 0;

    $d = new DataBrowser;

    $d->setJsonPath( "preview", $jsonPreviewPath );
    $d->setJsonPath( "publish", $jsonPublishPath );

    $f = $d->getFileLinks( "preview", $offset, $perpage );


    function paginator()
    {
        global $_GET,$f,$perpage;

        if ($_GET["offset"]>0)
        {
            echo sprintf(' <a href="?offset=%s">&nbsp;&lt;&lt;&nbsp;</a> ',0);
            echo sprintf(' <a href="?offset=%s">&nbsp;&lt;&nbsp;</a> ',($_GET["offset"] - $perpage));
        }

        for ($i=0;$i<ceil($f["total"] /  $perpage);$i++ )
        {
            if (($i * $perpage)==$_GET["offset"])
            {
                echo sprintf(' &nbsp;%s&nbsp; ',$i);
            }
            else
            {
                if (abs(($_GET["offset"] - ($i * $perpage))) < (4 * $perpage))
                {
                    echo sprintf(' <a href="?offset=%s">&nbsp;%s&nbsp;</a> ',($i * $perpage),$i);
                }
            }   
        }
        
        if (($_GET["offset"] + $perpage) < $f["total"])
        {
            echo sprintf(' <a href="?offset=%s">&nbsp;&gt;&nbsp;</a> ',($_GET["offset"] + $perpage));
            echo sprintf(' <a href="?offset=%s">&nbsp;&gt;&gt;&nbsp;</a> ',((ceil($f["total"] /  $perpage)-1) * $perpage));
        }

    }


?>
<html>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>
</head>

<script type="text/javascript" src="js/main.js"></script>

<link rel="stylesheet" type="text/css" href="css/jquery.json-viewer.css" media="screen">
<script type="text/javascript" src="js/jquery.json-viewer.js"></script>
<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans" />

<style>
body {
    font-family: Open Sans;
}

.clickable {
    cursor: pointer;
    text-decoration: underline;
}
.clickable:hover {
    color: #33f;
}
div {
    vertical-align: top;    
    margin-bottom:10px;
}
#list, #preview {
    float: left;
    width: 600px;
}
#list {
    font-size: 12px;
}
#preview {
    width: 100%;
    position:fixed;
    top:10px;
    left: 600px;
    z-index:100;
    height: 100%;
    overflow-y: scroll;

}
#paginator {
    margin-top: 25px;
}
#preview .title {
    font-style: italic;
    margin-bottom: 5px;
    display: none;
}
#preview .toggle {
    font-size: 12px;
    margin-bottom: 0px;
    display: none;
}
#json-renderer {
    padding: 5px 0 5px 0;
}
.highlight {
    color:red;
}
#paginator { 
    font-size: 12px;
}
#paginator a {
    display: inline-block;
    width: 30px;
    background: #eee;
    text-decoration: none;
    text-align: center;
}
#paginator a:hover {
    background: #ddf;
}
</style>
<body>
    <div id="header">
<?php
    echo " <h4>Batch datum: ",$f["created"],", ",$f["total"]," documenten</h4>";

?>
    </div>
    <div>
        <input type="button" value="bestanden publiceren" onclick="publishPreviewFiles();">
        <input type="button" value="bestanden verwijderen" onclick="deletePreviewFiles();">
    </div>
    <div>

    </div>

    <div id="paginator">
<?php

    paginator();

?>
    </div>

    <div id="main">
        <div id="list">
            <ul>
<?php

    foreach ($f["data"] as $val)
    {
        echo '<li><span class="clickable" data-filename="'.$val["filename"].'" onclick="loadPreview(this);">',$val["filename"],"</span></li>\n";
    }

?>
            </ul>
        </div>

        <div id="preview" class="fixedElement">
            <div class="title"></div>
            <div class="clickable toggle" onclick="toggleCollapse()">expand all</div>
            <pre id="json-renderer">(preview)</pre>
        </div>
</div>

<br clear="all" />

<div id="paginator">
    pagina: 
<?php

    paginator();

?>
</div>


<?php
    include_once("_menu.php");
?>

<style>
div.menu {
    position:relative;
}
div.menu ul {
    padding-inline-start: 0px;
}
div.menu ul li {
    text-align: left;
}
</style>

<form method="post" id="theForm" action="index.php">
    <input type="hidden" id="action" name="action" value="generate">
</form>

<script>
$( document ).ready(function()
{
    openDocumentByQueryKey();
});
</script>

</body>
</html>