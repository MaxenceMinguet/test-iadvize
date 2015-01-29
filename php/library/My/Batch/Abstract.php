<?php
/**
 * Classe utile lors de la création des batchs
 * @package batch
 */

declare(ticks=1);

/**
 * Mélange le Zend_Log et le Zend_Console_Opt
 * L'utilisation de cette classe oblige à certains comportements
 * lancer le batch avec le paramètre "a" pour l'execution
 * renseigner l'aide
 *
 * Note le paramètre destinataire peut être ajouter à vos paramètres
 * Il permet de recevoir un valeur.
 * C'est dans la classe que vous créez que vous utiliserait la méthode
 * getDestinataires/ c'est elle qui décide de faire la conversion de vos emails
 *
 *
 * La classe utilise le fichier de configuration common-batch.ini
 * Paramètres utilisés :
 *  nagios : Adresse des serveurs nagios à contacter
 *  backup : serveur de backup ? Définit l'éxécution du batch ou passe le traitement
 *
 * H-touch : Cet classe utilise le application.ini de bo
 *
 * @package batch
 */
abstract class My_Batch_Abstract extends Zend_Console_Getopt
{
    /**
     * Est-ce qu'on affiche à l'écran ce qui est loggué ?
     * @param string
     */
    private $b_trace_ecran=false;

    /**
     * Nombre de secondes d'attente entre deux exécutions lorsque l'option
     * --loop est précisée (-1 = pas de boucle).
     * @param integer
     */
    private $loop_every=-1;

    /**
     * Nom du fichier de log utilisé
     * @var string
     */
    private $s_nom_fichier_log = '';

    /**
     * Variable interne ne servant qu'à maintenir un fichier ouvert sur une
     * redirection de stdout vers le fichier de log $s_nom_fichier_log
     * @var file
     */
    private $stdout_to_log = null;

    /**
     * Variable interne ne servant qu'à maintenir un fichier ouvert sur une
     * redirection de stderr vers le fichier de log $s_nom_fichier_log
     * @var file
     */
    private $stderr_to_log = null;

    /**
     * zend logger on utilise les loggers de zend afin de gérer des traces
     * @var zend_log
     */
    private $logger = null;

    /**
     * niveau d'erreur courant de l'objet.
     * Cette valeur ne peut pas descendre.
     * @var integer
     */
    private $niveau_erreur = Zend_Log::INFO;

    /**
     * Objet Zend_Db_Table pour manipuler la table des résultats
     * @var Database_Batch
     */
    private $db_table_batch = null;

    /**
     * ressource de connection à la bdd
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db = null;

    /**
     * Object de traduction de Zend_Translate
     * @var Zend_Translate
     */
    protected  $translate = null;

