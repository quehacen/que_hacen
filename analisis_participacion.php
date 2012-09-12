<?php
  ini_set('display_errors', '1');

  if(is_file('db.php')) {
    include_once('db.php');
  } else {
    include_once('db_sample.php');
  }

  $my->real_query("SELECT id, xml FROM votacion");
  $res = $my->use_result();

  $diputado = array();
  while($row = $res->fetch_assoc()) {
    $r = new SimpleXMLElement($row['xml']);

    // A veces no hay votos a favor o en contra, solo "asentimiento"
    if($r->Totales->AFavor) {
      foreach($r->Votaciones->Votacion AS $v) {
        $nom = ''.$v->Diputado;
        $voto = ''.$v->Voto;
        $asiento = ''.$v->Asiento;

        if(!isset($diputado[$nom])) {
          $diputado[$nom] = array(
            'total' => 0,
            'Sí' => 0,
            'No' => 0,
            'Abstención' => 0,
            'No vota' => 0
          );
        }

        $diputado[$nom]['asientos'][$asiento] = true;
        $diputado[$nom]['total']++;
        $diputado[$nom][$voto]++;
      }
    }
  }
?>
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Análisis de participación de cada diputado</title>
        <script src="js/d3.v2.min.js"></script>
        <style type="text/css">
          svg {
            shape-rendering: crispEdges;
            background-color:#ADF;
          }
        </style>
    </head>
    <body>

      <div id="viz"></div>

      <script type="text/javascript">
var data = [
<?php
  ksort($diputado);
  foreach($diputado AS $nom => $info) {
    print sprintf("  { nombre:'%s', asientos:'%s', total:%s, si:%s, no:%s, abs:%s, novota:%s },\n",
      $nom,
      implode(', ', array_keys($info['asientos'])),
      $info['total'],
      $info['Sí'],
      $info['No'],
      $info['Abstención'],
      $info['No vota']);
  }
?>
];

var r = 800;

var vis = d3.select("#viz")
  .append("svg:svg")
  .attr("width", r)
  .attr("height", r);

var node = barDemo
  .selectAll("svg:rect")
  .data(data)
  .enter();

/*
  ... PENDIENTE
node.append("title")
  .text(function(d) { return d.total + ": " + format(d.si); });

node.append("circle")
  .attr("r", function(d) { return d.r; })
  .style("fill", function(d) { return fill(d.nombre); });

node.append("text")
  .attr("text-anchor", "middle")
  .attr("dy", ".3em")
  .text(function(d) { return d.nombre.substring(0, d.r / 3); });
*/
      </script>

    </body>
</html>


