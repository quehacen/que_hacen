d3.csv("../csv/participacion.csv", function(data) {

  // Convert strings to numbers.
  data.forEach(function(d) {
    d.total = +d.total;
    d.si = +d.si;
    d.no = +d.no;
    d.abs = +d.abs;
    d.novota = +d.novota;
  });

  var node = d3
    .select("body")
    .selectAll("div")
    .data(data)
    .enter()
    .append("div")
    .style("border-left", function(d) { return 200*(d.novota / d.total) + "px solid #880000"; })
    .style("padding-left", "10px")
    .style("margin-top", "2px")
    .style("font-size", "small")
    .text(function(d) { return d.nombre + " (" + d.novota + "/" + d.total + ")"; });
});

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
