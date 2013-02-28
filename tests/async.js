var async = require("async");

function test() {
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
test();