    /**
     * A l'aide de l'environement il va préparer le lancement du batch
     */
    final public function __construct()
    {
        $s_dossier_application = realpath(dirname(__FILE__).'/../../..');

        $this->s_nom_fichier_log = $this->getConfig()->htouch->folder->log.$this->getNomBatch().'.log';
        // objet de traduction generique

        // recupération de la connexion à la bdd
        $this->db = Zend_Db_Table::getDefaultAdapter();

        // contrôle que nous sommes bien dans un contexte de batch et pas dans une page web
        // on ne va pas plus loin
        if ( $_SERVER["argc"] == 0 )
        {
            throw new Exception('Script en mode cli uniquement');
        }

        parent::__construct($this->getParamsTous());
        pcntl_signal(SIGTERM, array($this, "killed"));
        pcntl_signal(SIGINT, array($this, "killed"));

        try
        {
            // trace à l'écran??
            if ( $this->getOption('verbose') )
            {
                if ( $this->getOption('daemon') ) {
                    fprintf(STDERR, "ATTENTION: Option --verbose ignorée car elle ne peut pas être utilisée avec --daemon\n");
                }
                else {
                    $this->b_trace_ecran=true;
                }
            }

            // aide
            if ( $this->getOption('help') )
            {
                echo $this->getUsageMessage();
                exit;
            }

            // mise en place du profiler
            if ( $this->getOption('profiling') )
            {
                $this->db->getProfiler()->setEnabled(true);
            }

            // Récupération du paramètre de rebouclage
            if ( isset($this->loop) ) {
                $this->loop_every = $this->loop === true ? 0 : $this->getOption('loop');
            }

            // Un nouveau nom de service
            if ( $this->getOption('service') ) {
                $this->s_service_choix = $this->getOption('service');
            }

            // contrôle des paramètres
            if ( sizeof($this->getParams()) != 0 )
            {
                $this->controleParam();
            }

        }
        catch (Zend_Console_Getopt_Exception $e)
        {
            fprintf(STDERR, "ERREUR: Commande non comprise\n\n%s\n", $this->getUsageMessage());
            exit(1);
        }

        // controle de l'utilisateur
//         $a_myself = posix_getpwuid(posix_geteuid());


//         if ( $this->getConfig()->htouch->batch->user !=  $a_myself['name'] )
//         {
//             fprintf(STDERR, "ERREUR: le script s'exécute sous l'utilisateur ".$a_myself['name']."\nSeul l'utilisateur ".$this->getConfig()->htouch->batch->utilisateur." a le droit d'exécuter les batchs.\n\n");
//             exit(2);
//         }

        $this->activationLogger();
        $this->ajoutTrace("LANCEMENT DU BATCH sous la forme '".implode(' ',$_SERVER['argv'])."'",Zend_Log::INFO);

        // Mode daemon ?
        if ( $this->getOption('daemon') ) {
            $this->goToBackground();
        }

        // lock le batch pour éviter qu'il ne soit lancer plusieurs fois
        if (! $this->lock())
        {
            $this->ajoutTrace("ERREUR: le verrou a déjà été posé",Zend_Log::WARN);
            exit(2);
        }

        $batch_start_time = time();

        do
        {
            if ($this->loop_every > 0)
            {
                $run_start_time = time();
                $time_of_next_run = $run_start_time + $this->loop_every - ( ($run_start_time - $batch_start_time) % $this->loop_every );
                # $this->ajoutTrace("batch_start_time=$batch_start_time ; run_start_time=$run_start_time ; time_of_next_run=$time_of_next_run", Zend_Log::DEBUG);
            }

            try
            {
                $this->ajoutTrace('DEBUT DU RUN', Zend_Log::INFO, true);

                // si on souhaite notifier on ajoute une trace en bdd
                if ( $this->getOption('notify'))
                {
                    // création de la ligne départ du batch
                    $data['nom_batch']   = $this->getNomBatch();
                    $data['nom_machine'] = $this->getNomMachine();
                    $data['argument']    = implode(' ',$_SERVER['argv']);
                    $data['pid']         = getmypid();
                    $data['tmstp_debut'] = $this->dateCourante();
                    $data['nom_service'] = $this->getServiceChoix();

                    // manipulation de la table batch
                    try {
                        $this->db_table_batch = new My_Db_Table_Oct2_Batch();

                        // récupération de l'id_batch, permet de tracer plus finement un batch
                        $this->ajoutTrace("Recuperation id_batch",Zend_Log::INFO);
                        $this->setId_Batch($this->db_table_batch->insert($data));
                    }
                    catch (Zend_Db_Table_Exception $e)
                    {
                        fprintf(STDERR, $this->getConfig()->htouch->dossier->cache_volatile.' '.$e->getMessage()."\n");
                        exit(2);
                    }
                }
                $this->run();

                // cloture le batch appelle url nagios
                $this->finTrace('FIN DU RUN');

            }
            catch (Exception $e)
            {
                // on ajoute une trace avec un niveau élevé.
                $this->ajoutTrace($e->getMessage(),Zend_Log::ALERT);
                $this->ajoutTrace($e->getTraceAsString(),Zend_Log::ALERT);

                // on prévient le system d'alerte
                $this->alert($e->getMessage());

            }

            // retourn l'analyse des requêtes du batch
            if ( $this->getOption('profiling') )
            {
                $this->profiling();
            }

            /* En mode loop, si on a fini avant l'heure du prochain tour de
             * boucle, on fait un somme. Sinon, on repart immédiatement.
            */
            if ($this->loop_every > 0)
            {
                $run_end_time = time();
                $duration_of_last_run = $run_end_time - $run_start_time;

                if ($duration_of_last_run >= $this->loop_every)
                {
                    $this->ajoutTrace("ATTENTION: durée de la boucle précédente $duration_of_last_run s >= $this->loop_every s", Zend_Log::WARN);
                }

                $s_time_of_next_run = date('r', $time_of_next_run);
                if ($run_end_time >= $time_of_next_run)
                {
                    $this->ajoutTrace("Pas de SLEEP car on a passé l'heure du prochain tour de boucle ($s_time_of_next_run)", Zend_Log::INFO);
                }
                else
                {
                    $dodo = $time_of_next_run - $run_end_time;
                    // On dort $dodo secondes ou jusqu'à ce qu'on reçoive un signal SIGINT (graceful stop)
                    while ($dodo > 0 && $this->loop_every > 0)
                    {
                        $this->ajoutTrace("SLEEP($dodo) jusqu'à $s_time_of_next_run");
                        $dodo = sleep($dodo);
                    }
                }
            }
        }
        while ($this->loop_every >= 0);

        $this->ajoutTrace('FIN DU BATCH');

        // retrait du lock
        $this->unlock();
    }
    /**
     * elle recupère le contenu généré par un module / controleur / action
     * et le retourne sous forme d'une variable
     *
     * @return string
     */
    final public function getAction($a_param)
    {

        $action     = $a_param['action'];unset($a_param['action']);
        $controller = $a_param['controller'];unset($a_param['controller']);
        $module     = $a_param['module'];unset($a_param['module']);

        $request = new Zend_Controller_Request_Simple($action,$controller,$module,$a_param);
        $front = Zend_Controller_Front::getInstance();

        $front->setRequest($request);
        $front->setRouter(new Controller_Router_Cli());

        $front->setResponse(new Zend_Controller_Response_Cli());

        $front->throwExceptions(true);
        $front->addModuleDirectory(realpath(dirname(__FILE__).'/../../../regie/'));
        // capture le buffer.
        ob_start();
        $front->dispatch();
        $page = ob_get_contents();
        ob_end_clean();
        return $page;
    }


