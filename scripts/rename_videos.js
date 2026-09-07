const fs = require('fs');
const path = require('path');

const outputDir = path.join(__dirname, '../tests/e2e/output');

function renameFilesInDir(dir) {
  if (fs.existsSync(dir)) {
    const files = fs.readdirSync(dir);
    
    files.forEach(file => {
      const fullPath = path.join(dir, file);
      
      // Nếu là thư mục thì đệ quy vào trong
      if (fs.statSync(fullPath).isDirectory()) {
        renameFilesInDir(fullPath);
      } else {
        // Chỉ xử lý file ảnh hoặc video
        if (file.endsWith('.webm') || file.endsWith('.png')) {
          const match = file.match(/(TC-[A-Z]+-\d+)/);
          if (match) {
            const tcId = match[1];
            const ext = path.extname(file);
            const newName = tcId + ext;
            const newPath = path.join(outputDir, newName); // Đưa thẳng ra ngoài thư mục output
            
            if (fullPath !== newPath) {
              try {
                if (fs.existsSync(newPath)) {
                   fs.unlinkSync(newPath); // Xóa file cũ đi để ghi đè
                }
                fs.renameSync(fullPath, newPath);
                console.log(`[Auto-Rename] ${file} -> ${newName}`);
              } catch (err) {
                console.error(`Không thể đổi tên file ${file}:`, err);
              }
            }
          } else {
            // File không chứa ID -> Đổi tên thành định dạng dễ đọc thay vì xóa
            try {
              let cleanName = file;
              
              // Nếu là video rác nội bộ của Playwright (page@...) -> Xóa luôn
              if (cleanName.startsWith('page@')) {
                  fs.unlinkSync(fullPath);
                  console.log(`[Auto-Delete] Đã xóa video rác nội bộ: ${file}`);
                  return;
              }
              
              // Xóa chuỗi mã Hash ngẫu nhiên (Playwright hay dùng chuỗi 36 ký tự)
              cleanName = cleanName.replace(/^[a-f0-9\-]{36}_/, '');
              // Xóa chữ .passed hoặc .failed
              cleanName = cleanName.replace(/\.passed/, '').replace(/\.failed/, '');
              // Đưa về file name cơ bản
              const ext = path.extname(cleanName);
              const basename = path.basename(cleanName, ext);
              // Thay dấu gạch dưới thành dấu cách cho dễ đọc
              const finalName = basename.replace(/_/g, ' ') + ext;
              const newPath = path.join(outputDir, finalName);
              
              if (fullPath !== newPath) {
                if (fs.existsSync(newPath)) {
                   fs.unlinkSync(newPath);
                }
                fs.renameSync(fullPath, newPath);
                console.log(`[Auto-Rename-Readable] ${file} -> ${finalName}`);
              }
            } catch(err) {
               console.error(`Không thể xử lý đổi tên cho file ${file}:`, err);
            }
          }
        }
      }
    });
  }
}

// Xóa thư mục rỗng sau khi dọn dẹp
function cleanEmptyDirs(dir) {
    if (fs.existsSync(dir)) {
        const files = fs.readdirSync(dir);
        if (files.length === 0) {
            fs.rmdirSync(dir);
        }
    }
}

renameFilesInDir(outputDir);
cleanEmptyDirs(path.join(outputDir, 'videos'));
console.log('✅ Hoàn tất quá trình quét và dọn dẹp video/ảnh!');
