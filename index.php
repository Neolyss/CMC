<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <a href="genericModel.php"><button>Load Traces</button></a>
        <script src="//cdn.zingchart.com/zingchart.min.js"></script>
        <script src="./zingchart-bubble-pack.min.js"></script>
        <h1>Taux de productivité par personne</h1>
        <div id="myChart"></div>
        <?php

        include 'ZC.php';
        include 'IndicatorEngine.php';
        use ZingChart\PHPWrapper\ZC;

        $indicator = new IndicatorEngine();

        $users = $indicator->getAllUsers();
        //var_dump($users);

        $series = array();
        $series[] = array("id" => "global", "parent" => "", "name" => "Global", "group" => "");
        //$series[] = array("id" => "test", "parent" => "global", "name" => "Test", "group" => "data", "value" => "5733551");

        foreach ($users as $user) {
            $connectionNumber = $indicator->getNumberOfConnection($user);
            $messageActivity = $indicator->getMessageActivityByName($user);
            $score = $indicator->getProductivity($connectionNumber, $messageActivity);
            $series[] = array("id" => $user, "parent" => "global", "name" => $user, "group" => "data", "value" => $score);
        }
        
        $zc = new ZC("myChart");
        $zc->setConfig("type", "bubble-pack");
        $zc->setConfig("series", $series);
        $zc->setChartHeight("400px");
        $zc->setChartWidth("100%");
        $renderScript = $zc->getRenderScript();

        echo $renderScript;
    ?>
    </body>
</html>