    /**
     * ajoute une trace dans le fichier de log avec un niveau d'erreur
     * si le niveau d'erreur est monté, il ne peut plus redescendre
     * sauf si $force est positionné à true.
     * @param string $s_message
     * @param integer $niveau
     * @param boolean $force
     */
    protected function ajoutTrace($s_message,$niveau=null, $force=false)
    {
        // construction de la ligne ajout de quelques informations supplémentaires
        $s_ligne='['.str_pad(getmypid(), 5, ' ', STR_PAD_LEFT)."] ".str_pad($this->getId_Batch(), 10)." ".$s_message;


        if ( $niveau != null )
        {
            // si le niveau d'erreur est changé on le mets à jour
            $this->setNiveau_Erreur($s_message,$niveau, $force);
        }
        // ajout de la trace au système de logger
        $this->getLogger()->log($s_ligne,$this->getNiveau_Erreur());
    }
    /**
     * termine l'enregistrement des logs
     *
     * @param integer $niveau Niveau syslog d'erreur
     */
    private function finTrace($s_trace,$b_systeme_alerte=true)
    {
        $this->ajoutTrace($s_trace);

        // message envoyé à la base plus riche si possible
        if ( $this->message_alert != '' )
        {
            $s_trace = $this->message_alert;
        }

        // si le système de notification est activé, on termine par une trace en bdd
        if ( $this->getOption('notify') )
        {
            $data['tmstp_fin']          = $this->dateCourante();
            $data['erreur_niveau']      = $this->getNiveau_Erreur();
            $data['erreur_commentaire'] = $s_trace;
            $where = 'id_batch = '.$this->getId_Batch();
            $this->db_table_batch->update($data,$where);
        }

        // pour plus de lisibilité l'appel au système de supervision est externalisé dans une procédure
        if ( $b_systeme_alerte )
        {
            $this->alert();
        }
        else
        {
            $this->ajoutTrace("Système d'alerte non prévenu.");
        }
    }


    /**
     * retoune la date du moment à insérer dans la base
     * @return string 2008-12-31 14:18:09 le format retourné
     */
    private function dateCourante()
    {
        return date("Y-m-d H:i:s O");
    }
    /**
     * enregistre le numéro de ligne affecté au batch
     *
     * @param integer $id
     * @return void
     */
    private function setId_Batch($id)
    {
        $this->id_batch=$id;
    }
    /**
     * retourne l'id qui a été affecté à ce batch dans la bdd
     *
     * @return integer
     */
    private function getId_Batch()
    {
        return $this->id_batch;
    }

