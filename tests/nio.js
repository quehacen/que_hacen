/*
https://gist.github.com/chriso/944111
http://www.coderholic.com/scraping-the-web-with-node-io/
https://github.com/chriso/node.io/wiki/Input---Output

This works: 

init() function

input() receives pos, which increases on each call, and limit (=1)
it will use pos to retrieve URL from database
callback must be called with an array of URLs as argument
	
run() receives url as only argument. it will getHtml() that URL
*/

var nodeio = require('node.io');
exports.job = new nodeio.Job({}, {
	settings: {
		a: 5,
		b: 20
	},	
	init: function() {
		console.log("init a:" + this.settings.a);
	},
	input: function(pos, limit, callback) {
		console.log("pos:" + pos + " limit:" + limit);
		if(pos < 5) {
			callback([pos, pos+100]);
		} else {
			callback(null, false);
		}
	},
	run: function(url) {
		this.emit(["url:"+url]);
	}

});
