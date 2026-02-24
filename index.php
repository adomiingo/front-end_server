<?php
$db_path = "/var/www/ubungen/kalender.db";
$mensaje = "";
$mensaje_tipo = "";

// 1. Conexión inicial para poder leer datos aunque no sea un POST
try {
    $db = new PDO("sqlite:$db_path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// 2. Lógica de guardado (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha_seleccionada = $_POST['daten'];
    $hoy = date('Y-m-d');

    if ($fecha_seleccionada < $hoy) {
        $mensaje = "Fehler: Das Datum darf nicht in der Vergangenheit liegen.";
        $mensaje_tipo = "error";
    } else {
        try {
            $sql = "INSERT INTO aufgaben (betreff, beschreibung, fach, daten) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $_POST['betreff'], 
                $_POST['beschreibung'], 
                $_POST['fach'], 
                $_POST['daten']
            ]);
            $mensaje = "Aufgabe erfolgreich gespeichert!";
            $mensaje_tipo = "success";
        } catch (PDOException $e) {
            $mensaje = "Datenbankfehler: " . $e->getMessage();
            $mensaje_tipo = "error";
        }
    }
}

// 3. Lógica de FILTRADO (GET)
$query_parts = [];
$params = [];

if (!empty($_GET['f_fach'])) {
    $query_parts[] = "fach = ?";
    $params[] = $_GET['f_fach'];
}
if (!empty($_GET['f_zustand'])) {
    $query_parts[] = "zustand = ?";
    $params[] = $_GET['f_zustand'];
}
if (!empty($_GET['f_datum'])) {
    $query_parts[] = "daten = ?";
    $params[] = $_GET['f_datum'];
}

$sql_filter = "SELECT * FROM aufgaben";
if (count($query_parts) > 0) {
    $sql_filter .= " WHERE " . implode(" AND ", $query_parts);
}
$sql_filter .= " ORDER BY daten ASC";

$stmt_list = $db->prepare($sql_filter);
$stmt_list->execute($params);
$aufgaben = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erinnerungen</title>
    <link rel="stylesheet" href="./css/phpindex.css">
    <style>
        /* Estilos rápidos para la tabla y filtros si no están en tu CSS */
        .filter-section { background: #fff; padding: 15px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .aufgaben-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
        .aufgaben-table th, .aufgaben-table td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .aufgaben-table th { background: #007bff; color: white; }
        .status-ausstehen { color: #dc3545; font-weight: bold; }
        .status-erledigt { color: #28a745; font-weight: bold; }
        .reset-link { font-size: 0.8em; color: #666; text-decoration: none; margin-left: 10px; }
    </style>
</head>
<body>
    
    <div id="principal">
        <h2>Neue Aufgabe</h2>
        
        <?php if($mensaje): ?>
            <div class="alert <?php echo $mensaje_tipo; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>Titel:</label>
            <input type="text" name="betreff" placeholder="Was ist zu tun?" required>
            
            <label>Beschreibung:</label>
            <textarea name="beschreibung" rows="3" placeholder="Details..."></textarea>
            
            <label>Fach:</label>
            <select name="fach">
                <option value="Redes">Redes Locales</option>
                <option value="Sistemas">Sistemas Operativos</option>
                <option value="Seguridad">Seguridad Informática</option>
                <option value="Web">Aplicaciones Web</option>
                <option value="Andere">Andere</option>
            </select>
            
            <label>Datum:</label>
            <input type="date" name="daten" min="<?php echo date('Y-m-d'); ?>" required>
            
            <button type="submit">Fertig</button>
        </form>

        <hr>

        <div class="filter-section">
            <h3>Filter <a href="index.php" class="reset-link">(Zurücksetzen)</a></h3>
            <form method="get" style="display: flex; flex-wrap: wrap; gap: 10px;">
                <select name="f_fach">
                    <option value="">Alle Fächer</option>
                    <option value="Redes" <?php if(@$_GET['f_fach']=='Redes') echo 'selected'; ?>>Redes</option>
                    <option value="Sistemas" <?php if(@$_GET['f_fach']=='Sistemas') echo 'selected'; ?>>Sistemas</option>
                    <option value="Seguridad" <?php if(@$_GET['f_fach']=='Seguridad') echo 'selected'; ?>>Seguridad</option>
                    <option value="Web" <?php if(@$_GET['f_fach']=='Web') echo 'selected'; ?>>Web</option>
                </select>

                <select name="f_zustand">
                    <option value="">Status</option>
                    <option value="Ausstehen" <?php if(@$_GET['f_zustand']=='Ausstehen') echo 'selected'; ?>>Ausstehen</option>
                    <option value="Erledigt" <?php if(@$_GET['f_zustand']=='Erledigt') echo 'selected'; ?>>Erledigt</option>
                </select>

                <input type="date" name="f_datum" value="<?php echo @$_GET['f_datum']; ?>">
                
                <button type="submit" style="width: auto; padding: 5px 15px;">Suche</button>
            </form>
        </div>

        <table class="aufgaben-table">
            <thead>
                <tr>
                    <th>Betreff</th>
                    <th>Fach</th>
                    <th>Datum</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($aufgaben) > 0): ?>
                    <?php foreach ($aufgaben as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['betreff']); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['beschreibung']); ?></small>
                            </td>
                            <td><?php echo $row['fach']; ?></td>
                            <td><?php echo date("d-m-Y", strtotime($row['daten'])); ?></td>
                            <td class="<?php echo ($row['zustand'] == 'Ausstehen') ? 'status-ausstehen' : 'status-erledigt'; ?>">
                                <?php echo $row['zustand']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Keine Aufgaben gefunden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>