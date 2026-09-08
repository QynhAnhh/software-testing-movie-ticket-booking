const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'movie_ticket_auth_test.postman_collection.json');
let data = JSON.parse(fs.readFileSync(file, 'utf8'));

// The script lines we want to inject into TC-AH-01
const newScriptLines = [
    "pm.collectionVariables.set('sharedEmail', 'test_shared_' + Date.now() + '@test.com');",
    "pm.collectionVariables.set('sharedPhone', '090' + Math.floor(1000000 + Math.random() * 9000000));"
];

// Remove sharedEmail generation from Collection pre-request script
if (data.event) {
    data.event.forEach(ev => {
        if (ev.listen === 'prerequest' && ev.script && ev.script.exec) {
            ev.script.exec = ev.script.exec.filter(line => !line.includes('sharedEmail') && !line.includes('sharedPhone'));
            // but we still want freshEmail and freshPhone to be generated for other tests (they can stay)
            // actually freshEmail/freshPhone are fine being generated on every request, they are truly "fresh"
        }
    });
}

// Add sharedEmail generation to TC-AH-01 pre-request script
data.item.forEach(item => {
    if (item.name.includes('[TC-AH-01]')) {
        let hasPreRequest = false;
        if (!item.event) item.event = [];
        
        item.event.forEach(ev => {
            if (ev.listen === 'prerequest') {
                hasPreRequest = true;
                if (!ev.script) ev.script = { type: 'text/javascript', exec: [] };
                // prepend the new script lines
                ev.script.exec = [...newScriptLines, ...ev.script.exec];
            }
        });
        
        if (!hasPreRequest) {
            item.event.push({
                listen: 'prerequest',
                script: {
                    type: 'text/javascript',
                    exec: newScriptLines
                }
            });
        }
    }
});

fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf8');
console.log('Fixed TC-AH-01 and Collection prerequest in Postman collection!');
