var width = 800,
  height = 200;

var viz = d3.select("#viz")
  .append("svg:svg")
  .attr("width", width)
  .attr("height", height);

d3.csv("../csv/votaciones.csv", function(data) {

  // Convert strings to numbers.
  data.forEach(function(d) {
    d.presentes = +d.presentes;
    d.si = +d.si;
    d.no = +d.no;
    d.abs = +d.abs;
  });

  var barWidth = width / data.length;
  var maxPresentes = d3.max(data, function(d) { return d.presentes; });
  var minPresentes = d3.min(data, function(d) { return d.presentes; });
  var x = d3.scale.linear().domain([0, data.length]).range([0, width]);
  var y = d3.scale.linear().domain([0, 350]).rangeRound([0, height]);

  var node = viz
    .selectAll("rect")
    .data(data)
    .enter();

  node.append("svg:rect")
    .attr("x", function(d, index) { return x(index); })
    .attr("y", function(d) { return height - y(d.si) - y(d.no); })
    .attr("height", function(d) { return y(d.no); })
    .attr("width", barWidth)
    .attr("fill", "#AA4444");

  node.append("svg:rect")
    .attr("x", function(d, index) { return x(index); })
    .attr("y", function(d) { return height - y(d.si); })
    .attr("height", function(d) { return y(d.si); })
    .attr("width", barWidth)
    .attr("fill", "#448800");

  node.append("svg:rect")
    .attr("x", function(d, index) { return x(index); })
    .attr("y", function(d) { return height - y(d.si) - y(d.no) - y(d.abs); })
    .attr("height", function(d) { return y(d.abs); })
    .attr("width", barWidth)
    .attr("fill", "#444444");

  d3.select("body").append("div").text("Max presentes: " + maxPresentes);
  d3.select("body").append("div").text("Min presentes: " + minPresentes);
});
