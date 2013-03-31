var csv = require('ya-csv');

var filename = '../csv/test_node.csv';

var writer = csv.createCsvFileWriter(filename);

var data = [
    ['a','b','c','d','e','f','g'], 
    ['h','i','j','k','l','m','n']
];

data.forEach(function(rec) {
    writer.writeRecord(rec);
});
