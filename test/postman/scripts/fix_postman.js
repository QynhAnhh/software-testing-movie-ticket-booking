const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'movie_ticket_auth_test.postman_collection.json');
let data = JSON.parse(fs.readFileSync(file, 'utf8'));

// Add pre-request script at the collection level
data.event = data.event || [];
const preRequestIndex = data.event.findIndex(e => e.listen === 'prerequest');
const preRequestScript = {
    listen: "prerequest",
    script: {
        type: "text/javascript",
        exec: [
            "if (!pm.collectionVariables.has('sharedEmail')) {",
            "    pm.collectionVariables.set('sharedEmail', 'test_shared_' + Date.now() + '@test.com');",
            "    pm.collectionVariables.set('sharedPhone', '090' + Math.floor(1000000 + Math.random() * 9000000));",
            "}",
            "pm.variables.set('freshEmail', 'test_fresh_' + Date.now() + Math.floor(Math.random()*1000) + '@test.com');",
            "pm.variables.set('freshPhone', '09' + Math.floor(10000000 + Math.random() * 90000000));"
        ]
    }
};
if (preRequestIndex > -1) {
    data.event[preRequestIndex] = preRequestScript;
} else {
    data.event.push(preRequestScript);
}

// Modify items
data.item.forEach(item => {
    if (item.request && item.request.body && item.request.body.raw) {
        let bodyObj = JSON.parse(item.request.body.raw);
        
        // Strategy: 
        // TC-AH-01 (Đăng ký hợp lệ): Use sharedEmail and sharedPhone, password: "abcdfhgjtghtg"
        if (item.name.includes('[TC-AH-01]')) {
            bodyObj.email = "{{sharedEmail}}";
            bodyObj.phone = "{{sharedPhone}}";
        }
        // TC-AH-15 (Đăng ký trùng Email): Use sharedEmail and freshPhone
        else if (item.name.includes('[TC-AH-15]')) {
            bodyObj.email = "{{sharedEmail}}";
            bodyObj.phone = "{{freshPhone}}";
        }
        // TC-AH-16 (Đăng ký trùng SĐT): Use freshEmail and sharedPhone
        else if (item.name.includes('[TC-AH-16]')) {
            bodyObj.email = "{{freshEmail}}";
            bodyObj.phone = "{{sharedPhone}}";
        }
        // TC-AH-17, TC-AH-18 (Login): Use sharedEmail
        else if (item.name.includes('[TC-AH-17]') || item.name.includes('[TC-AH-18]')) {
            bodyObj.email = "{{sharedEmail}}";
            if (item.name.includes('[TC-AH-17]')) {
                bodyObj.password = "abcdfhgjtghtg"; // using the pass from TC-AH-01
            }
        }
        // All other Registration tests: Use freshEmail and freshPhone unless testing invalid email/phone format
        else if (!item.request.url.raw.includes('login')) {
            if (!item.name.includes('SĐT') && !item.name.includes('Email') && !item.name.includes('Số điện thoại')) {
                bodyObj.email = "{{freshEmail}}";
                bodyObj.phone = "{{freshPhone}}";
            } else if (item.name.includes('Email')) {
                // If it's testing invalid email format, keep the bad email, but fresh phone
                bodyObj.phone = "{{freshPhone}}";
            } else if (item.name.includes('SĐT') || item.name.includes('Số điện thoại')) {
                // If it's testing invalid phone format, keep the bad phone, but fresh email
                bodyObj.email = "{{freshEmail}}";
            }
        }

        item.request.body.raw = JSON.stringify(bodyObj, null, 4);
    }

    // 2. Fix Assertions in tests
    if (item.event) {
        item.event.forEach(ev => {
            if (ev.listen === 'test' && ev.script && ev.script.exec) {
                let script = ev.script.exec.join('\n');
                
                if (item.name.includes('[TC-AH-11]')) {
                    script = script.replace(/pm\.expect\(jsonData\.message\)\.to\.include\('.*?'\);/, "pm.expect(jsonData.message).to.include('chỉ được chứa số');");
                }
                if (item.name.includes('[TC-AH-15]')) {
                    script = script.replace(/pm\.expect\(jsonData\.message\)\.to\.include\('.*?'\);/, "pm.expect(jsonData.message).to.include('Email này đã được sử dụng');");
                }
                if (item.name.includes('[TC-AH-16]')) {
                    script = script.replace(/pm\.expect\(jsonData\.message\)\.to\.include\('.*?'\);/, "pm.expect(jsonData.message).to.include('Số điện thoại này đã được sử dụng');");
                }
                if (item.name.includes('[TC-AH-21]')) {
                    script = script.replace(/pm\.expect\(jsonData\.message\)\.to\.include\('.*?'\);/, "pm.expect(jsonData.message).to.include('Mật khẩu xác nhận không khớp');");
                }
                if (item.name.includes('[TC-AH-26]') || item.name.includes('[TC-AH-28]')) {
                    script = script.replace(/pm\.expect\(jsonData\.message\)\.to\.include\('.*?'\);/, "pm.expect(jsonData.message).to.include('không được chứa số hoặc ký tự đặc biệt');");
                }
                if (item.name.includes('[TC-AH-30]') || item.name.includes('[TC-AH-32]')) {
                    script = script.replace(/pm\.expect\(jsonData\.message\)\.to\.include\('.*?'\);/, "pm.expect(jsonData.message).to.include('không được vượt quá 255 ký tự');");
                }
                
                ev.script.exec = script.split('\n');
            }
        });
    }
});

fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf8');
console.log('Postman collection fixed successfully!');
