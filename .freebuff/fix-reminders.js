const fs = require('fs');
const path = 'C:\\Users\\user\\Desktop\\Haro\\DMX\\Finance_ar Software\\build\\preview\\index.html';
let content = fs.readFileSync(path, 'utf8');

// Find and remove the old grid section between the new tabs section and CREDIT
// The old section starts with "<!-- OLD REMOVED -->" and ends before CREDIT
const oldStart = content.indexOf('<!-- OLD REMOVED -->');
const creditMarker = '<!-- =================== CREDIT =================== -->';

if (oldStart !== -1) {
    const creditIndex = content.indexOf(creditMarker, oldStart);
    if (creditIndex !== -1) {
        // Remove everything between oldStart and the new template close before CREDIT
        // Find the </template> before CREDIT
        const templateClose = content.lastIndexOf('</template>', creditIndex);
        content = content.substring(0, oldStart) + '</div>\n</template>\n\n' + content.substring(creditIndex);
        fs.writeFileSync(path, content, 'utf8');
        console.log('Old reminders section removed successfully');
    } else {
        console.log('Could not find CREDIT marker');
    }
} else {
    console.log('Old section marker not found - it may already be removed');
}
