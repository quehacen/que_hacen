var qhdb = require("./qhdb.js");

qhdb.connect(function() {
	qhdb.test();
});
