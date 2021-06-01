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
        private string $url;
        
        /** @var string */
        protected $host;
        protected $user;
        protected $password;
        protected $database;

        public function __construct()
        {
            $this->url = __DIR__ . '/../../../../.env';

            if (file_exists( $this->url )) {

                $this->dotenv = new Dotenv();
                $this->dotenv->load( $this->url );
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