    /**
     * modifie le niveau d'erreur courant de l'objet si cette valeur a été augmentée.
     *
     * @param integer $niveau_erreur
     * @param boolean $force
     * @return boolean
     */
    private function setNiveau_Erreur($s_message,$niveau_erreur,$force=false)
    {
        // le niveau d'erreur ne peut pas redescendre, sauf si $force vaut true
        if ( !$force && $this->niveau_erreur < $niveau_erreur)
        {
            return false;
        }

        // si nous rentrons dans un niveau d'erreur particulier, nous mettons le premier message du niveau d'erreur le plus important de côté
        // c'est ce message qui sera envoyé à l'outil de monitoring.
        $this->message_alert=$s_message;

        $this->niveau_erreur=$niveau_erreur;
        return true;
    }

    /**
     * fonction pour changer le message d'alert à envoyer à nagios Ce message est remplacé si l'on change le niveau d'erreur.
     * @param string $s_trace message à envoyer au serivice d'alerte
     * @return void
     */

    protected function setMessage_Alert($s_trace) {
        // si nous rentrons dans un niveau d'erreur particulier, nous mettons le premier message du niveau d'erreur le plus important de côté
        // c'est ce message qui sera envoyé à l'outil de monitoring.
        // suppression des retours chariots
        $s_trace = preg_replace('/\s+/', ' ', $s_trace);
        $s_trace = preg_replace('/[^A-Za-z0-9_\.,\/:@ !-]/', ' ', $s_trace);

        $this->message_alert=$s_trace;
    }

    /**contient le premier message du niveau d'alerte le plus élevé.
     * @var string
    */
    private $message_alert = null;

    /**
     * retourne le niveau d'erreur courant
     * @return integer
     */
    private function getNiveau_Erreur()
    {
        return $this->niveau_erreur;
    }

    /**
     * stocke le numéro de ligne qui a enregistré le début du batch
     * @var integer
     */
    private $id_batch;

    /**
     * retourne l'état du mode ecran
     * @return boolean
     */
    private function getB_Trace_Ecran()
    {
        return $this->b_trace_ecran;
    }

    /**
     * logger utilisé
     * @param Zend_Log $logger
     */
    private function setLogger($logger)
    {
        $this->logger=$logger;
    }
    /**
     * retourne le logger courant
     * @return Zend_Log
     */
    private function getLogger()
    {
        return $this->logger;
    }
    /**
     * retourne l'objet de config de la classe
     * lève une exception si le fichier n'est pas correcte
     * @return Zend_Config_Ini
     */
    protected function getConfig()
    {
        if ( !is_null($this->config) )
        {
            return $this->config;
        }
        $this->config = Zend_Registry::get('config');
        // mise en place dans la registry pour la partie ecriture des urls
        return $this->config;
    }
    /**
     * propriété contentant l'objet de configurationd de la classe
     * @var Zend_Config_Ini
     */
    private $config = null;

    /**
     * activation de la politique de logger
     * @param boolean $b_trace_ecran activation de la trace ecran
     */
    private function activationLogger()
    {
        // outil de tracage
        // on utilise le Zend framework pour cela
        $logger = new Zend_Log();

        // trace ecran synchro
        if ( $this->b_trace_ecran )
        {
            $redacteur3 = new Zend_Log_Writer_Stream('php://output');
            $logger->addWriter($redacteur3);
        }

        // trace fichier texte
        try
        {
            $redacteur4 = new Zend_Log_Writer_Stream('/tmp/'.$this->getNomBatch().'.log');
        }
        catch (Zend_Log_Exception $e)
        {
            fprintf(STDERR, 'Problème dans le fichier de log '.$this->s_nom_fichier_log.".\n");
            exit(2);
        }
        // en mode écran synchro on redirige vers l'ecran
        $logger->addWriter($redacteur4);

        // logger de la classe
        $this->setLogger($logger);
    }


    /**
     * nom complet de la machine
     * @var string
     */
    private $nom_machine;

