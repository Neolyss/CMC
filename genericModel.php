<?php
    include_once('dc.inc.php');
    const SITE_ROOT = __DIR__;

    $db = new PDO("$server:host=$host;dbname=$database", $user, $pass);

    echo "<form action='genericModel.php' method='post'>
          <input type='submit' name='submit' value='GENERATE JSON'/>
    </form>";

    if(isset($_POST["submit"])) { // If the button is clicked
        // Generate
        $transitions_request = "SELECT * FROM `transition` ORDER BY Date, Heure;";

        $stmt = $db->prepare($transitions_request);
        $stmt->execute();

        $categories_request = "SELECT categorie.Titre AS categorieTitle, transition.Titre AS transitionTitle FROM `transition` LEFT JOIN activite ON transition.Titre = activite.Titre LEFT JOIN categorie ON activite.IDCat = categorie.IDCat GROUP BY transition.Titre;";

        $stmt_categories = $db->prepare($categories_request);
        $stmt_categories->execute();
        $categoriesResult = $stmt_categories->fetchAll();

        $categories = array(); // Creating the map of categories
        foreach ($categoriesResult as $category) {
            $transition_title = $category["transitionTitle"];
            $category_title = $category["categorieTitle"];

            if(isset($category_title)) {
                $categories[$transition_title] = $category_title;
            } else {
                $new_category_title = match ($transition_title) {
                    "Bouger la scrollbar en bas - afficher la fin du message", "Bouger la scrollbar en bas", "Afficher une structure (cours/forum)", "Afficher une structure du cours" => "Parcours du forum",
                    "Afficher le fil de discussion", "Citer un message", "Upload un ficher avec le message", "Download un fichier dans le message" => "Operation sur les messages de communication",
                    default => "Vide",
                };
                $categories[$transition_title] = $new_category_title;
            }
        }

        $temp_array_week = array();

        $firstRow = $stmt->fetch();
        $firstDate = new DateTime($firstRow["Date"]);
        $currentWeek = (int)$firstDate->format("W");
        $currentYear = (int)$firstDate->format("Y");

        $i=0;
        foreach ($stmt as $row) {
            $date = new DateTime($row["Date"]);
            $week = (int)$date->format("W");
            $year = (int)$date->format("Y");
            if($year > $currentYear) { // If year is changing
                $currentYear = $year;
                createFile($currentYear, $currentWeek, $temp_array_week);
            } else { // Same year
                if($week > $currentWeek) { // If week is changing
                    $currentWeek = $week;
                    createFile($currentYear, $currentWeek, $temp_array_week);
                } else { // Same week
                    $temp_array_week[] = createTrace($row, $categories);
                }
            }
            $i++;
        }
        var_dump($i);
    }

    function createTrace($row, $categories): array
    {
        $traceObject = array();
        $traceObject["traceId"] = $row["IDTran"];
        $traceObject["user"] = $row["Utilisateur"];
        $action = array();
        $action["type"] = $row["Titre"];
        $action["date"] = $row["Date"] . " " . $row["Heure"];
        $action["categorie"] = $categories[$row["Titre"]];
        $traceObject["action"] = $action;
        $trace = array();
        $split = explode(",",$row["Attribut"]);
        foreach ($split as $attribut) {
            $splitAttribut = explode("=", $attribut);
            switch ($splitAttribut[0]) {
                case "IDForum":
                    $trace["idForum"] = $splitAttribut[1];
                    break;
                case "IDMsg":
                    $trace["idMessage"] = $splitAttribut[1];
                    break;
                case "IDParent":
                    $trace["idParent"] = $splitAttribut[1];
                    break;
                default:
                    break;
            }
        }
        $traceObject["trace"] = $trace;
        return $traceObject;
    }

    function createFile($week, $year, $data): void
    {
        $myfile = fopen(SITE_ROOT."/traces/$week-$year.json", "w");
        fwrite($myfile, json_encode($data, JSON_PRETTY_PRINT));
        fclose($myfile);
    }
