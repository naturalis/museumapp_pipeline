<?php

    $db["host"] = isset($_ENV["MYSQL_HOST"]) ? $_ENV["MYSQL_HOST"] : 'mysql';
    $db["user"] = isset($_ENV["MYSQL_USER"]) ? $_ENV["MYSQL_USER"] : 'root';
    $db["pass"] = isset($_ENV["MYSQL_ROOT_PASSWORD"]) ? $_ENV["MYSQL_ROOT_PASSWORD"] : 'root';
    $db["database"] = isset($_ENV["MYSQL_DATABASE"]) ? $_ENV["MYSQL_DATABASE"] : 'reaper';

    include_once('auth.php');
    include_once('class.baseClass.php');
    include_once('class.ttikData.php');

    $page = $_GET["page"] ?? 0;

    $d = new TtikData;

    $d->setDatabaseCredentials( $db );
    $d->init();
    $d->setPage($page);

    $data = $d->getBatch();

?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans" />
<link rel="stylesheet" type="text/css" href="css/main.css" />

<style>
th {
    text-align: left;
}
td.text, td.text {
    text-align: right;
}
td.text {
    background-color: #b3ffb3;
}
td.text.empty {
    background-color: #ffb3b3;
}
a.navigation {
    display: inline-block;
    text-decoration: none;
    font-size: 0.9em;
}
a.navigation:hover {
    text-decoration: underline;
}
a.navigation.prev,a.navigation.next {
}
a.navigation.current {
    text-decoration: none;
    cursor: default;
    color: black;
    font-weight: bold;
}
a.navigation.current:hover {
    text-decoration: none;
}
a.navigation.page {
    padding-left: 5px;
    padding-right: 5px;
}
div.navigation {
    margin-top:10px;
}
</style>
<body>

    <h3>Overzicht content TTIK</h3>

<?php

    include_once("_menu.php");
?>

    <table>
        <thead>
            <tr>
                <th title="taxon">taxon</th>
                <th class="text" title="lengte Nederlandse beschrijving">NL tekst</th>
                <th class="text" title="lengte Engelse beschrijving">EN tekst</th>
            </tr>
        </thead>
        <tbody>

<?php

    foreach ($data["results"] as $key => $val)
    {
        $nl_text = json_decode($val["nl_text"],true);
        $nl_text = array_filter((array)$nl_text,
            function($a)
            {
                return $a["title"]=="Beschrijving";
            });
        if (count($nl_text)>0)
        {
            $nl_text = $nl_text[0];
        }

        $en_text = json_decode($val["en_text"],true);
        $en_text = array_filter((array)$en_text,
            function($a)
            {
                return $a["title"]=="Beschrijving";
            });
        if (count($en_text)>0)
        {
            $en_text = $en_text[0];
        }

        echo '
            <tr>
                <td class="taxon">' . $val["taxon"] . '</th>
                <td class="text nl' . ($nl_text["verified"]==0 ? ' empty' : '') . '">' .
                    strlen($nl_text["body"]) . 
                '</td>
                <td class="text en' . ($en_text["verified"]==0 ? ' empty' : '') . '">' . 
                    strlen($en_text["body"]) . 
                '</td>
            </tr> 
        ';
    }

?>
        </tbody>
    </table>
    <div class="navigation">
<?php


    if ($page > 0)
    {
        echo '<a class="navigation prev" href="?page=' . ($page-1) .'">previous</a>';
    } 

    for($i=0;$i<ceil($data["totalResults"]/$d->pageSize);$i++) 
    {
        echo ' <a class="navigation page' .($page==$i ? ' current' : '' ). '" href="?page=' . ($i) .'">'. $i .'</a> ';        
    }

    if ($page < ceil($data["totalResults"]/$d->pageSize)-1)
    {
        echo '<a class="navigation next" href="?page=' . ($page+1) .'">next</a>';
    } 

?>
    </div>

</body>
</html>