const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 55400;
const DIR = __dirname;

const MIME = {
    '.html': 'text/html',
    '.js': 'text/javascript',
    '.css': 'text/css',
    '.json': 'application/json',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.svg': 'image/svg+xml'
};

const server = http.createServer((req, res) => {
    let filePath = path.join(DIR, req.url === '/' ? 'index.html' : req.url);
    fs.readFile(filePath, (err, data) => {
        if (err) {
            res.writeHead(404);
            res.end('Not found');
        } else {
            const ext = path.extname(filePath);
            res.writeHead(200, { 'Content-Type': MIME[ext] || 'text/plain', 'Cache-Control': 'no-store, no-cache, must-revalidate' });
            res.end(data);
        }
    });
});

server.listen(PORT, '127.0.0.1', () => {
    console.log(`Preview server running at http://127.0.0.1:${PORT}`);
});
