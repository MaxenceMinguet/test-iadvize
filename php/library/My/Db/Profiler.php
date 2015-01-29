<?php
/**
 * @package common
 * @subpackage My_Db
 * On écrit ici toutes les informations autour des requêtes de l'application
 */
class My_Db_Profiler extends Zend_Db_Profiler
{

    static public function render()
    {
        $config = Zend_Registry::get('config');
        if (false) {
            $config = new My_Config(array());
        }

        $db=Zend_Db_Table_Abstract::getDefaultAdapter();
        $profileur = $db->getProfiler();
        $tempsTotal       = $profileur->getTotalElapsedSecs();
        $nombreRequetes   = $profileur->getTotalNumQueries();
        $tempsLePlusLong  = 0;
        $requeteLaPlusLongue = null;

        // Volontairement on ignore les pages dynamiques qui générent CSS et JS
        if ( $profileur->getQueryProfiles() !== false ) {
            $sDuree = '';
            $sDureeLongue = '';
            foreach ($profileur->getQueryProfiles() as $query) {
                // On logue les requetes normales
                if ($query->getElapsedSecs() > $tempsLePlusLong) {
                    $tempsLePlusLong  = $query->getElapsedSecs();
                    $requeteLaPlusLongue = $query->getQuery();
                }
                $sDuree.='** '.$query->getElapsedSecs().' ** => ' .$query->getQuery() ."\n";

                $sDureeLongue.='** '.$query->getElapsedSecs().' ** => ' .$query->getQuery() ."\n";

            }

            $logger = new Zend_Log();
            $redacteur = new Zend_Log_Writer_Stream($config->folder->log.'/'.$config->name->fileProfiler);
            $logger->addWriter($redacteur);
            // chaîne ajoutée au log
            $logger->log(
                "\n".
                '----ATTENTION SI UN REDIRECT EST FAIT DANS VOTRE PAGE LES REQUETES NE SERONT PAS TRACEES----' . "\n".
                $_SERVER['REQUEST_URI']."\n".
                'Exécution de '.$nombreRequetes. ' requêtes en '.$tempsTotal.' secondes' . "\n".
                'Temps moyen : '.$tempsTotal / $nombreRequetes. ' secondes' . "\n".
                'Requêtes par seconde: '. $nombreRequetes / $tempsTotal. ' seconds' . "\n".
                'Requête la plus lente (secondes) : '.$tempsLePlusLong . "\n".
                "Requête la plus lente (SQL) : \n".
                $requeteLaPlusLongue . "\n".
                "detail de l'analyse\n".$sDuree."\n\n", Zend_Log::INFO
            );
        }
    }
}
