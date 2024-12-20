<?php

namespace magicalella\emarsys;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * Class Emarsys
 * Emarsys component
 * @package magicalella\emarsys
 *
 * @author Raffaella Lollini
 */
class Emarsys extends Component
{

    /**
     * @var string 
     */
    public $client_id;

    /**
     * @var string 
     */
    public $client_secret;
    
    
    /**
     * @var string
     */
    public $endpoint_autentication;
    
    /**
     * @var string
     */
    public $endpoint_api;

    /**
     * @var retrive after login
     */
    public $access_token = false;
    public $access_token_expire = false; 
    
    private $client;
    
    public $log = '';

    const STATUS_SUCCESS = true;
    const STATUS_ERROR = false;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->client_id) {
            throw new InvalidConfigException('$client_id not set');
        }

        if (!$this->client_secret) {
            throw new InvalidConfigException('$client_secret not set');
        }
        
        if (!$this->endpoint_autentication) {
            throw new InvalidConfigException('$endpoint_autentication not set');
        }
        
        if (!$this->endpoint_api) {
            throw new InvalidConfigException('$endpoint_api not set');
        }
        
        
        parent::init();
    }
    
    /**
    Login recupera ApiKey
     */
    private function getAccessToken(){
        $data = [];
        $data['username'] = $this->user;
        $data['password'] = $this->password;
        $json = json_encode($data);
        $errore ='';
        $messaggio = '';
        $key = base64_encode("$this->client_id:$this->client_secret");
        $request = $this->client->createRequest()
            ->setMethod('POST')
            ->setUrl($this->endpoint_autentication)
            ->setFormat(Client::FORMAT_JSON)
            ->setHeaders([
                'content-type' => 'application/x-www-form-urlencoded;charset=UTF-8',
                'Authorization' => 'Basic '.$key ,
                'Accept' => 'application/json' ,
            ])
            ->setData([
                'grant_type' => 'client_credentials',
            ]);
        $response = $request->send();
        
        if ($response->isOk) {
            if (!($this->access_token = $response->data['access_token'])) {
                Yii::$app->session->setFlash('error', 'AccessToken non ricevuta');
                Yii::error(sprintf('ERRORE CHIAMATA AccessToken EMARSYS :  AccessToken non ricevuta'), __METHOD__);
                $this->log .= ' ERRORE CHIAMATA AccessToken EMARSYS :  AccessToken non ricevuta ';
                return false;
            }else{
                $this->access_token_expire = $response->data['expires_in'];
            }
            return true;
        }else {
            //testare CODE del response checkStatusCode()
            $errore = $this->checkStatusCode($response);
            $messaggio = sprintf('ERRORE CHIAMATA AccessToken EMARSYS :  Impossibile connettersi a EMARSYS %s',$messaggio);
            Yii::error($messaggio, __METHOD__);
            $this->log .= $messaggio;
            return false;
        }
    }
    
    // private function checkLoginIsValid()
    // {
    //     $url = 'loginmanager/loginisvalid/'.$this->access_token;
    //     $request = $this->client->createRequest()
    //         ->setMethod('GET')
    //         ->setUrl($url)
    //         ->setFormat(Client::FORMAT_JSON)
    //         ->setHeaders([
    //             'Accept: application/json, application/json',
    //             'Content-Type' => 'application/json;charset=UTF-8',
    //             'ApiKey: ' . $this->access_token
    //         ])
    //         ->setContent($data);
    //     $response = $request->send();
    //     
    //     if (!$response->isOk) {
    //         //testare CODE del response checkStatusCode()
    //         Yii::error(sprintf('ERRORE CHIAMATA EMARSYS %s data : ',$url,print_r($data, true)), __METHOD__);
    //         $this->log .= ' ERRORE CHIAMATA EMARSYS : '.$url;
    //     }
    //     
    //     return $response;
    // }
    
    // public function checkLogOff()
    // {
    //     $errore ='';
    //     $messaggio = '';
    //     $url = 'loginmanager/logoff/'.$this->access_token;
    //     
    //     $request = $this->client->createRequest()
    //         ->setMethod('GET')
    //         ->setUrl($url)
    //         ->setFormat(Client::FORMAT_JSON)
    //         ->setHeaders([
    //             'Accept: application/json, application/json',
    //             'Content-Type' => 'application/json;charset=UTF-8',
    //             'ApiKey: ' . $this->access_token
    //         ]);
    //         //->setContent($data);
    //     $response = $request->send();
    //     
    //     if (!$response->isOk) {
    //         $errore = $this->checkStatusCode($response);
    //         $messaggio = sprintf('ERRORE CHIAMATA LogOff EMARSYS :  URL: %s , ERRORE: %s ',$url , $errore );
    //         Yii::error($messaggio, __METHOD__);
    //         $this->log .= $messaggio ;
    //     }
    //     return $response;
    // }
    
    
    /**
    In base a status code della risposta ritorna $errore
    */
    private function checkStatusCode($response)
    {
        $errore = 'Non riconosciuto';
        //con mappatura tutti errori
        $code = $response->statusCode;
        switch($code){
            case '400':
                $errore = 'Bad Request';
            break;
            case '401':
                $errore = 'Unauthorized';
            break;
            case '404':
                $errore = 'End Point non trovato';
            break;
    }
        return $errore;
    }
    
    /**
     * Call EMARSYS function POST
     * @param string $call Name of API function to call
     * @param array $data
     * @return response [[
              'status' true/false
              'message' messaggio
              'data' il content restituito dalla CURL che se errore contiene ok e msg altrimenti oggetto richiesto
          ]
     */
    public function post($call, $data)
    {
        $response = [];
        $result = [];
        $message = '';
        $status = Self::STATUS_SUCCESS;
        $content = [];
        $this->client = new Client(
            [
                'baseUrl' => $this->endpoint ,
                'responseConfig' => [
                    'format' => Client::FORMAT_JSON
                ],
            ]
        );
        if(!$this->access_token){
            $this->getAccessToken();
        }
        
        $json = json_encode($data);
        $response = $this->curl($this->endpoint . $call, $json,'POST');
        
        if($response['status'] == Self::STATUS_SUCCESS){
            //la chiamata può restituire oggetto desiderato o errori di post 
            if(isset($response['data']->ok) && !$response['data']->ok){
                $status = Self::STATUS_ERROR;
                $message = $response['data']->msg;
            }else{
                $content = $response['data'];
    }
        }else{
            //errore nella chiamata
            $status = Self::STATUS_ERROR;
            $message = $this->log;    
        }
        $result = [
            'status' => $status,
            'message' => $message,
            'data' => $content
        ];
        return $result;
    }
    
    /**
     * Call EMARSYS function GET
     * @param string $call Name of API function to call
     * @param array $data
     * @return response [[
               'status' true/false
               'message' messaggio
               'data' il content restituito dalla CURL che se errore contiene ok e msg altrimenti oggetto richiesto
           ]
     */
    
    public function get($call, $data)
    {
        $response = [];
        $result = [];
        $message = '';
        $status = Self::STATUS_SUCCESS;
        $content = [];
        $this->client = new Client(
            [
                'baseUrl' => $this->endpoint ,
                'responseConfig' => [
                    'format' => Client::FORMAT_JSON
                ],
            ]
        );
        if(!$this->access_token){
            $this->getAccessToken();
        }
        $json = json_encode($data);
        $response = $this->curl($this->endpoint . $call, $json,'GET');
        
        if($response['status'] == Self::STATUS_SUCCESS){
            //la chiamata può restituire oggetto desiderato o errori di post 
            if(isset($response['data']->ok) && !$response['data']->ok){
                $status = Self::STATUS_ERROR;
                $message = $response['data']->msg;
            }else{
                $content = $response['data'];
    }
        }else{
            //errore nella chiamata
            $status = Self::STATUS_ERROR;
            $message = $this->log;    
        }
        $result = [
            'status' => $status,
            'message' => $message,
            'data' => $content
        ];
        return $result;
    }
    

    /**
     * Do request by CURL
     * @param $url
     * @param $data
     * @return $result [
         'status' true/false
         'message' messaggio
         'data' il content restituito dalla CURL formato json
     ]
     */
    private function curl($url, $data, $method = 'POST')
    {
        $errore ='';
        $messaggio = '';
        $status = Self::STATUS_SUCCESS;
        $response = false;
        $result = [];
        $content = [];
        
        $request = $this->client->createRequest()
            ->setMethod($method)
            ->setUrl($url)
            ->setFormat(Client::FORMAT_JSON)
            ->setHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Bearer '.$this->access_token ,
            ])
            ->setContent($data);
        $response = $request->send();
        // echo $url.' ';
        // print_r($response);
        // exit();
        if (!$response->isOk) {
            $status = Self::STATUS_ERROR;
            $errore = $this->checkStatusCode($response);
            $messaggio = sprintf('ERRORE CHIAMATA CURL EMARSYS :  URL: %s , ERRORE: %s , DATA json: %s ',$url , $errore ,print_r($data,true) );
            Yii::error($messaggio, __METHOD__);
            $this->log = $messaggio ;
        }else{
            if($response->content != ''){
                $content = json_decode($response->content);
        }
        }
         
        //dopo ogni chiamata chiudo sessione
        //$this->checkLogOff();
        return $result = [
            'status' => $status,
            'message' => $messaggio,
            'data' => $content
        ];
    }


}
