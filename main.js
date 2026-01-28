const { app, BrowserWindow, Tray, Menu } = require('electron');
const express = require('express');
const path = require('path');

let tray = null;
let mainWindow = null;
let phpServer = null;

// Start Express server to serve PHP files
function startServer() {
    const serverApp = express();
    const port = 8000;

    // Serve static files
    serverApp.use(express.static(__dirname));

    // Handle POST requests to gemini-api.php
    serverApp.post('/gemini-api.php', express.json(), async (req, res) => {
        const { exec } = require('child_process');
        const tempFile = path.join(__dirname, 'temp-request.json');
        const fs = require('fs');
        
        // Write request to temp file
        fs.writeFileSync(tempFile, JSON.stringify(req.body));
        
        // Execute PHP script
        exec(`php gemini-api-electron.php "${tempFile}"`, (error, stdout, stderr) => {
            if (error) {
                res.json({ success: false, error: stderr || error.message });
                return;
            }
            
            try {
                const result = JSON.parse(stdout);
                res.json(result);
            } catch (e) {
                res.json({ success: false, error: 'Failed to parse PHP response' });
            }
            
            // Clean up temp file
            if (fs.existsSync(tempFile)) {
                fs.unlinkSync(tempFile);
            }
        });
    });

    phpServer = serverApp.listen(port, () => {
        console.log(`Server running on http://localhost:${port}`);
    });
}

function createWindow() {
    // Don't create a new window if one already exists
    if (mainWindow) {
        if (mainWindow.isMinimized()) mainWindow.restore();
        mainWindow.show();
        mainWindow.focus();
        return;
    }

    mainWindow = new BrowserWindow({
        width: 400,
        height: 600,
        x: 100, // Position from right edge
        y: 100, // Position from top
        frame: true,
        resizable: true,
        alwaysOnTop: true,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        },
        icon: path.join(__dirname, 'ifen_logo_1.png')
    });

    mainWindow.loadURL('http://localhost:8000/gemini-chat.html');

    // Handle window close - hide instead of destroy
    mainWindow.on('close', (event) => {
        if (!app.isQuitting) {
            event.preventDefault();
            mainWindow.hide();
        }
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

function createTray() {
    // Try to use .ico file first (better for Windows), fallback to .png
    let iconPath = path.join(__dirname, 'ifen_logo_1.ico');
    if (!require('fs').existsSync(iconPath)) {
        iconPath = path.join(__dirname, 'ifen_logo_1.png');
    }
    
    tray = new Tray(iconPath);
    
    const contextMenu = Menu.buildFromTemplate([
        {
            label: 'Open Chat',
            click: () => {
                createWindow();
            }
        },
        {
            label: 'Quit',
            click: () => {
                app.isQuitting = true;
                app.quit();
            }
        }
    ]);

    tray.setToolTip('Gemini Chat');
    tray.setContextMenu(contextMenu);

    // Click tray icon to toggle window
    tray.on('click', () => {
        if (mainWindow && mainWindow.isVisible()) {
            mainWindow.hide();
        } else {
            createWindow();
        }
    });
}

// Start server when app is ready
app.whenReady().then(() => {
    startServer();
    createTray();
    createWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    });
});

// Quit when all windows are closed (except on macOS)
app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        // Don't quit, just hide to tray
    }
});

// Cleanup on quit
app.on('before-quit', () => {
    if (phpServer) {
        phpServer.close();
    }
});
