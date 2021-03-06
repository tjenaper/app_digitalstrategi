<?php

    session_start();

    date_default_timezone_set("Europe/Berlin");

    define('gapi_email', 'analytics@digital-strategi-statistik.iam.gserviceaccount.com');
    define('gapi_pass', 'Digital_Strategi_Statistik-9bfb05b57067.p12');

    // Skapar konstanten ROOT. Vet alltid vilken som är vår huvudadress: everythingsthlm.se
    define('ROOT', $_SERVER['HTTP_HOST']);

    require_once('db-settings.php');

   // autoload känner av att vi anropar klasser och laddar motsvarande fil. 
    function __autoload($class_name) {

        $class_file = 'classes/'.strtolower($class_name).'.class.php';

        if(file_exists($class_file)) {
            require_once($class_file);
        }
        else {
            return false;
        }

    }

    // Skapar en tom array utifall metoden som anropas inte skickar med någon data.
    $twig_data = [];

    // Skapar en tom $data-variabel. Skickas med in i metoden som anropas så den har något att arbeta med. 
    $data = FALSE; 

    // här kollar vi om .htaccess hittat everythingsthlm.se/class/ 
    if(isset($_GET['class'])) { 
        
        // Omvandlar $_GET['class'] till $class, samt plockar bort den ur $_GET-arrayen.
        $class = $_GET['class'];
        unset($_GET['class']);

        // Kollar om .htaccess har läst av något efter ett andra / (everythingsthlm.se/class/method)
        if(isset($_GET['method'])) {
            // Omvandlar $_GET['method'] till $method, samt plockar bort den ur $_GET-arrayen.
            $method = $_GET['method'];
            unset($_GET['method']);
        }
        // Finns ingen metod i $_GET vet sidan inte vilken metod som ska användas. Då användas "fallback"-metoden för resp. klass. 
        else {
            $method = 'fallback';
        }
        
        // Kollar om det finns något kvar i $_GET efter att $_GET['class'] och $_GET['method'] är borttaget, lägger detta i 
        // $get_data som skickas med in i Twig.  
        if(isset($_GET)) {
            $get_data = $_GET;
        } else {
            $get_data = FALSE;
        }

        // Samma koll som ovan görs för $_POST-data
        if(isset($_POST)) {
            $post_data = $_POST;
        } else {
            $post_data = FALSE;
        }

        //Kombinerar ihop eventuella $_GET- och $_POST-variabler till en $data-variabel med array_merge 
        $data = array_merge($get_data, $post_data);
        $data['http_referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    // Om .htaccess inte hittar en klass används Home-klassen och metod fallback. 
    else {
        $class = 'home';
        $method = 'fallback';
    }

    // Twig-filen som laddas in heter alltid samma som metoden.klassen.twig
    $twig_file = $class.'.'.$method.'.twig';

    // Klassen heter alltid något med versaler först, använder därför funktionen ucfirst() -- gör om första bokstaven i strängen till versal. 
    $class = ucfirst($class);

    // Lägger in det som returneras från metoden i $twig_data som sedan läses in i Twig. 
    if(class_exists($class)) {
        if(method_exists($class, $method)) {
          $twig_data = $class::$method($data);
        }
        else {
            $twig_data = Home::fourOhFour();
        }
    }
    else {
        $twig_data = Home::fourOhFour();
    }

    if(isset($twig_data['redirect_url'])) { 
        header('Location: '.$twig_data['redirect_url']); die; 
    }

    if(isset($twig_data['error'])) { 
        echo $twig_data['error']; 
    }

    if(!isset($twig_data['page'])) {
        $twig_data['page'] = $twig_file;
    }

    if(!isset($twig_data['no_login_check']) || (isset($twig_data['no_login_check']) && $twig_data['no_login_check'] != true)) {
        User::is_logged_in();
    }
    
    if(isset($_SESSION['digitalstrategi'])) {
        $twig_data['current_user'] = new User($_SESSION['digitalstrategi']['user_id']);
    }

    $twig_data['http_referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

    // Nytt twig-objekt skapas. $page innehåller vår twig-data och vi renderar twig-filen index.twig som hanterar all vår data
    $page = new Twig($twig_data);

    echo $page->render('index.twig');
