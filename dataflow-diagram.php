<?php
require_once __DIR__ . '/includes/session_config_superadmin.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/conn/db_connect.php';

$dbc = $GLOBALS['conn'] ?? ($GLOBALS['conn_login'] ?? null);
$user_school = (function_exists('getUserSchool') && $dbc) ? getUserSchool($dbc) : null;
$theme_color = $user_school['theme_color'] ?? ($_SESSION['theme_color'] ?? '#098744');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qnnect Data Flow Diagram</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-color: <?php echo htmlspecialchars($theme_color, ENT_QUOTES); ?>; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f5f7; padding: 24px; }
        .page-header { display:flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .page-header h1 { font-size: 20px; margin: 0; color: #1f2937; }
        .card { box-shadow: 0 8px 24px rgba(0,0,0,.06); border: 1px solid #e5e7eb; border-radius: 12px; }
        .card-header { background: #ffffff; }
        .legend { font-size: 12px; color: #6b7280; }
        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-outline-primary { color: var(--primary-color); border-color: var(--primary-color); }
        .btn-outline-primary:hover { background-color: var(--primary-color); color: #fff; }
        .mermaid { background: #ffffff; border-radius: 12px; padding: 16px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvg@3/dist/browser/canvg.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        const primary = '<?php echo addslashes($theme_color); ?>';
        mermaid.initialize({
            startOnLoad: true,
            theme: 'default',
            themeVariables: {
                primaryColor: primary,
                primaryTextColor: '#ffffff',
                primaryBorderColor: primary,
                lineColor: primary,
                secondaryColor: '#E5F4EC',
                tertiaryColor: '#F8FAFC',
                fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
            }
        });
        async function exportDfdPng() {
            try {
                // Render fresh SVG from source
                const srcNode = document.getElementById('dfd-src');
                const src = srcNode ? srcNode.textContent : null;
                if (!src) { alert('Source not found. Reload and try again.'); return; }
                const out = await mermaid.render('dfdExport', src);
                let svgString = out.svg || '';

                // Normalize SVG (add namespaces and dimensions if missing)
                const doc = new DOMParser().parseFromString(svgString, 'image/svg+xml');
                const svgRoot = doc.documentElement;
                if (!svgRoot.getAttribute('xmlns')) svgRoot.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                if (!svgRoot.getAttribute('xmlns:xlink')) svgRoot.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
                let width = parseFloat(svgRoot.getAttribute('width')) || 0;
                let height = parseFloat(svgRoot.getAttribute('height')) || 0;
                const vbAttr = svgRoot.getAttribute('viewBox');
                if ((!width || !height) && vbAttr) {
                    const p = vbAttr.split(/\s+/).map(Number);
                    width = width || p[2] || 1400; height = height || p[3] || 900;
                }
                if (!svgRoot.getAttribute('viewBox')) {
                    const vbW = width || 1400; const vbH = height || 900;
                    svgRoot.setAttribute('viewBox', `0 0 ${vbW} ${vbH}`);
                }
                if (!width) { width = 1400; svgRoot.setAttribute('width', String(width)); }
                if (!height) { height = 900; svgRoot.setAttribute('height', String(height)); }
                svgString = new XMLSerializer().serializeToString(svgRoot);
                const scale = 2; const outW = Math.ceil(width * scale); const outH = Math.ceil(height * scale);

                // Prepare canvas
                const canvas = document.createElement('canvas');
                canvas.width = outW; canvas.height = outH;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, outW, outH);

                // Try Canvg first; fallback to Image data URL if unavailable
                try {
                    if (window.Canvg) {
                        const v = await window.Canvg.fromString(ctx, svgString);
                        await v.render();
                    } else {
                        const url = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgString);
                        await new Promise((resolve, reject) => {
                            const img = new Image();
                            img.crossOrigin = 'anonymous';
                            img.onload = () => { ctx.drawImage(img, 0, 0, outW, outH); resolve(); };
                            img.onerror = () => { reject(new Error('image')); };
                            img.src = url;
                        });
                    }
                } catch (err) {
                    // Fallback: rasterize the on-screen diagram using html2canvas
                    const target = document.querySelector('.mermaid');
                    if (!target || !window.html2canvas) throw new Error('Export fallback unavailable');
                    const canvasShot = await html2canvas(target, { backgroundColor: '#ffffff', scale: 2 });
                    canvasShot.toBlob(function (blob) {
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = 'qnnect-dataflow.png';
                        document.body.appendChild(a);
                        a.click();
                        setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
                    }, 'image/png');
                    return;
                }

                canvas.toBlob(function (blob) {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'qnnect-dataflow.png';
                    document.body.appendChild(a);
                    a.click();
                    setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
                }, 'image/png');
            } catch (e) {
                alert('Export failed: ' + e.message);
            }
        }
    </script>
</head>
<body>
    <div class="container-fluid">
        <div class="page-header">
            <h1>Qnnect Data Flow Diagram</h1>
            <div class="d-flex gap-2">
                <a href="admin/admin_panel.php" class="btn btn-outline-primary btn-sm">Back to Admin</a>
                <button class="btn btn-primary btn-sm" onclick="exportDfdPng()">Export PNG</button>
        </div>
    </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="legend">End-to-end flow from Registration to Reporting with scoped Super Admin access</span>
                <span class="badge text-bg-light" style="border:1px solid #e5e7eb; color: var(--primary-color);">Theme Color</span>
            </div>
            <div class="card-body">
                <div class="mermaid">
flowchart TD
  A(( )) --> B["User lands on Login page"]
  B -->|Chooses Register| C["Registration Form: username, email, password, role request"]
  C --> D{"Validation: server-side and duplicate checks"}
  D -->|Valid| E["Create user (role set per policy: admin or instructor)\nLink optional School ID if provided"]
  D -->|Invalid| C
  E --> F["Send verification email or show PIN modal (if enabled)"]
  F --> G["Login: authenticate then issue session"]
  G --> H{"Role routing"}
  H -->|Admin/Instructor| J["Admin Dashboard - teaching and admin"]
  H -->|Super Admin| K["Super Admin Panel"]
  
  K --> KA["Select School Scope or All Schools<br/>Scope School ID stored in session"]
  KA --> KB["Users & Roles management<br/>CRUD on Users"]
  KA --> KC["System Logs management<br/>CRUD on System Logs"]
  KA --> KD["Courses & Sections (hierarchical)<br/>CRUD on Courses and Sections"]
  KA --> KE["Students<br/>CRUD on Students"]
  KA --> KF["Attendance<br/>CRUD on Attendance"]
  KA --> KG["Schedules<br/>CRUD on Schedules"]
  
  J --> T1["View assigned classes/sections"]
  T1 --> T2["Scan/record attendance (QR)"]
  T2 --> T3["Writes to Attendance with School"]
  T1 --> T4["View schedules from Schedules"]
  
  J --> R["Reports & Printing (scoped)"]
  K --> R
  R --> R1["Aggregates by scope: per student, class, school<br/>Joins Students, Attendance, Sections, Courses, Schools"]
  
  subgraph Logging
  L1["Every CRUD action logs to System Logs<br/>(user, action, entity, school, time)"]
  end

  KB --> L1
  KC --> L1
  KD --> L1
  KE --> L1
  KF --> L1
  KG --> L1
                </div>
                <!-- Keep a copy of the diagram source for export rendering -->
                <script type="text/plain" id="dfd-src">
flowchart TD
  A(( )) --> B["User lands on Login page"]
  B -->|Chooses Register| C["Registration Form: username, email, password, role request"]
  C --> D{"Validation: server-side and duplicate checks"}
  D -->|Valid| E["Create user (role set per policy: admin or instructor)\nLink optional School ID if provided"]
  D -->|Invalid| C
  E --> F["Send verification email or show PIN modal (if enabled)"]
  F --> G["Login: authenticate then issue session"]
  G --> H{"Role routing"}
  H -->|Admin/Instructor| J["Admin Dashboard - teaching and admin"]
  H -->|Super Admin| K["Super Admin Panel"]

  K --> KA["Select School Scope or All Schools\nScope School ID stored in session"]
  KA --> KB["Users & Roles management\nCRUD on Users"]
  KA --> KC["System Logs management\nCRUD on System Logs"]
  KA --> KD["Courses & Sections (hierarchical)\nCRUD on Courses and Sections"]
  KA --> KE["Students\nCRUD on Students"]
  KA --> KF["Attendance\nCRUD on Attendance"]
  KA --> KG["Schedules\nCRUD on Schedules"]

  J --> T1["View assigned classes/sections"]
  T1 --> T2["Scan/record attendance (QR)"]
  T2 --> T3["Writes to Attendance with School"]
  T1 --> T4["View schedules from Schedules"]

  J --> R["Reports & Printing (scoped)"]
  K --> R
  R --> R1["Aggregates by scope: per student, class, school\nJoins Students, Attendance, Sections, Courses, Schools"]

  subgraph Logging
  L1["Every CRUD action logs to System Logs\n(user, action, entity, school, time)"]
  end

  KB --> L1
  KC --> L1
  KD --> L1
  KE --> L1
  KF --> L1
  KG --> L1
    </script>
            </div>
        </div>
</div>
</body>
</html>


