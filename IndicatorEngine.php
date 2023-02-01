<?php
const SITE_ROOT = __DIR__;

ini_set('memory_limit', '512M');
class IndicatorEngine
{
    private $dir;
    private $weeks = array();

    private $connections = array();

    public function __construct()
    {
        $this->dir = glob(SITE_ROOT."/traces/*");
        foreach($this->dir as $fileinfo){
            //_dump($fileinfo);
            $handle = fopen($fileinfo, "r");
            $json = fread($handle, filesize($fileinfo));
            $json_data = json_decode($json);
            //var_dump($json_data);
            $this->weeks[] = $json_data;
            fclose($handle);
        }
    }

    public function getAllUsers(): array
    {
        $users = array();
        foreach($this->weeks as $week) {
            foreach ($week as $trace) {
                $users[] = $trace->user;
            }
        }
        return array_unique($users);
    }
    function getNumberOfConnection($name): array
    {
        $connections = array();
        foreach($this->weeks as $week) {
            $array = array_filter($week, function($k) use ($name) {
                return $k->action->type == "Connexion" && $k->user == $name;
            });
            //var_dump($array);
            $count = count($array);
            $connections[] = $count;
        }
        //var_dump($connections);
        return $connections;
    }

    /**
     * @throws Exception
     */
    function getNumberOfConnectionByWeek($name, $numberOfWeek): array
    {
        $connections = array();
        /**/
        for($i = 0; $i <= $numberOfWeek; $i++) {
            $week = $this->weeks[$i];
            $connectionsUser = array_filter($week, function($k) use ($name) {
                return $k->action->type == "Connexion" && $k->user == $name;
            });
            //var_dump($connectionsUser);
            if($connectionsUser != null) {
                $firstDate = new DateTime(current($connectionsUser)->action->date);
            } else {
                $firstDate = new DateTime(current($week)->action->date);
            }
            $currentWeek = $firstDate->format("W");
            $connections["Semaine-$currentWeek"] = count($connectionsUser);
        }
        return $connections;
    }

    function getMessageActivityByWeeks($weekNumber): array
    {
        $users = $this->getAllUsers();
        $messageActivity = array();
        $weekKeys = array_keys($this->weeks);

        $sumMessageUsers = 0;
        foreach ($users as $user) {
            $sumMessage = 0;
            for($i = 0; $i <= $weekNumber; $i++) {
                $week = $this->weeks[$weekKeys[$i]];
                // Retrieve traces that only concerns messages interaction from name
                $arrayMessage = array_filter($week, function ($k) use ($user) {
                    return $k->action->category == "Operation sur les messages de communication" && $k->user == $user;
                });
                $sumMessage += count($arrayMessage);
            }
            $messageActivity[$user] = $sumMessage;
            $sumMessageUsers += $sumMessage;
        }
        $averageMessageActivity = $sumMessageUsers / count($users);
        $messageActivity["average"] = $averageMessageActivity;
        return $messageActivity;
    }

    function getMakerActivities($weekNumber): array
    {
        $users = $this->getAllUsers();
        $makerActivities = array();
        $weekKeys = array_keys($this->weeks);
        $sumActivity = 0;
        foreach ($users as $user) {
            for($i = 0; $i <= $weekNumber; $i++) {
                $week = $this->weeks[$weekKeys[$i]];
                // Retrieve traces that only concerns messages interaction from name
                $arrayMessage = array_filter($week, function ($k) use ($user) {
                    return $k->user == $user && $k->action->type == "Poster un nouveau message" || $k->action->type == "Upload un ficher avec le message";
                });
                $sumActivity += count($arrayMessage);
            }
            $makerActivities[$user] = $sumActivity;
        }
        return $makerActivities;
    }

    function getMessageActivityByName($name): array
    {
        $messageActivity = array();
        foreach ($this->weeks as $week) {
            // Retrieve traces that only concerns messages interaction from name
            $arrayMessage = array_filter($week, function ($k) use ($name) {
                return $k->action->category == "Operation sur les messages de communication" && $k->user == $name;
            });
            $forumMap = array();
            foreach($arrayMessage as $message) {
                $idForum = $message->trace->idForum;
                if(!isset($forumMap[$idForum])) {
                    $forumMap[$idForum] = 1;
                }
                $forumMap[$idForum]++;
            }
            $messageActivity[] = $forumMap;
        }
        //var_dump($messageActivity);
        return $messageActivity;
    }

    function getProductivity($connectionNumber, $forumArray): int
    {
        $sumPoints = 0;
        foreach($connectionNumber as $number) {
            $sumPoints += $this->computeConnectionNumber($number);
        }
        foreach($forumArray as $activity) {
            $sumPoints += $this->computeConnectionNumber($activity);
        }
        return $sumPoints;
    }

    function computeConnectionNumber($number): int
    {
        $score = 0;
        switch (true) {
            case in_array($number, range(0, 3)):
                $score = 0;
                break;
            case in_array($number, range(3, 7)):
                $score = 1;
                break;
            case in_array($number, range(7, 14)):
                $score = 2;
                break;
            case in_array($number, range(14, 42)):
                $score = 3;
                break;
            case $number > 42:
                $score = 4;
                break;
        }
        return $score;
    }

    function computeForumActivity($number): int
    {
        $score = 0;
        switch (true) {
            case in_array($number, range(0, 20)):
                break;
            case in_array($number, range(20, 50)):
                $score = 1;
                break;
            case in_array($number, range(50, 150)):
                $score = 2.5;
                break;
            case in_array($number, range(150, 300)):
                $score = 3.5;
                break;
            case $number > 300:
                $score = 5;
                break;
        }
        return $score;
    }

}