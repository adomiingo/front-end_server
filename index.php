<?php
$db_path = "/var/www/ubungen/kalender.db";
$mensaje = "";
$mensaje_tipo = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha_seleccionada = $_POST['daten'];
    $hoy = date('Y-m-d');

    if ($fecha_seleccionada < $hoy) {
        $mensaje = "Fehler: Das Datum darf nicht in der Vergangenheit liegen.";
        $mensaje_tipo = "error";
    } else {
        try {
            $db = new PDO("sqlite:$db_path");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
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
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erinnerungen</title>
    <link rel="stylesheet" href="./css/phpindex.css">
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
            
            <label>Datu:</label>
            <input type="date" name="daten" min="<?php echo date('Y-m-d'); ?>" required>
            
            <button type="submit">fertig</button>
        </form>
    </div>
</body>
</html>