    /**
     * retourne le nom complet de machine
     * @return string retourne l'information de la commande système
     */
    protected function getNomMachine()
    {
        if ( $this->nom_machine != '' )
        {
            return $this->nom_machine;
        }
        $this->nom_machine = exec('hostname --long');
        return $this->nom_machine;
    }
    /**
     * permet d'appeler le système d'alerte extérieur
     * pose des jalons permet de mesurer la durée d'excution de certaines séquences
     *
     */
    protected function alert($s_trace = null)
    {
        if ( !$this->getOption('notify'))
        {
            $this->ajoutTrace("Aucune notification au système d'alerte");
            return;
        }

        $this->ajoutTrace('previent le systeme alerte');

        if ( $s_trace !== null ) {
            $this->setMessage_Alert($s_trace);
            if(empty($this->message_alert) or ! preg_match('/^[A-Za-z0-9_\.,\/:@ !-]+$/', $this->message_alert))
            {
                $this->setMessage_Alert('souci');
            }
        }
        $a_nagios = explode(',', $this->getConfig()->environment->nagios);

        // nous prévenons différents système d'alerte.
        // si l'un d'entre eux échoue, nous notons l'information
        // à la fin du parcours des urls, nous laisserons une trace

        $appel_nagios_ok = true;
        foreach ($a_nagios as $nagios_srv) {
            $url_nagios=$this->conversionUrlNagios($nagios_srv, $this->getServiceChoix(),$this->getNiveau_Erreur(),$this->getNomMachine(),$this->message_alert);

            $timeout = 15;
            $old_timeout = ini_set('default_socket_timeout', $timeout);
            // l'ouverture de l'url c'est elle bien passé
            if ( !@fopen($url_nagios, "r") ) {
                $this->ajoutTrace('Impossible de prévenir le serveur : '.$nagios_srv.' (timeout '.$timeout.'s)'.' (url '.$url_nagios.')');
                $appel_nagios_ok = false;
            } else {
                $this->ajoutTrace('Mise à jour du serveur nagios : '.$nagios_srv.' (url '.$url_nagios.')');
            }

            ini_set('default_socket_timeout', $old_timeout);
        }

        // un erreur a été rencontré dans la mise à jour des alertes nagios nous laissons une trace.
        if(!$appel_nagios_ok)
        {
            // on change le niveau d'erreur
            $this->ajoutTrace('Erreur lors de la mise à jour d au moins un serveur Nagios',Zend_Log::WARN);
        }
    }

    /**
     * Construction de l'url Nagios
     *
     * @param string $service
     * @param string $niveau_erreur
     * @param string $nom_machine
     * @param string $commentaire
     * @return string url nagios du batch
     */
    private function conversionUrlNagios($nagios_srv, $service,$niveau_erreur,$host,$comment)
    {
        // recuperation du nom de machine
        $host=str_replace ('.hi-media-techno.com','',$host);
        // conversion du niveau d'erreur syslog en niveau d'erreur nagios
        switch ($niveau_erreur)
        {
            case Zend_Log::DEBUG :
            case Zend_Log::INFO :
            case Zend_Log::NOTICE :
                $state='ok';
                break;
            case Zend_Log::WARN :
                $state='warning';
                break;
            case Zend_Log::EMERG :
            case Zend_Log::ALERT :
            case Zend_Log::CRIT :
            case Zend_Log::ERR :
                $state='critical';
                break;
            default:
                $state='unknown';
                break;
        }
        $base_url='http://guest:guest@'.$nagios_srv.'/nagios3/nsca.php?';
        $a_param=array (
                'service' => $service,
                'host' => $host,
                'state' => $state,
                'comment' => $comment
        );

        foreach ( $a_param as $param => $valeur )
        {
            $a_chaine[]=$param.'='.urlencode($valeur);
        }

        return $base_url.implode('&',$a_chaine);
    }
    /**
     * mise en place du lock sur le batch.
     * un batch ne peut être lancé qu'une seule fois
     *
     */
    private function lock()
    {
        $this->ajoutTrace("Mise en place du lock");
        // il n'est pas possible de mettre les locks dans un dossier au sein de lock.
        // tous les dossiers sont supprimés au redémarrage de la machine.
        // on utilise le nom du service du batch comme clé de lock

        $lockfile=$this->getConfig()->htouch->folder->lock.'/lock_'.$this->getService();
		
		exec('dotlockfile -c '.$lockfile, $output, $returnVar);

        if ( !is_dir($this->getConfig()->htouch->folder->lock))
        {
            throw new Exception('Problème configuration lock dossier : '.$this->getConfig()->htouch->dossier->lock);
        }

        // déjà lancé?
        if ( $returnVar === 0 ) {
                return false;
            exit;
        }
        exec('dotlockfile -l '.$lockfile, $output, $returnVar);

        // le lock est en place alors nous arrêtons l'execution du programme
        return (true);
    }
    private function unlock()
    {
        $this->ajoutTrace("Suppression du lock");
        // il n'est pas possible de mettre les locks dans un dossier au sein de lock.
        // tous les dossiers sont supprimés au redémarrage de la machine.
        // on utilise le nom du service du batch comme clé de lock
        $lockfile=$this->getConfig()->htouch->folder->lock.'/lock_'.$this->getService();
        system("dotlockfile -u $lockfile", $rc);
    }

