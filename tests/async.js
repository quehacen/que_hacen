var async = require("async");

function waterfall() {
	var msg = "hi";
	async.waterfall([
		function(c){ 
			var a = [1];
			c(null, a); 
		}, function(a, c){ 
			a.push(2);
			a.push(msg);
			c(null, a); 
		}
	], function(err, result) {
		console.log(result);
		console.log(msg);
	});
}
function eachSeries() {
    var files = ['one', 'two', 'three'];
    async.eachSeries(files, function(item, cb) {
            console.log(item);
            setTimeout(function() {
                cb();
            }, 1000);
    }, function(err){
        if(err) {
            console.log(err);
        } else {
            console.log('success!');
        }
    });
}

//waterfall();
eachSeries();
