<?php
/**
 * Description of HttpBasicAuth
 *
 * @author usersio
 */
class HttpBasicAuth extends \Slim\Middleware
{
    /**
     * @var string
     */
    protected $realm;
    /**
     * Constructor
     *
     * @param   string  $realm      The HTTP Authentication realm
     */
    public function __construct($realm = 'Protected Area')
    {
        $this->realm = $realm;
    }
 
    /**
     * Deny Access
     *
     */   
    public function deny_access() {
        $res = $this->app->response();
        $res->status(401);
        $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));        
    }
 
    /**
     * Authenticate 
     *
     * @param   string  $username   The HTTP Authentication username
     * @param   string  $password   The HTTP Authentication password     
     *
     */
    public function authenticate($username, $password) {
        if(!ctype_alnum($username))
            return false;
         
        if(isset($username) && isset($password)) {
            $user=R::findOne('users','login="'.$username.'" AND password="'.sha1($password).'"');
            if($user){
                /*
                 * Deleting all beans older then 5mn
                 */
                $date2=time()-(60);
                $date=date('Y-m-d h:i:s',$date2);
                $beans=R::findAll('log','date<"'.$date.'"');
                R::trashAll($beans);
                
                /*
                 * checking if the users can do more requests
                 */
                $number=R::count('log','user_id= ?',[$user->id]);
                if($number>250)
                {
                    return false;
                }else
                {
                    /*
                    * saving the logs in the database
                    */
                   $log=R::dispense('log');
                   $log->date=date('Y-m-d h:i:s');
                   $log->user=$user;
                   R::store($log);
                   return true;
                }
                
                
                
            }
        }
        else
        {
            return false;
        }
    }
 
    /**
     * Call
     *
     * This method will check the HTTP request headers for previous authentication. If
     * the request has already authenticated, the next middleware is called. Otherwise,
     * a 401 Authentication Required response is returned to the client.
     */
    public function call()
    {
        $req = $this->app->request();
        $res = $this->app->response();
        $authUser = $req->headers('PHP_AUTH_USER');
        $authPass = $req->headers('PHP_AUTH_PW');
         
        if ($this->authenticate($authUser, $authPass)) {
            $this->next->call();
        } else {
            $this->deny_access();
        }
    }
}
