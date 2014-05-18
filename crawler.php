<?php
// How to Run this program ==============================
// Edit CRUNCHBASE API KEY CODE
// Use at Shell Command like below
// php crawler.php ./Input > Output.csv
//
// Input ============================================
// crunch base permalink list or crunchbase company url
// Facebook => facebook or http://www.crunchbase.com/company/facebook
// Sample is Input_Samples.txt
//
// Output =========================================
// Column is below
// Sample is Output_sample.csv
// 'name'             ,
// 'crunchbase_url'   ,
// 'homepage_url'     ,
// 'short_description',
// 'description'      ,
// 'products'         ,
// 'founded_on'       ,
// 'closed_on'        ,
// 'stock_exchange_id',
// 'own'              ,
// 'current_team'     ,
// 'acquisitions'     ,
// 'competitors'      ,
// 'offices'          ,
// 'headquarters'     ,
// 'funding_rounds'   ,
// 'markets'          ,
// 'investments'      ,
// 'founders'         ,
// 'ipo'              ,
// 'web_presences'    ,
// 'press'            ,
//
$CONFIG = parse_ini_file( "./config.ini" );
define( 'API_KEY', $CONFIG["API_KEY"] ); 
define( 'USER_KEY', "?user_key=" . API_KEY );
define( 'PAGENATE_NUM', 8 );
define( 'PAGENATE_UNIT', 1000 );

define( 'API_URL', 'http://api.crunchbase.com/v/2/organization/%s' . USER_KEY );

if( is_valid($argv) ){
     main($argv[1]);
}

function main($file_name){
   $input=file_get_contents($file_name);
   $files=explode("\n", $input);

   echo Constant::get_firstline();
   foreach( $files as $file ){
      if( empty($file) ) { echo "\n"; continue;  }
      $url             = Constant::get_url($file);
      $getJsonContents = new getJsonContents($url);
      $getJsonContents -> run();
      sleep(1);
   }
}

function is_valid($argv){
   if( empty( $argv[1] )){
     print "Input file is undefined\n";
     return false;
   }
   if(  !is_file( $argv[1] )){
     print "Input file is not found\n";
     return false;
   }
   return true;
}

class getJsonContents{
   function __construct($url){
      $json_contents       = json_decode( file_get_contents($url), true );
      $this->url           = array(
                                    "www"      => $json_contents["metadata"]["www_path_prefix"  ], 
                                    "api"      => $json_contents["metadata"]["api_path_prefix"  ],
                                    "image"    => $json_contents["metadata"]["image_path_prefix"],
                                   );
      $this->relationships = $json_contents["data"]["relationships"];
      $this->properties    = $json_contents["data"]["properties"];
      $this->data          = Array();
   }

   function run () {
      self::parse_json();
      echo self::ajust_format();
   }

   private function get_pagenate( $paging ){
       $res       = array();
       $page_max  = ceil( $paging["total_items"] / PAGENATE_UNIT );
       for( $n = 1; $n <= $page_max; $n++){
          $url      = $paging["first_page_url"] . USER_KEY . "&page=" . $n . "&order=" . rawurlencode( $paging["sort_order"] );
          $contents = json_decode( file_get_contents( $url ),  true );
          $res      = array_merge( $res, $contents["data"]["items"] );
       }
       return $res;
   }

   private function get_items( $data, $term ){
      if( !preg_match( "/press|current_team/", $term ) && $data[$term]["paging"]["total_items"] > PAGENATE_NUM ){
         $data[$term]["items"] = self::get_pagenate( $data[$term]["paging"] );
      }
      $detail = array();
      foreach( $data[$term]["items"] as $item ){
         array_push( $detail , self::get_item_detail( $item, $term ) );
         array_push( $detail , "" );
      }
      return join("\r", $detail );
   }

   private function get_item_detail( $item, $term ){
      $res = array();
      switch( true ){
         case $term == "owns" :
            array_push( $res, $item["name"] );
            array_push( $res, $this->url["www"] . $item['path'] );
         break;
         case $term == "current_team" :
            $from = (!empty($item["started_on"]))? " From " . $item["started_on"]:"";
            array_push( $res, $item["title"] . " : " .  $item["last_name"] . " " .  $item["first_name"] . $from );
            array_push( $res, $this->url["www"] . $item['path'] );
         break;
         case $term == "acquisitions" :
            $from = (!empty($item["announce_on"]))? " From " . $item["announce_on"]:"";
            array_push( $res, $item["type"] . " : " .  $item["name"] . $from );
            array_push( $res, $this->url["www"] . $item['path'] );
         break;
         case preg_match( "/funding_rounds|competitors|founders|ipo|products/", $term ) :
            array_push( $res, $item["type"] . " : " .  $item["name"] );
            array_push( $res, $this->url["www"] . $item['path'] );
         break;
         case $term == "markets" :
            array_push( $res, $item["type"] . " : " .  $item["name"] );
         break;
         case preg_match( "/headquarters|offices/", $term ) :
            array_push( $res, self::get_location( $item ) );
         break;
         case $term == "investments" :
            array_push( $res, self::get_investments( $item ) );
         break;
         case $term == "web_presences" :
            array_push( $res, self::get_web_presences( $item ) );
         break;
         case $term == "press" :
            array_push( $res, self::get_press( $item ) );
         break;
      }
      return join("\r", $res);
   }