    /**
     * retourne la liste des paramètres obligatoires pour un batch
     * @return array la forme attendue par Zend_Console_Getopt dans son constructeur
     */
    static final public function getParametreObligatoire()
    {
        return array(
                'daemon'		=> "lance le script en mode démon (arrière-plan)",
                'help|h'		=> "affiche l'aide intégrée",
                'loop-i'		=> "lance le script continuellement [toutes les N secondes]",
                'notify|n'		=> "active la notification au système d'alerte. Utilisation pour les crons",
                'profiling|p'	=> "active le profiling du batch",
                'service|s=s'	=> "surcharge le nom du système d'alerte",
                'verbose|v'		=> "affiche à l'écran les informations logguées par le serveur.",
        );
    }

    /**
     * retourne la liste complète des paramètres du batch
     * @return array la forme attendue par Zend_Console_Getopt dans son constructeur
     */
    public function getParamsTous()
    {
        $a_param = array_merge(My_Batch_Abstract::getParametreObligatoire(), $this->getParams(), array('help|h'=>$this->getHelp()));
        // tri par ordre de clé pour avoir une régularité dans tous les batchs
        ksort($a_param);
        return $a_param;
    }

    /**
     * fonction chargée de faire le profil des requêtes du batch
     */
    protected function profiling()
    {
        $db=Zend_Db_Table::getDefaultAdapter();
        $profileur = $db->getProfiler();
        $tempsTotal       = $profileur->getTotalElapsedSecs();
        $nombreRequetes   = $profileur->getTotalNumQueries();
        $tempsLePlusLong  = 0;
        $requeteLaPlusLongue = null;
        if ( $profileur->getQueryProfiles() !== false )
        {
            $s_duree='';
            foreach ($profileur->getQueryProfiles() as $query)
            {
                if ($query->getElapsedSecs() > $tempsLePlusLong)
                {
                    $tempsLePlusLong  = $query->getElapsedSecs();
                    $requeteLaPlusLongue = $query->getQuery();
                }
                $s_duree.='** '.$query->getElapsedSecs().' ** => ' .substr($query->getQuery(),0,60) ."\n";
            }

            echo 'Exécution de '.$nombreRequetes. ' requêtes en '.$tempsTotal.' secondes' . "\n";
            echo 'Temps moyen : '.$tempsTotal / $nombreRequetes. ' secondes' . "\n";
            echo 'Requêtes par seconde: '. $nombreRequetes / $tempsTotal. ' seconds' . "\n";
            echo 'Requête la plus lente (secondes) : '.$tempsLePlusLong . "\n";
            echo "Requête la plus lente (SQL) : \n"
            . substr($requeteLaPlusLongue,0,60) . "\n";
            echo "detail de l'analyse\n";
            echo $s_duree;
        }
        else
        {
            print('aucune requete');
        }

        $profileur->clear();
    }

    /**
     * retourne le dossier de cache du batch
     * @return string
     */
    protected function getCacheFolder()
    {
        //creation du dossier de stockage
        $s_dossier = $this->getConfig()->htouch->dossier->cache.'/batch/';
        $s_dossier.= str_replace('_','/',$this->getNomBatch());
        // création du dossier
        @mkdir($s_dossier, 0777, TRUE);
        return $s_dossier . '/';
    }
    /**
     * choix du service à notifier
     *
     * @var string
     */
    private $s_service_choix=null;
    /**
     * mécanisme de contrôle si le nom du service n'a pas été surchargé
     * @return string
     */
    private function getServiceChoix()
    {
        if ( $this->s_service_choix === null)
        {
            return $this->getService();
        }
        return $this->s_service_choix;
    }

    /**
     * Une aide en francais
     * @return string
     */
    public function getHelp() {
        return $this->getService();
    }

