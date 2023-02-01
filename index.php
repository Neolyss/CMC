<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <a href="genericModel.php"><button>Load Traces</button></a>
        <script src="//cdn.zingchart.com/zingchart.min.js"></script>
        <script src="./zingchart-bubble-pack.min.js"></script>
        <h1>Investissement de chaque personne sur un mois</h1>
        <div id="connection"></div>
        <div id="messages"></div>
        <div id="participation"></div>
        <?php

        include 'ZC.php';
        include 'IndicatorEngine.php';
        use ZingChart\PHPWrapper\ZC;

        $indicator = new IndicatorEngine();

        $users = $indicator->getAllUsers();
        //var_dump($users);

        $series = array();

        $seriesConnection = array();
        $keysUser = array_keys($users);
        $userLegend = array();
        for($i = 0; $i <= 5; $i++) {
            $user = $users[$keysUser[$i]];
            $userLegend[] = $user;
            try {
                $connectionNumber = $indicator->getNumberOfConnectionByWeek($user, 4);
                $seriesConnection[] = array("values" => array_values($connectionNumber));
            } catch (Exception $e) {
            }
        }

        $messageActivity = $indicator->getMessageActivityByWeeks(4);
        $makerActivities = $indicator->getMakerActivities(4);
        $makerActivitiesValue = array_values($makerActivities);
        $average = array_sum($makerActivitiesValue)/count($makerActivitiesValue);
        $et = 0;
        foreach ($makerActivitiesValue as $v){
            $et += pow(($v - $average), 2);
        }
        $et = $et / (count($makerActivitiesValue) - 1);
        $standardDeviation = pow($et, 1/2);

        $underSD = array_filter($makerActivities, function ($value) use ($average, $standardDeviation) {
            return $value < $average - $standardDeviation;
        });
        $averageSD = array_filter($makerActivities, function ($value) use ($average, $standardDeviation) {
            return $value >= $average - $standardDeviation && $value < $average + $standardDeviation;
        });
        $moreSD = array_filter($makerActivities, function ($value) use ($average, $standardDeviation) {
            return $value > $average + $standardDeviation;
        });

        $series[] = array("id" => "global", "parent" => "", "name" => "Global", "group" => "");

        $series[] = array("id" => "Under", "parent" => "global", "name" => "Créer peu de contenu", "value" => array_sum($underSD));
        foreach($underSD as $key => $value) {
            $series[] = array("id" => $key, "parent" => "Under", "name" => $key, "group" => "under", "value" => $value);
            $seriesUnder[] = array("id" => $key, "parent" => "Under", "name" => $key, "group" => "under", "value" => $value);
        }

        $series[] = array("id" => "Average", "parent" => "global", "name" => "Normal", "value" => array_sum($averageSD));
        foreach($averageSD as $key => $value) {
            $series[] = array("id" => $key, "parent" => "Average", "name" => $key, "group" => "average", "value" => $value);
            $seriesAverage[] = array("id" => $key, "parent" => "Average", "name" => $key, "group" => "under", "value" => $value);
        }

        $series[] = array("id" => "More", "parent" => "global", "name" => "Créer beaucoup de contenu", "value" => array_sum($moreSD));
        foreach($moreSD as $key => $value) {
            $series[] = array("id" => $key, "parent" => "More", "name" => $key, "group" => "more", "value" => $value);
            $seriesMore[] = array("id" => $key, "parent" => "More", "name" => $key, "group" => "under", "value" => $value);
        }

        
        $zc = new ZC("participation");
        $zc->setConfig("type", "bubble-pack");
        $zc->setConfig("plotarea", array("margin" => 50));
        $zc->setTitle("Participation à la création de contenu sur un mois");
        $zc->setConfig("series", $series);
        $zc->setChartHeight("500px");
        $zc->setChartWidth("100%");
        $renderScript = $zc->getRenderScript();
        echo $renderScript;

        $zc2 = new ZC("connection");
        $zc2->setTitle("Fréquence de connexion par utilisateur sur un mois");
        $zc2->setConfig("type", "line");
        $zc2->setConfig("series", $seriesConnection);
        $zc2->setSeriesText($userLegend);
        $zc2->setChartHeight("400px");
        $zc2->setChartWidth("100%");
        $renderScript = $zc2->getRenderScript();
        echo $renderScript;

        $zc3 = new ZC("messages");
        $zc3->setTitle("Nombre de messages envoyés par utilisateur sur un mois");
        $zc3->setChartType("line");
        $average = $messageActivity["average"];
        unset($messageActivity["average"]);
        $averages = array();
        for($i = 0; $i <= count($users); $i++) {
            $averages[] = $average;
        }
        $zc3->setSeriesData(0, array_values($messageActivity));
        $zc3->setConfig("plot", array("tooltip" => array("text"=> "%t", "connectNulls" => "true",)));
        $zc3->enableTooltip();
        $zc3->setSeriesData(1, $averages);
        $zc3->setChartHeight("400px");
        $zc3->setChartWidth("100%");
        $renderScript = $zc3->getRenderScript();
        echo $renderScript;


    ?>
    </body>
</html>

