<?php

    // require_once '../vendor/autoload.php';

    declare(strict_types = 1);

    if(!isset($_SESSION)) {
        session_start();
    }

    use Symfony\Component\Dotenv\Dotenv;
    use Palvoelgyi\Helper\Helper;

    class Configuration
    {
        private Dotenv $dotenv;
        
        /** @var string */
        protected $host;
        protected $user;
        protected $password;
        protected $database;

        public function __construct()
        {
            if (file_exists('../.env')) {

                 $this->dotenv = new Dotenv();
                $this->dotenv->load('../.env');
            }

            /** either $_SESSION or .env file or standard setting  */
            $this->host =
            ( 
                $_SESSION['HOST'] ?? 
                ($_ENV['HOST']  ?? 'localhost' )
            );
            $this->user =
            ( 
                $_SESSION['USER'] ?? 
                ($_ENV['USER']  ?? 'root' )
            );
            $this->password =
            ( 
                $_SESSION['PASSWORD'] ?? 
                ($_ENV['PASSWORD']  ?? '' )
            );
            $this->database =
            ( 
                $_SESSION['DATABASE'] ?? 
                ($_ENV['DATABASE']  ?? 'test' )
            );
        }
    }