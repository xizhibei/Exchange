<?php

// Define path to application directory
defined('APPLICATION_PATH')
        || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
        || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
            realpath(APPLICATION_PATH . '/../library'),
            get_include_path(),
        )));

require_once 'Zend/Registry.php';
/* * **********   Set Logs *************** */
require_once 'Zend/Log.php';
require_once 'Zend/Log/Writer/Stream.php';

$log = new Zend_Log(new Zend_Log_Writer_Stream('../logs/errors_' . date("Y-m-d") . '.log', 'a+'));
Zend_Registry::set('error_log', $log);

//require_once 'Zend/Config/Ini.php';
//$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'staging');
//Zend_Registry::set('config', $config);

//require_once 'Zend/Db.php';
//$params = $config->database->params->toArray();
//$params['driver_options'] = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8');
//$db = Zend_Db::factory('PDO_MYSQL', $params);


//require_once 'Zend/Db/Table.php';
//Zend_Db_Table::setDefaultAdapter($db);

/** Setup layout */
//require_once 'Zend/Layout.php';
//Zend_Layout::startMvc(APPLICATION_PATH . '/views');


/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
                APPLICATION_ENV,
                APPLICATION_PATH . '/configs/application.ini'
);

require_once 'Zend/Acl.php';
$acl = new Zend_Acl();
$acl->addRole(new Zend_Acl_Role('guest'));
$acl->addRole(new Zend_Acl_Role('user'), 'guest');
$acl->addRole(new Zend_Acl_Role('admin'));
require_once 'Zend/Auth.php';
$auth = Zend_Auth::getInstance();
//begin to deal identity
if ($auth->hasIdentity()) {
    $user = (array) $auth->getStorage()->read();
    if (isset($user['uid'])) {
        $_SESSION['KCFINDER']['disabled'] = false;
        $dir = "/upload/" . $user['uid'] . "/";
        $_SESSION['KCFINDER']['uploadURL'] = $dir;
        $dir = "." . $dir;
        if (!is_dir($dir)) {
            mkdir($dir);
            touch($dir . "index.html");
            mkdir($dir . ".thumbs/");
            touch($dir . ".thumbs/index.html");
        }
    } else {//guest
        $_SESSION['KCFINDER']['disabled'] = true;
    }
} else {
    $auth->getStorage()->write((object) array(
                'role' => 'guest',
                'name' => '游客',
    ));
    $_SESSION['KCFINDER']['disabled'] = true;
}

Zend_Registry::set('auth', $auth);
Zend_Registry::set('acl', $acl);


//require_once 'Zend/Loader.php';

$application->bootstrap()
        ->run();