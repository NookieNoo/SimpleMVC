<?php
namespace ItForFree\SimpleMVC;

/**
 * Класс для работы с данными пользователя
 * @author qwe
 */
class User extends Session
{
    public $role = null;
    
    public $userName = null;
    
    /**
    * Вернёт объект юзера
    * 
    * @staticvar type $instance
    * @return \static
    */
    public static function get()
    {
        static $instance = null; // статическая переменная
        if (null === $instance) { // проверка существования
            $instance = new static();
        }
        return $instance;
    }
    
    /** 
     * Скрываем конструктор для того чтобы класс нельзя было создать в обход getInstance 
     */
    protected function __construct()
    {
        if (!empty(Session::get()->session['user']['role'])
                && !empty(Session::get()->session['user']['userName'])) {
            $this->role = Session::get()->session['user']['role'];
            $this->userName = Session::get()->session['user']['userName'];
        }
        else {
            Session::get()->session['user']['role'] = 'guest';
            Session::get()->session['user']['userName'] = 'guest';
            $this->role = 'guest';
            $this->userName = 'guest';
            Session::get()->session['user']['userSessionLikesCount'] = 0;
        }
    }
        
    /**
     * Присваивает данной сессии имя пользователя и роль в соответствии с полученными данными
     * @param type $userName
     * @param type $pass
     * @return boolean
     */
    public function login($login, $pass)
    {
        if ($this->checkAuthData($login, $pass)) {
            
            $role = $this->getRoleByUserName($login); 
            $this->role =  $role; 
            $this->userName = $login;
            Session::get()->session['user']['role'] = $role; 
            Session::get()->session['user']['userName'] = $login; 
            Session::get()->session['user']['userSessionLikesCount'] = 0; 
            return true;
        }
        else return false;
    }
    
    /**
     * Получить роль по имени пользователя
     * @param type $userName
     * @return type
     */
    private function getRoleByUserName($userName)
    {
        $pdo = new mvc\Model();
        $sql = "SELECT role FROM users WHERE login = :login";
        $st = $pdo->pdo->prepare($sql);
        $st->bindValue( ":login", $userName, \PDO::PARAM_STR);
        $st->execute();
        
        $siteAuthData = $st->fetch();
        if (isset($siteAuthData['role'])) {
            return $siteAuthData['role'];
        }
        
    }
    
    /**
     * Проверяет, можно ли авторизировать пользователя с данным логином и паролем
     * 
     * @param string $login
     * @param string $pass
     * @return boolean
     */
    private function checkAuthData($login, $pass)
    {
        $result = false;
        
        $pdo = new mvc\Model();
        $sql = "SELECT salt, pass FROM users WHERE login = :login";
        $st = $pdo->pdo->prepare($sql);
        $st->bindValue( ":login", $login, \PDO::PARAM_STR);
        $st->execute();
        $siteAuthData = $st->fetch();
   
        $pass .= $siteAuthData['salt'];
        $passForCheck = password_verify($pass, $siteAuthData['pass']);

        
        if (isset($siteAuthData['pass'])) {
            if ($passForCheck) {
                $result = true;
            }
        }
        return $result;
    }
    
    /**
     * Удаляет из Userа и Сессии данные об актуальной роли и мени пользователя
     */
    public function logout()
    {
        
        $this->role = "";
        $this->userName = "";
        Session::get()->session['user'] = null;
//        session_destroy();
        return true;
    }
    
    /**
     * Проверяет разрешено ли данному пользовалю использвать данный маршрут
     * 
     * @param string $route маршрут
     * @return boolean  доступен ли он данном пользователю
     */
    public function isAllowed($route)
    {
        $result = false;
        $controllerClassName = "\\application\\controllers\\" . Router::getControllerClassName($route);
        $controller = new $controllerClassName();
        $action = $controller->getControllerActionName($route);
        
//        echo "<br>Контроллер: " .  $controllerClassName . "<br> Действие: " . $action;
        
        if ($controller->isEnabled($route, $action)) {
            $result = true;
        }
//        echo "<br>Результат: " . $result;
        return $result;
    }
 
    /**
     * 
     * @param type $route
     * @param type $elementHTML
     */
    public function returnIfAllowed($route, $elementHTML) 
    {
        if($this->isAllowed($route)) {
            echo $elementHTML;
        };
    }
    
}
