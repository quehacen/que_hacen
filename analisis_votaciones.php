<?php
  ini_set('display_errors', '1');

  if(is_file('db.php')) {
    include_once('db.php');
  } else {
    include_once('db_sample.php');
  }

  $my->real_query("SELECT id, xml FROM votacion");
  $res = $my->use_result();
?>
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Visualización de las votaciones comparando sí/no/abs/no presente</title>
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
  while($row = $res->fetch_assoc()) {
    $r = new SimpleXMLElement($row['xml']);

    if($r->Totales->AFavor) {
      print sprintf("  { fecha:%s, presentes:%s, si:%s, no:%s, abs:%s },\n",
        $r->Informacion->Fecha,
        $r->Totales->Presentes,
        $r->Totales->AFavor,
        $r->Totales->EnContra,
        $r->Totales->Abstenciones
      );
    } else {
      print sprintf(" // Asentimiento: %s (%s)\n",
        $r->Totales->Asentimiento,
        $r->Informacion->Fecha);
    }
  }
?>
];

var width = 800;
var height = 200;
var barWidth = width / data.length;
var maxPresentes = d3.max(data, function(d) { return d.presentes; });
var minPresentes = d3.min(data, function(d) { return d.presentes; });

var x = d3.scale.linear().domain([0, data.length]).range([0, width]);
var y = d3.scale.linear().domain([0, 350]).rangeRound([0, height]);

var barDemo = d3.select("#viz")
  .append("svg:svg")
  .attr("width", width)
  .attr("height", height);

var enter = barDemo
  .selectAll("rect")
  .data(data)
  .enter();

enter.append("svg:rect")
  .attr("x", function(d, index) { return x(index); })
  .attr("y", function(d) { return height - y(d.si) - y(d.no); })
  .attr("height", function(d) { return y(d.no); })
  .attr("width", barWidth)
  .attr("fill", "#AA4444");

enter.append("svg:rect")
  .attr("x", function(d, index) { return x(index); })
  .attr("y", function(d) { return height - y(d.si); })
  .attr("height", function(d) { return y(d.si); })
  .attr("width", barWidth)
  .attr("fill", "#448800");

enter.append("svg:rect")
  .attr("x", function(d, index) { return x(index); })
  .attr("y", function(d) { return height - y(d.si) - y(d.no) - y(d.abs); })
  .attr("height", function(d) { return y(d.abs); })
  .attr("width", barWidth)
  .attr("fill", "#444444");

document.write("Max presentes: " + maxPresentes + "<br/>");
document.write("Min presentes: " + minPresentes + "<br/>");
      </script>

    </body>
</html>