    /**
     * Passe le batch en arrière-plan (mode daemon)
     *
     * @note Code hérité de SystemDaemon.php de skytools/pgq-php
     */
    public function goToBackground()
    {
        $pid = pcntl_fork();
        if ($pid < 0) {
            fprintf(STDERR, "ERREUR: fork failure: %s\n", posix_strerror(posix_get_last_error()));
            exit(2);
        }
        else if ( $pid ) {
            // Le processus père se termine ici et passe la main au processus fils
            $this->ajoutTrace("Activation du mode daemon => je passe la main au process d'arrière-plan de PID $pid", Zend_Log::INFO);
            exit;
        }

        /*
         *	Processus fils
        */
        // On se crée un process group
        if ( posix_setsid() < 0 ) {
            fprintf(STDERR, "ERREUR: setsid() failure: %s\n", posix_strerror(posix_get_last_error()));
            exit(2);
        }

        /*
         * On ferme STDIN et on redirige STDOUT et STDERR vers le fichier de
        * log au cas où il reste des echo, des print ou des fprintf(STDERR)
        * qui traînent (sans cela, le programme va s'arrêter sans un mot sur
                * un EBADF). Comme PHP ne semble pas avoir de dup/dup2() ou freopen(),
        * on simule la chose en ouvrant le fichier de log juste après la
        * fermeture, et on maintient le fichier ouvert en référençant l'objet
        * avec des attributs de $this. Pas sûr que ce soit très fiable... X
        */
        /*
         $this->stdout_to_log = fopen($this->s_nom_fichier_log, 'a');

        if(!$this->stdout_to_log){
        fprintf(STDOUT, "ERREUR: fopen(".$this->s_nom_fichier_log."): failed to open stream: Permission denied \n");
        exit(2);
        }

        fclose(STDOUT);
        define("STDOUT", $this->stdout_to_log);
        fclose(STDERR);
        $this->stderr_to_log = fopen($this->s_nom_fichier_log, 'a');
        define("STDERR", $this->stderr_to_log);
        fclose(STDIN);
        chdir('/tmp');
        */
    }

    /**
     * Contrôle l'ensemble des paramètres attendus

     */
    protected function controleParam()
    {
        // dans certains cas, notamenet les batchs qui envoient des mails,
        // ce paramètre permet de rediriger le contenu vers un email passé en paramètre
        // @see My/Batch/My_Batch_Abstract::getDestinataires($destinataires)
        if ( $this->getOption('destinataire') ) {
            $o_zend_validate_email = new Zend_Validate_EmailAddress();
            if ( !$o_zend_validate_email->isValid($this->getOption('destinataire')) ) {
                echo $this->getUsageMessage();
                exit;
            }
        }
    }

    /**
     * Réception des signaux
     */
    public function killed($sig)
    {
        switch($sig) {
            case SIGINT:
                $this->ajoutTrace("Arrêt grâcieux demandé via le signal SIGINT, je finis mon tour de boucle");
                $this->loop_every = -1;
                break;

            case SIGTERM:
                $this->ajoutTrace("Arrêt immédiat sur réception du signal SIGTERM");
                exit();
                break;

            default:
                $this->ajoutTrace("Arrêt immédiat sur réception du signal $sig que je ne sais pas traiter");
                exit();
        }
    }

    /**
     * Retourne la syntaxe et l'aide nécessaire à ce type d'option
     * à utiliser pour ajouter ce paramètre
     */
    public function getParamDestinataire(){
        return  array('destinataire=s'	=> "Redirige tous les mails envoyés par ce batch sur le mail en paramètre (Utilisé pour des tests)");
    }

    /**
     * Renvoie les destinataires soit à partir des paramètres,
     * soit  à partir de la fonction fille
     * @param string|array destinataires définis par le batch
     * @return string|array (en fonction du paramètre d'entrée) avec les destintaires, surchargés au besoin
     */
    protected function getDestinataires($destinataires)
    {
        // Le premier argument doit être de type array ou string
        if(!is_array($destinataires) && !is_string($destinataires))
        {
            trigger_error('Le premier argument doit être de type array ou string', E_USER_ERROR);
        }

        // Si le paramètre destinataire a été donné en ligne de commande, on utilise ce paramètre
        // On retourne le paramètre destinataire dans le même type que l'argument de la fonction
        if($this->getOption('destinataire')) {
            // rajout d'un log pour notifier que nous passons bien dans le cas d'une redirection de mail
            $this->ajoutTrace('redirection des mails vers'.$this->getOption('destinataire'));
            if(is_array($destinataires)) {
                return array($this->getOption('destinataire'));
            } else if(is_string($destinataires)) {
                return $this->getOption('destinataire');
            }
        }
        return $destinataires;
    }
}
