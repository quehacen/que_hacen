var http = require('http');

http.createServer(function (req, res) {
  res.writeHead(200, {'Content-Type': 'text/plain'});
  res.end('Hello World\n');
}).listen(1337, '62.75.161.47');

console.log('Server running at http://127.0.0.1:1337/');

