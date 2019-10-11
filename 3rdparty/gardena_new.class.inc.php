<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class gardena_new {

	protected $url_auth_api = 'https://api.authentication.husqvarnagroup.dev/v1';
	protected $url_gardena_api = 'https://api.smart.gardena.dev/v1';
	
    protected $username;
	protected $password;
    protected $clientId;
    protected $clientSecret;

	protected $accessToken;
	
    protected $provider;

    var $locations;
    var $devices = array();

    function gardena_new($user, $pw, $clientId, $clientSecret, $accessToken=null)
    {
        $this -> username = $user;
        $this -> password = $pw;
        $this -> clientId = $clientId;
        $this -> clientSecret = $clientSecret;
        $this -> accessToken = $accessToken;
        
        $this -> loadLocations();
        $this -> loadDevices();        
        
        $this->loadLocations();
        $this->loadDevices();
    }

    public function getProvider() {
        if ($this->provider==null) {
            $this->provider = new GenericProvider([
                'urlAuthorize' => '',
                'urlAccessToken'=> $this->url_auth_api . '/oauth2/token',
                'urlResourceOwnerDetails'=> $this->url_auth_api . '/users/',
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'redirectUri' => '',
            ]);
        }
        return $this->provider;
	}
    
	public function getAccessToken($_forceRefresh = false) {
        if ($this->accessToken!=null){
            // Refresh token if it has expired
            if ($this->accessToken->hasExpired()){
                print("============ REFRESH TOKEN ===============\n");
                $this->accessToken = $this->getProvider()->getAccessToken('refresh_token', ['refresh_token' => $this->accessToken->getRefreshToken()]);
            } else {
                print("============ REUSE TOKEN ===============\n");
            }
        } else {
            // Get new token
            print("============ NEW TOKEN ===============\n");
            $this->accessToken = $this->getProvider()->getAccessToken('password', [
                'username' => $this->username,
                'password' => $this->password]);
        }
        print_r($this->accessToken);
        return $this->accessToken;
	}
    
	public function request($_type, $_request, $_options = array()) {
		$options = ['headers' => ['X-Api-Key' => $this->clientId, 'Authorization-Provider' => 'husqvarna']];
		$options = array_merge_recursive($options, $_options);
		$provider = $this->getProvider();
		try {
			$request = $provider->getAuthenticatedRequest($_type, $this->url_gardena_api . $_request, $this->getAccessToken(), $options);
			$response = $provider->getResponse($request);
            		if(!is_array($response)){
			      return json_decode($response->getBody()->getContents());
			}
			return $response;
		} catch (Exception $e) {
            print $e->getMessage();
		}
		$request = $provider->getAuthenticatedRequest($_type, $this->url_gardena_api . $_request, $this->getAccessToken(true), $options);
		return json_decode($provider->getResponse($request)->getBody()->getContents());
	}
    
    function logout()
	{
		$result = $this->del_api("token/".$this->token);
		if ( $result !== false )
		{
			unset($this->token);
			unset($this->provider);
			return true;
		}
		return false;
	}
    
	function loadLocations()
	{
		$result = $this->request('GET', '/locations');
        $this -> locations = $result->data;  
        print("============ LOCATIONS ===============\n");
        print_r($this -> locations);
	}

    function loadDevices()
    {         
        foreach($this->locations as $location)
        {
            $result = $this->request('GET', '/locations/'.$location -> id);
            print("============ DEVICES [" . $location -> attributes -> name . "] ===============\n");
            print_r($result);
            $this -> devices[$location -> id] = $result->included;
        }
    }
    
    /**
    * Finds all occurrences of a certain category type.
    * Example: You want to find all of your mowers, having one or more gardens. 
    * 
    * @param constant $category
    */
    function getDevicesOfCategory($category)
    {
        $categoryDevices = array();
        foreach($this -> devices as $locationId => $devices)
        {        
            foreach($devices as $device)
                if ($device -> type == $category) {
                    $categoryDevices[] =  $device;
                }
        }
        return $categoryDevices;
    }

    function getDeviceByCategoryAndId($category, $id)
    {
        foreach($this -> devices as $locationId => $devices)
        {        
            foreach($devices as $device)
                if ($device -> type == $category && $device -> id == $id)
                    return $device;
        }
    }
}
$gardena = new gardena_new("xavier.lecourtier@gmail.com", "Juliette2009g", "4cbc0610-27e5-42b2-8584-df6edcfc8ab0", "69e11df3-433d-4a71-93f4-a8cf37719aaf");
print("============ MOWER DEVICES ===============\n");
foreach($gardena->getDevicesOfCategory("MOWER") as $device){
    print("============ FOUND MOWER DEVICE ". $device->id . " ===============\n");
    $common = $gardena->getDeviceByCategoryAndId("COMMON", $device->id);
    $mower  = $gardena->getDeviceByCategoryAndId("MOWER", $device->id);
    print "NAME:\t" . $common->attributes->name->value . "\n";
    print "ACTIVITY:\t" . $mower->attributes->activity->value . "\n";
}
?>