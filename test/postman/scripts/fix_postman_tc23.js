const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'movie_ticket_auth_test.postman_collection.json');
let data = JSON.parse(fs.readFileSync(file, 'utf8'));

data.item.forEach(item => {
    if (item.name.includes('[TC-AH-23]') && item.request && item.request.body && item.request.body.raw) {
        let bodyObj = JSON.parse(item.request.body.raw);
        delete bodyObj.email;
        item.request.body.raw = JSON.stringify(bodyObj, null, 4);
    }
});

fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf8');
console.log('Fixed TC-AH-23 in Postman collection!');
