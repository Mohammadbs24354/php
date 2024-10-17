<?php

function getDbConnection()
{
    $db = new mysqli('localhost', 'root', '', 'umfrage');
    if ($db->connect_error) {
        die("Verbindung fehlgeschlagen: " . $db->connect_error);
    }
    return $db;
}

function loadQuestions($db)
{
    $result = $db->query("SELECT * FROM frage");
    $questions = [];
    while ($row = $result->fetch_object()) {
        $questions[$row->id] = $row;
        $questions[$row->id]->moeglicheAntworten = [];
        $questions[$row->id]->ausgewaehlteAntwortID = 0;
    }
    return $questions;
}

function loadPossibleAnswers($db, &$questions)
{
    $result = $db->query("SELECT * FROM moeglicheantwort");
    while ($row = $result->fetch_object()) {
        $questions[$row->frageid]->moeglicheAntworten[] = $row;
    }
}

function saveOrUpdateAnswer($db, $nutzertoken, $frageid, $antwortid)
{
    if ($antwortid == 0) {
        $db->query("DELETE FROM abgegebeneantwort WHERE nutzertokenid = $nutzertoken AND frageid = $frageid");
    } else {
        $result = $db->query("SELECT id FROM abgegebeneantwort WHERE nutzertokenid = $nutzertoken AND frageid = $frageid");
        if ($result->num_rows > 0) {
            $db->query("UPDATE abgegebeneantwort SET antwortid = $antwortid WHERE nutzertokenid = $nutzertoken AND frageid = $frageid");
        } else {
            $db->query("INSERT INTO abgegebeneantwort (nutzertokenid, frageid, antwortid) VALUES ($nutzertoken, $frageid, $antwortid)");
        }
    }
}

function getUserToken($db)
{
    if (!isset($_COOKIE['nutzertoken'])) {
        $db->query("INSERT INTO nutzertoken () VALUES ()");
        $token = $db->insert_id;
        setcookie('nutzertoken', $token, time() + (10 * 365 * 24 * 60 * 60)); // Cookie für 10 Jahre setzen
        return $token;
    }
    return $_COOKIE['nutzertoken'];
}

function loadUserAnswers($db, $nutzertoken, &$questions)
{
    $result = $db->query("SELECT frageid, antwortid FROM abgegebeneantwort WHERE nutzertokenid = $nutzertoken");
    while ($row = $result->fetch_object()) {
        $questions[$row->frageid]->ausgewaehlteAntwortID = $row->antwortid;
    }
}

function loadSurveyResults($db)
{
    $results = [];
    $questions = loadQuestions($db);
    loadPossibleAnswers($db, $questions);

    $totalUsers = $db->query("SELECT COUNT(*) AS total FROM nutzertoken")->fetch_object()->total;

    foreach ($questions as $frage) {
        $frageResults = [
            'fragetext' => $frage->fragetext,
            'antworten' => []
        ];

        foreach ($frage->moeglicheAntworten as $antwort) {
            $count = $db->query("SELECT COUNT(*) AS anzahl FROM abgegebeneantwort WHERE antwortid = $antwort->id")->fetch_object()->anzahl;
            $percentage = $totalUsers > 0 ? round($count / $totalUsers * 100, 2) : 0;
            $frageResults['antworten'][] = [
                'antworttext' => $antwort->antworttext,
                'anzahl' => $count,
                'prozent' => $percentage
            ];
        }

        $noAnswerCount = $totalUsers - array_sum(array_column($frageResults['antworten'], 'anzahl'));
        $noAnswerPercentage = $totalUsers > 0 ? round($noAnswerCount / $totalUsers * 100, 2) : 0;
        $frageResults['antworten'][] = [
            'antworttext' => 'Keine Antwort',
            'anzahl' => $noAnswerCount,
            'prozent' => $noAnswerPercentage
        ];

        $results[] = $frageResults;
    }

    return $results;
}
?>