   private function get_web_presences( $item ){
      $res = array();
      array_push( $res, $item["type"] . " : ". $item["title"]);
      array_push( $res, $item["url"] );
      return join("\r", $res);
   }

   private function get_press( $item ){
      $res = array();
      array_push( $res, $item["type"] . " : ". $item["title"] . " " . $item["author"]  . " On " . $item["posted_on"]);
      array_push( $res, $item["url"] );
      return join("\r", $res);
   }

   private function get_investments( $item ){
      $res = array();
      array_push( $res, $item['type'] . ' :' . $item['money_invested_currency_code'] . ' ' . $item['money_invested'] . ' ' . $item['money_invested_usd'] );
      array_push( $res, $item['invested_in']['type'] . " : " . $item['invested_in']["name"] );
      array_push( $res, $this->url["www"] . $item['invested_in']['path'] );
      array_push( $res, "Founded Round\n" . $this->url['www'] . $item['funding_round']['path'] );
      return join( $res, "\r" );
   }
   private function get_location( $item ){
      $res = $item["type"] . " : " . $item["name"] . " ";
      $location = array();
      if(!empty( $item["street_1"] ))      { array_push( $location, $item["street_1"] ); }
      if(!empty( $item["street_2"] ))      { array_push( $location, $item["street_2"] ); }
      if(!empty( $item["city"] ))          { array_push( $location, $item["city"] ); }
      if(!empty( $item["region"] ))        { array_push( $location, $item["region"] ); }
      if(!empty( $item["country_code"] ))  { array_push( $location, $item["country_code"] ); }
      return $res . join( ", ", $location);
   }

   function parse_json(){
      $this->data['name'             ] = $this->properties['name'];
      $this->data['crunchbase_url'   ] = $this->url["www"] . "/organization/" . $this->properties["permalink"];
      $this->data['homepage_url'     ] = $this->properties['homepage_url'];
      $this->data['short_description'] = $this->properties['short_description'];
      $this->data['description'      ] = $this->properties['description'];
      $this->data['products'         ] = self::get_items( $this->relationships, "products" );
      $this->data['founded_on'       ] = (!empty( $this->properties['founded_on']) )?$this->properties['founded_on']:"";
      $this->data['closed_on'        ] = (!empty( $this->properties['closed_on'] ) )?$this->properties['closed_on']:"";
      $this->data['stock_exchange_id'] = $this->properties['stock_exchange_id'];
      $this->data['own'              ] = self::get_items( $this->relationships, "owns" );
      $this->data['current_team'     ] = self::get_items( $this->relationships, "current_team" );
      $this->data['acquisitions'     ] = self::get_items( $this->relationships, "acquisitions" );
      $this->data['competitors'      ] = self::get_items( $this->relationships, "competitors" );
      $this->data['offices'          ] = self::get_items( $this->relationships, "offices" );
      $this->data['headquarters'     ] = self::get_items( $this->relationships, "headquarters" );
      $this->data['funding_rounds'   ] = self::get_items( $this->relationships, "funding_rounds" );
      $this->data['markets'          ] = self::get_items( $this->relationships, "markets" );
      $this->data['investments'      ] = self::get_items( $this->relationships, "investments" );
      $this->data['founders'         ] = self::get_items( $this->relationships, "founders" );
      $this->data['ipo'              ] = self::get_items( $this->relationships, "ipo" );
      $this->data['web_presences'    ] = self::get_items( $this->relationships, "web_presences" );
      $this->data['press'            ] = self::get_items( $this->relationships, "press" );
   }

   function ajust_format(){
      foreach( Constant::get_columns() as $column ){
         $this->data[$column] = preg_replace("/\r\r\r/", "\r\r", $this->data[$column] );
         $this->data[$column] = str_replace('"', '""', $this->data[$column] );
      }
      return mb_convert_encoding( '"'.join('","', $this->data), 'sjis-win', 'UTF-8' ). '"' . "\n";  
   }


}

class Constant{
   public static function get_columns(){
      return array (
          'name'             ,
          'crunchbase_url'   ,
          'homepage_url'     ,
          'short_description',
          'description'      ,
          'products'         ,
          'founded_on'       ,
          'closed_on'        ,
          'stock_exchange_id',
          'own'              ,
          'current_team'     ,
          'acquisitions'     ,
          'competitors'      ,
          'offices'          ,
          'headquarters'     ,
          'funding_rounds'   ,
          'markets'          ,
          'investments'      ,
          'founders'         ,
          'ipo'              ,
          'web_presences'    ,
          'press'            ,
      );
   }
   public static function get_firstline(){
      return '"' . join('","', self::get_columns() ) . '"'. "\n";
   }
   public static function get_url($file){
      return sprintf( API_URL, basename( $file) );
   }
}
?>
