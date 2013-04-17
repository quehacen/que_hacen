<?php

include_once "quehacenlib.php";

// connect
$m = new MongoClient();

// select a database
$db = $m->dipusPrueba;

// select a collection (analogous to a relational database's table)
$htmlColl= $db->htmlCongreso;

// find everything in the collection
$cursor = $htmlColl->find();
echo "Número de elementos: ".$htmlColl->count()."\n";
foreach ($cursor as $elem) { 
    echo "Tipo html: ".gettype($elem["html"])."\n";
    $shtml=getSimpleHTMLCongreso($elem["url"]);
    echo "Tipo shtml: ".gettype($shtml)."\n";
}

echo "Tipo html: ".gettype($cursor[0]["html"])."\n";
$shtml=getSimpleHTMLCongreso($cursor[0]["url"]);
echo "Tipo shtml: ".gettype($shtml)."\n";


?>
