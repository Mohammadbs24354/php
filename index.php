<?php
require_once 'db.php';

session_start();
$db = getDbConnection();

if (isset($_GET['action']) && $_GET['action'] == 'results') {
    $results = loadSurveyResults($db);
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="umfrageergebnisse_' . date('Y-m-d') . '.json"');
    echo json_encode($results);
    exit;
}

$nutzertoken = getUserToken($db);

if (!isset($_SESSION['fragen'])) {
    $fragen = loadQuestions($db);
    loadPossibleAnswers($db, $fragen);
    loadUserAnswers($db, $nutzertoken, $fragen);
    $_SESSION['fragen'] = $fragen;
} else {
    $fragen = $_SESSION['fragen'];
}

if (!isset($_SESSION['fragenindex'])) {
    $_SESSION['fragenindex'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentIndex = $_SESSION['fragenindex'];
    $currentQuestion = $fragen[$currentIndex];
    $selectedAnswer = isset($_POST['ausgewaehlte_antwort']) ? intval($_POST['ausgewaehlte_antwort']) : 0;

    if ($currentQuestion->ausgewaehlteAntwortID != $selectedAnswer) {
        saveOrUpdateAnswer($db, $nutzertoken, $currentQuestion->id, $selectedAnswer);
        $currentQuestion->ausgewaehlteAntwortID = $selectedAnswer;
    }

    $_SESSION['fragenindex']++;
    if ($_SESSION['fragenindex'] >= count($fragen)) {
        $_SESSION['fragenindex'] = 0;
        header('Location: danke.html');
        exit;
    }
}

$currentIndex = $_SESSION['fragenindex'];
$currentQuestion = $fragen[$currentIndex];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Umfrage</title>
</head>
<body>
    <h1>Umfrage</h1>
    <form action="index.php" method="post">
        <p><?php echo htmlspecialchars($currentQuestion->fragetext); ?></p>
        <ul>
            <?php foreach ($currentQuestion->moeglicheAntworten as $antwort): ?>
                <li>
                    <label>
                        <input type="radio" name="ausgewaehlte_antwort" value="<?php echo $antwort->id; ?>"
                            <?php echo $currentQuestion->ausgewaehlteAntwortID == $antwort->id ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($antwort->antworttext); ?>
                    </label>
                </li>
            <?php endforeach; ?>
            <li>
                <label>
                    <input type="radio" name="ausgewaehlte_antwort" value="0"
                        <?php echo $currentQuestion->ausgewaehlteAntwortID == 0 ? 'checked' : ''; ?>>
                    Keine Antwort
                </label>
            </li>
        </ul>
        <input type="submit" value="Nächste Frage">
    </form>
    <a href="index.php?action=results">Ergebnisse herunterladen</a>
</body>
</html>
