<?php
  /**
   *
   * @copyright 2008 - https://www.clicshopping.org
   * @Brand : ClicShopping(Tm) at Inpi all right Reserved
   * @Licence GPL 2 & MIT

   * @Info : https://www.clicshopping.org/forum/trademark/
   *
   */

  namespace ClicShopping\OM\Module\Hooks\Shop\AllShop;

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\Cache;
  use ClicShopping\OM\HTTP;

  use ClicShopping\Service\Shop\WhosOnline;

  class CurrenciesGeolocalisation
  {

    protected $spider_flag;

    public function __construct()
    {
      if (\defined('CONFIGURATION_CURRENCIES_GEOLOCALISATION_SSLKEY')) {
        $ssl_key = CONFIGURATION_CURRENCIES_GEOLOCALISATION_SSLKEY;
        $this->SSLKey = $ssl_key;
      }

      $this->UrlAPISSL = "https://ssl.geoplugin.net/json.gp?ip=";
      $this->UrlAPI = 'http://www.geoplugin.net/json.gp?ip=';
      $this->spiderFlag = WhosOnline::getResultSpiderFlag();
      $this->ipCustomer = HTTP::getIpAddress();
    }

    /*
     * indicate different informations on the customer
     * @param $localisation_array return an array on the localisation
     *
    */
    private function setUrlAPI(): string
    {

      if (!empty($this->SSLKey) && !\is_null($this->SSLKey)) {
        $url = $this->UrlAPISSL . $this->ipCustomer . '&k=' . $this->SSLKey;
      } else {
        $url = $this->UrlAPI . $this->ipCustomer;
      }

      return $url;
    }

//------------------------------------------------------
//  Debug
//------------------------------------------------------
    /**
     * Display all git information inside a repository or sub directory
     * @param $result , repository to analyse
     * @return $repo,values of array of all git information
     *
     */
    public function displayDataAPI(): string
    {
      $data = '<pre>' . print_r($this->setUrlAPI(), true) . '</pre>';

      return $data;
    }

    /**
     * getJsonCustomerData
     * @param
     * @return $result all data for customer identification and insert in cahce
     * @access private
     */
    private function getJsonCustomerData(): string
    {

      $ip = str_replace('.', '_', $this->ipCustomer);

      $geolocalisation = new Cache('geolocalisation-' . $ip);

      if ($geolocalisation->exists(10)) {
        $result = $geolocalisation->get();
      } else {
        $result = HTTP::getResponse([
          'url' => file_get_contents($this->setUrlAPI())
        ]);
      }

      $result = trim($result);

      if (!empty($result)) {
        $result->save($result);
      }


      /*
            if($CLICSHOPPING_Cache->read('geolocalisation-' . $ip, 60)) {
              $result = $CLICSHOPPING_Cache->getCache();
            } else {
              $url = file_get_contents($this->setUrlAPI() ); //content of readme.
              $data = json_decode($url);
      
              $result = $CLICSHOPPING_Cache->write($data, 'geolocalisation-' . $ip);
            }
      */
      return $result;
    }


    /**
     * getCustomerCountryCode
     * @param
     * @return $country_code,code iso 2 of the country - FR
     *
     */
    public function getCustomerCountryCode(): string
    {

      $data = $this->getJsonCustomerData();
      $country_code = $data->geoplugin_countryCode;

      return $country_code;
    }

    /**
     * getCustomerCountryName
     * @param
     * @return $country_code, name of the country - FRANCE
     *
     */
    public function getCustomerCountryName(): string
    {

      $data = $this->getJsonCustomerData();
      $country_code = $data->geoplugin_countryName;

      return $country_code;
    }

    /**
     * getCustomerRegionCode
     * @param
     * @return $region_code, region of the country name - Jura
     *
     */
    public function getCustomerRegionCode(): string
    {

      $data = $this->getJsonCustomerData();
      $region_code = $data->geoplugin_regionCode;

      return $region_code;
    }

    /**
     * getCustomerRegionName
     * @param
     * @return $region, baem of region of the country name - Jura
     *
     */
    public function getCustomerRegionName(): string
    {

      $data = $this->getJsonCustomerData();
      $region = $data->geoplugin_regionName;

      return $region;
    }

    /**
     * getCustomerContinent
     * @param
     * @return $continent, conteninent of the country - NA / EU / AS ...
     *
     */

    public function getCustomerContinent(): string
    {

      $data = $this->getJsonCustomerData();
      $continent = $data->geoplugin_continentCode;

      return $continent;
    }

    /*
     * Currency in function the localisation
     * @param $new_currency return the currency in function the localisation
     *
     * osc_get_currencies_location
    */
    public function getCurrenciesLocation(): string
    {
      $country_code2 = $this->getCustomerCountryCode();

      if ($this->getCustomerContinent() == 'NA') {
        if ($country_code2 == 'CA') {
          if (DEFAULT_CURRENCY != 'CAD') {
            $new_currency = 'CAD';
          } else {
            $new_currency = DEFAULT_CURRENCY;
          }
        } elseif ($country_code2 == 'US') {
          if (DEFAULT_CURRENCY != 'USD') {
            $new_currency = 'USD';
          } else {
            $new_currency = DEFAULT_CURRENCY;
          }
        } else {
          $new_currency = DEFAULT_CURRENCY;
        }
      } elseif ($this->getCustomerContinent() == 'EU') {
        if ($country_code2 == 'EU') {
          if (DEFAULT_CURRENCY != 'EUR') {
            $new_currency = 'EUR';
          } else {
            $new_currency = DEFAULT_CURRENCY;
          }
        } else {
          $new_currency = DEFAULT_CURRENCY;
        }
      } else {
        $new_currency = DEFAULT_CURRENCY;
      }

      return $new_currency;
    }


    /*
     * Define currency in function the localisation
     * @param
     * @return : false is nothing else return the currency
     *
     * osc_get_currencies_location
    */
    private function getCurrenciesByGeolocalization()
    {
      if (\defined('CONFIGURATION_CURRENCIES_GEOLOCALISATION')) {
        $currencies_by_geolocalization = $this->getCurrenciesLocation();
      } else {
        $currencies_by_geolocalization = false;
      }
      return $currencies_by_geolocalization;
    }

    /*
     * In stall db if does'nt exist
     * @param
     *
     *
    */
    private function install()
    {
      $CLICSHOPPING_Db = Registry::get('Db');

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Do you want to display an automatic default currency by continent ?',
          'configuration_key' => 'CONFIGURATION_CURRENCIES_GEOLOCALISATION',
          'configuration_value' => 'false',
          'configuration_description' => 'Depending on the source of the client and the continent, the price is updated in function the customer continent.<br /><br /><u><strong>Note :</strong></u><br />- The default currency implemented are: USD, EUR, CAD <br />- If the customer comes from another continent, it will be the default currency that is displayed.<br />- The automatic adjustment of currencies depending the language does not work in this case.',
          'configuration_group_id' => '1',
          'sort_order' => '9',
          'set_function' => 'clic_cfg_set_boolean_value(array(\'true\', \'false\'))',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Do you want insert a Key for SSL certification for your automatic currencies by continent (no mandatory) ?',
          'configuration_key' => 'CONFIGURATION_CURRENCIES_GEOLOCALISATION_SSLKEY',
          'configuration_value' => '',
          'configuration_description' => 'The website http://geoplugin.net can propose you a SSL key for a secure connexion. Please go on website and see their documentation',
          'configuration_group_id' => '1',
          'sort_order' => '9',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );
    }

    public function execute()
    {
       if (!\defined('CONFIGURATION_CURRENCIES_GEOLOCALISATION') ) {
        $this->install();

        Cache::clear('menu-administrator');
        Cache::clear('configuration');
      }

      if ($this->spiderFlag === false && \defined('CONFIGURATION_CURRENCIES_GEOLOCALISATION')) {
        if ($this->getCurrenciesByGeolocalization() !== false) {
          $_SESSION['currency'] = $this->getCurrenciesByGeolocalization();
        }

        return $_SESSION['currency'];
      }
    }
  }