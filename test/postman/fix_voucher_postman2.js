const fs = require('fs');
const path = require('path');
const file = path.join(__dirname, 'movie_ticket_voucher_test.postman_collection.json');
let data = JSON.parse(fs.readFileSync(file, 'utf8'));

data.item.forEach(item => {
    if (item.request && item.request.url && item.request.url.raw) {
        item.request.url.path = ["software-testing-movie-ticket-booking", "api", "check_voucher.php"];
    }
});
fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf8');
console.log('Postman paths fixed!');
