<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Matrix - Real-time Inventory</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #1a1a1a;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
            overflow: hidden;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .matrix-wrapper {
            background: #0d0d0d;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 3px solid #333;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .matrix {
            display: grid;
            gap: 10px;
            flex: 1;
            overflow: hidden;
        }

        .level-row {
            display: grid;
            grid-template-columns: 90px repeat(6, 1fr);
            gap: 10px;
            flex: 1;
            min-height: 0;
        }

        .level-label {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2em;
            color: #ffffff;
            background: linear-gradient(135deg, #1e1e2e, #2d2d44);
            border-radius: 10px;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            border: 3px solid #444;
        }

        .column-cell {
            border-radius: 10px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        }

        .column-cell:hover {
            transform: scale(1.08);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.6);
            border: 4px solid rgba(255, 255, 255, 0.4);
        }

        /* Status colors */
        .status-empty {
            background: linear-gradient(135deg, #404040, #505050);
            color: #aaa;
            border: 4px dashed #666;
        }

        .column-sku {
            font-size: 1.4em;
            margin-bottom: 6px;
            font-family: 'Courier New', monospace;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
        }

        .column-product {
            font-size: 1em;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 900;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
        }

        .column-count {
            font-size: 1.1em;
            opacity: 1;
            margin-bottom: 4px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .column-percent {
            font-size: 0.9em;
            margin-top: 4px;
            opacity: 1;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .height-labels {
            display: grid;
            grid-template-columns: 90px repeat(6, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .height-label {
            text-align: center;
            font-weight: bold;
            color: white;
            font-size: 1.2em;
            background: linear-gradient(135deg, #2a2a3e, #3a3a52);
            border-radius: 10px;
            padding: 8px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            border: 3px solid #555;
        }

        .height-label:first-child {
            visibility: visible;
        }

        .fullscreen-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.8em;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 9999;
        }

        .fullscreen-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.5);
        }

        .fullscreen-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <button class="fullscreen-btn" id="fullscreenBtn" title="Enter Fullscreen">⛶</button>
    <div class="container">
        <div class="matrix-wrapper">
            <div class="height-labels">
                <div class="height-label">LEVEL</div>
                <div class="height-label">HEIGHT 1</div>
                <div class="height-label">HEIGHT 2</div>
                <div class="height-label">HEIGHT 3</div>
                <div class="height-label">HEIGHT 4</div>
                <div class="height-label">HEIGHT 5</div>
                <div class="height-label">HEIGHT 6</div>
            </div>
            <div class="matrix" id="matrixContainer">
                <!-- Matrix will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Fullscreen functionality
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        
        fullscreenBtn.addEventListener('click', function() {
            const element = document.documentElement;
            
            if (!document.fullscreenElement) {
                // Enter fullscreen
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
                fullscreenBtn.textContent = '⛶';
                fullscreenBtn.title = 'Exit Fullscreen';
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
                fullscreenBtn.textContent = '⛶';
                fullscreenBtn.title = 'Enter Fullscreen';
            }
        });

        // Update button state when fullscreen changes
        document.addEventListener('fullscreenchange', function() {
            if (document.fullscreenElement) {
                fullscreenBtn.title = 'Exit Fullscreen';
            } else {
                fullscreenBtn.title = 'Enter Fullscreen';
            }
        });

        // Fetch data every 10 seconds
        const REFRESH_INTERVAL = 10000; // 10 seconds

        // SKU to color mapping - Professional vibrant colors visible from far
        const SKU_COLORS = {
            'CC-500': { bg: 'linear-gradient(135deg, #FF4444, #DD0000)', text: '#FFFFFF' },
            'CC-600': { bg: 'linear-gradient(135deg, #00CCCC, #0099AA)', text: '#FFFFFF' },
            'CC-700': { bg: 'linear-gradient(135deg, #FFDD00, #FFAA00)', text: '#000000' },
            'CC-800': { bg: 'linear-gradient(135deg, #00DD55, #00AA33)', text: '#FFFFFF' },
            'CC-900': { bg: 'linear-gradient(135deg, #88FF00, #66DD00)', text: '#000000' },
            'CC-1000': { bg: 'linear-gradient(135deg, #FF5588, #FF0066)', text: '#FFFFFF' },
            'CC-1100': { bg: 'linear-gradient(135deg, #9966FF, #6633FF)', text: '#FFFFFF' },
            'CC-1200': { bg: 'linear-gradient(135deg, #FF8833, #FF6600)', text: '#FFFFFF' },
            'CC-1300': { bg: 'linear-gradient(135deg, #3399FF, #0066FF)', text: '#FFFFFF' },
            'CC-1400': { bg: 'linear-gradient(135deg, #FF3399, #DD0088)', text: '#FFFFFF' },
            'CC-1500': { bg: 'linear-gradient(135deg, #00FFAA, #00DD88)', text: '#000000' },
            'CC-1600': { bg: 'linear-gradient(135deg, #FF9900, #FF7700)', text: '#FFFFFF' },
        };

        function getSkuColor(sku) {
            if (SKU_COLORS[sku]) {
                return SKU_COLORS[sku];
            }
            // Generate a hash-based color for unknown SKUs
            let hash = 0;
            for (let i = 0; i < sku.length; i++) {
                hash = ((hash << 5) - hash) + sku.charCodeAt(i);
                hash = hash & hash; // Convert to 32bit integer
            }
            const colors = Object.values(SKU_COLORS);
            const colorIndex = Math.abs(hash) % colors.length;
            return colors[colorIndex];
        }

        function fetchMatrixData() {
            Promise.all([
                fetch('/api/warehouse/matrix-data').then(r => r.json()),
                fetch('/api/warehouse/summary').then(r => r.json())
            ]).then(([matrixResponse, summaryResponse]) => {
                updateMatrix(matrixResponse.matrix);
            }).catch(error => console.error('Error fetching data:', error));
        }

        function updateMatrix(matrix) {
            const container = document.getElementById('matrixContainer');
            container.innerHTML = '';

            matrix.forEach(levelData => {
                const levelRow = document.createElement('div');
                levelRow.className = 'level-row';

                // Level label
                const levelLabel = document.createElement('div');
                levelLabel.className = 'level-label';
                levelLabel.textContent = levelData.level;
                levelRow.appendChild(levelLabel);

                // Height cells
                levelData.heights.forEach(cell => {
                    const cellDiv = document.createElement('div');
                    cellDiv.className = `column-cell status-${cell.status}`;
                    cellDiv.title = `${cell.level}${cell.height}: ${cell.product_name} (${cell.active_count}/${cell.capacity})`;

                    // Apply custom color if not empty
                    if (cell.status !== 'empty') {
                        const colorData = getSkuColor(cell.sku);
                        cellDiv.style.background = colorData.bg;
                        cellDiv.style.color = colorData.text;
                    }

                    let content = '';
                    if (cell.status !== 'empty') {
                        content = `
                            <div class="column-sku">${cell.sku}</div>
                            <div class="column-product">${cell.product_name}</div>
                            <div class="column-count">${cell.active_count}/${cell.capacity}</div>
                            <div class="column-percent">${cell.occupancy_percent}%</div>
                        `;
                    } else {
                        content = '<div style="font-size: 3.5em; margin-bottom: 10px;">+</div><div style="font-size: 1.5em;">EMPTY</div>';
                    }

                    cellDiv.innerHTML = content;
                    levelRow.appendChild(cellDiv);
                });

                container.appendChild(levelRow);
            });
        }

        // Initial load
        fetchMatrixData();

        // Auto-refresh every 10 seconds
        setInterval(fetchMatrixData, REFRESH_INTERVAL);
    </script>
</body>
</html>
