<?php 
Class Lib_google
{
    var $client;
    var $people_service;
    var $nextPage = false;
    var $contacts = array();

    public function __construct() 
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Dinner4Friends');
        $this->client->setAuthConfig(CLIENT_SECRET_PATH);
        $this->client->setAccessType('offline');
        $this->client->addScope(Google_Service_People::CONTACTS_READONLY);
        $this->client->addScope(Google_Service_People::USER_EMAILS_READ);

    }
    public function getFriends($pageToken=false) 
    {
        $this->client->authenticate($_GET['code']);
        $access_token = $this->client->getAccessToken();
        if($access_token){
            $_SESSION['google_access_token'] = $access_token;
        }

        $this->client->setAccessToken(json_encode($_SESSION['google_access_token']));

        $this->people_service = new Google_Service_People($this->client);
        $optParams = array(
            'pageSize' => 500,
            'sortOrder' => 'LAST_MODIFIED_ASCENDING'
        );
        if($pageToken)
            $optParams['pageToken'] = $pageToken;

        $connections = $this->people_service->people_connections->listPeopleConnections('people/me', $optParams);

        $resources = array();
        $i=0;
        foreach($connections->connections as $contact){
            $resources[$i]['resourceNames'][] = $contact->resourceName;
            if(count($resources[$i]['resourceNames']) > 49){
                $i++;
            }
        }

        foreach($resources as $resourceNames){
            $this->getPersonInfo($resourceNames);
        }

        if($connections->nextPageToken)
            $this->getFriends($connections->nextPageToken);

        return $this->contatos;
    }

    public function getPersonInfo($resourceNames) 
    {
        try{
            $contacts = $this->people_service->people->getBatchGet($resourceNames);
            foreach ($contacts->responses as $person) {
                if(isset($person->person->emailAddresses) and filter_var($person->person->emailAddresses[0]->value, FILTER_VALIDATE_EMAIL)){
                    $this->contatos[] = array('nome' => (isset($person->person->names) ? $person->person->names[0]->displayName : ''), 
                                        'email' => $person->person->emailAddresses[0]->value
                                       );
                }
            }
        } catch(Exception $e){
            print '<pre class="debug" style="text-align:left;">'.print_r(json_decode($e->getMessage()), true)."</pre>";
        }

    }

    public function getAuthUrl() 
    {
        return $this->client->createAuthUrl();
    }
}
