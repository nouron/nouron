<?php
// Cantina Hotspots tab — POST handler (JSON), and HTML content.
// Requires: $hotspots, $hotspotsPath, $characterSlugs, $tab

// ── POST handler (JSON save) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        header('Content-Type: application/json');
        $input        = json_decode(file_get_contents('php://input'), true);
        $allowedSlots = ['spot_0', 'spot_1', 'spot_2', 'spot_3', 'spot_4', 'spot_5'];
        $allowedDevices = ['desktop', 'tablet', 'mobile'];

        foreach ($allowedSlots as $slot) {
            foreach ($allowedDevices as $device) {
                if (isset($input[$slot][$device]['left'], $input[$slot][$device]['top'])) {
                    $hotspots[$slot][$device] = [
                        'left' => round(max(0, min(100, (float) $input[$slot][$device]['left'])), 2),
                        'top'  => round(max(0, min(100, (float) $input[$slot][$device]['top'])),  2),
                    ];
                }
            }
            if (isset($input[$slot]['characters']) && is_array($input[$slot]['characters'])) {
                $hotspots[$slot]['characters'] = array_values(array_filter(
                    array_map('strval', $input[$slot]['characters']),
                    fn($s) => preg_match('/^[a-z0-9_]+$/', $s)
                ));
            }
        }

        file_put_contents($hotspotsPath, json_encode($hotspots, JSON_PRETTY_PRINT));
        echo json_encode(['ok' => true]);
        exit;
    }
}
?>
<div class="tab-content <?= $tab === 'cantina' ? 'active' : '' ?>">
<div class="cantina-editor">

    <div class="hs-toolbar">
        <span class="hs-toolbar-label">Device:</span>
        <div class="hs-toolbar-group" id="hs-device-group">
            <button class="hs-btn active" data-device="desktop">Desktop ≥1024px</button>
            <button class="hs-btn" data-device="tablet">Tablet 768–1023px</button>
            <button class="hs-btn" data-device="mobile">Mobile &lt;768px</button>
        </div>
        <span class="hs-toolbar-label" style="margin-left:.5rem">Active Slot:</span>
        <div class="hs-toolbar-group" id="hs-slot-group">
            <button class="hs-btn slot-active" data-slot="spot_0">Spot 0</button>
            <button class="hs-btn" data-slot="spot_1">Spot 1</button>
            <button class="hs-btn" data-slot="spot_2">Spot 2</button>
            <button class="hs-btn" data-slot="spot_3">Spot 3</button>
            <button class="hs-btn" data-slot="spot_4">Spot 4</button>
            <button class="hs-btn" data-slot="spot_5">Spot 5</button>
        </div>
    </div>

    <div class="hs-canvas-wrap" id="hs-canvas">
        <img id="hs-image" src="/public/img/cantina/cantina-interior.webp" alt="Cantina Interior" draggable="false">
    </div>

    <div class="hs-coords-bar">
        <span>Click image to position active slot.</span>
        <span id="hs-cursor-pos"></span>
        <button class="hs-save-btn" id="hs-save-btn">Save</button>
        <span id="hs-save-status"></span>
    </div>

    <div class="hs-char-section">
        <h3 class="hs-char-title">Character → Spot Mapping</h3>
        <table class="hs-matrix" id="hs-matrix">
            <!-- rendered by JS -->
        </table>
    </div>

</div>
</div>

<script>const hotspots = <?= json_encode($hotspots) ?>; const characterSlugs = <?= json_encode($characterSlugs) ?>;</script>
<script src="/tools/assets/cantina.js"></script>
