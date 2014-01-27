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
// 'name',
// 'website',
// 'blog_url',
// 'category_code',
// 'number_of_employees',
// 'founded',
// 'deadpooled',
// 'description',
// 'overview',
// 'relationships',
// 'competitors',
// 'providerships',
// 'total_money_raised',
// 'funding_rounds',
// 'acquisition',
// 'acquisitions',
// 'invest',
//
// ================================================
// API_KEY = CRUNCHBASE API KEY CODE
define( 'API_KEY', '' ); 

define( 'API_URL', 'http://api.crunchbase.com/v/1/company/%s.js?api_key=' . API_KEY );
define( 'COMPANY_URL', 'http://www.crunchbase.com/company/' );
define( 'PEOPLE_URL', 'http://www.crunchbase.com/person/' );
$COLUMNS = array(
    'name',
    'website',
    'blog_url',
    'category_code',
    'number_of_employees',
    'founded',
    'deadpooled',
    'description',
    'overview',
    'relationships',
    'competitors',
    'providerships',
    'total_money_raised',
    'funding_rounds',
    'acquisition',
    'acquisitions',
    'invest',
);
if( empty( $argv[1] )){
  echo "Input file is undefined\n";
  exit;
}
if(  !is_file( $argv[1] )){
  echo "Input file is not found\n";
  exit;
}
$input=file_get_contents($argv[1]);
$files=explode("\n", $input);

echo '"' . join('","', $COLUMNS) . '"'. "\n";
foreach( $files as $file ){
   if( empty($file)) { echo "\n"; continue;  }
   $url = sprintf( API_URL, get_company_name($file) );
   $con = new getJsonContents($url);
   $data = $con -> run();
   foreach( $COLUMNS as $column ){
      $data[$column] = preg_replace("/\r\r\r/", "\r\r", $data[$column] );
      $data[$column] = str_replace('"', '""', $data[$column] );
   }
   echo '"'.join('","', $data) . '"' . "\n";
   sleep(1);
}

function get_company_name( $url ){
  return basename( $url );
}

class getJsonContents{
   function __construct($url){
     $this->json_array = json_decode( file_get_contents($url), true );
   }

   function run () {
     $data['name']                = $this->json_array['name'];
     $data['website']             = $this->json_array['homepage_url'];
     $data['blog_url']            = $this->json_array['blog_url'];
     $data['category_code']       = $this->json_array['category_code'];
     $data['number_of_employees'] = $this->json_array['number_of_employees'];
     $data['founded']             = join( "/", array( $this->json_array['founded_year'], $this->json_array['founded_month'], $this->json_array['founded_day'] ));
     $data['deadpooled']          = join( "/", array( $this->json_array['deadpooled_year'], $this->json_array['deadpooled_month'], $this->json_array['deadpooled_day'] ));
     $data['description']         = $this->json_array['description'];
     $data['overview']            = self::get_overview( $this->json_array['overview'] );
     $data['relationships']       = self::get_relationships( $this->json_array['relationships'] );
     $data['competitors']         = self::get_competitions( $this->json_array['competitions'] );
     $data['providerships']       = self::get_providerships( $this->json_array['providerships'] );
     $data['total_money_raised']  = $this->json_array['total_money_raised'];
     $data['funding_rounds']      = self::get_funding_rounds( $this->json_array['funding_rounds'] );
     $data['acquisition']         = $this->json_array['acquisition'];
     $data['acquisitions']        = self::get_acquisitions( $this->json_array['acquisitions'] );
     $data['invest']              = self::get_invest( $this->json_array['investments'] );
     return $data;
   }

   function get_relationships( $relation_ships ){
      $output = array();
      foreach( $relation_ships as $value ){
         if( $value['is_past'] ){
            $output[] = "Past " . $value['title'] . " : " . $value['person']['first_name'] . " " . $value['person']['last_name'] . "\r   " . PEOPLE_URL . $value['person']['permalink'];
         }else{
            $output[] = $value['title'] . " : " . $value['person']['first_name'] . " " . $value['person']['last_name'] . "\r   " . PEOPLE_URL . $value['person']['permalink'];
         }
      }
      return join( "\r", $output );
   }

   function get_competitions( $competitions ){
      $output = array();
      foreach( $competitions as $value ){
         $output[] = $value['competitor']['name'] . "\r   " . COMPANY_URL . $value['competitor']['permalink'];
      }
      return join( "\r\r", $output);
   }

   function get_providerships( $providerships ){
      $output = array();
      foreach( $providerships as $value ){
         if( $value['is_past'] ){
            $output[] = " Past " . $value['title'] . " : " . $value['provider']['name'] . "\r   " . COMPANY_URL . $value['provider']['permalink'];
         }else{
            $output[] = $value['title'] . " : " . $value['provider']['name'] . "\r   " . COMPANY_URL . $value['provider']['permalink'];
         }
      }
      return join( "\r\r", $output);
   }

   function get_offices( $offices ){
      $output = array();
      foreach( $offices as $value ){
         $output[] = $value['description'] . " : " . trim ( join(" ", array($value['address2'], $value['address1'], $value['zip_code'], $value['city'], $value['state_code'], $value['country_code'] )) );
      }
      return join( "\r\r", $output);

   }

   function get_invest( $investments ){
      $output = array();
      foreach( $investments as $value ){
#         $value['source_description'] = str_replace('"', '""', $value['source_description']);
         $output[] = $value['funding_round']['round_code'] . " : " . $value['funding_round']['raised_currency_code'] . ' ' . $value['funding_round']['raised_amount'] . " " .
                     join( "/", array( $value['funding_round']['funded_year'] , $value['funding_round']['funded_month'], $value['funding_round']['funded_day'] ) ) . "\r" .
                     $value['funding_round']['company']['name'] . "\r   " . COMPANY_URL . $value['funding_round']['company']['permalink'] . "\r" .
                     $value['funding_round']['source_description'] . "\r   "  . $value['funding_round']['source_url'];
      }
      return join( "\r\r", $output);
   }

   function get_acquisitions( $acquisitions ){
      $output = array();
      foreach( $acquisitions as $value ){
#         $value['source_description'] = str_replace('"', '""', $value['source_description']);
         $output[] = $value['company']['name'] . " : " . $value['price_amount'] . " " . $value['price_currency_code'] . " " . $value['term_code'] . " " .
                     join( "/", array( $value['acquired_year'] , $value['acquired_month'], $value['acquired_day'] ) ) . "\r   " .
                     COMPANY_URL . $value['company']['permalink'] . "\r".
                     $value['source_description']  . "\r   " . $value['source_url'];
      }
      return join( "\r\r", $output);
   }

   function get_funding_rounds( $funding_rounds ){
      $output = array();
      foreach( $funding_rounds as $value ){
#         $value['source_description'] = str_replace('"', '""', $value['source_description']);
         $output[] = $value['round_code'] . " : " . $value['raised_currency_code'] . ' ' . $value['raised_amount'] . " " .
                     join( "/", array( $value['funded_year'] , $value['funded_month'], $value['funded_day'] ) ) . "\r" .
                     self::get_investments( $value['investments'] ) . "\r".
                     $value['source_description'] . "\r   "  . $value['source_url'];
      }
      return join( "\r\r", $output);
   }

   function get_investments( $investments ){
      $output = array();
      foreach( $investments as $value ){
         $data     = $value['company']['name'] . " " . $value['company']['permalink'] . "\r   ";
         $data    .= (empty($value['financial_org']))?"":$value['financial_org']['name'] . "   " . COMPANY_URL . $value['financial_org']['permalink'] . " ";
         $data    .= (empty($value['person']))?"":$value['person']['first_name'] . " " . $value['person']['last_name'] . "   " . PEOPLE_URL . $value['person']['permalink'];

         $output[] = $data;
      }
      return join( "\r", $output);
   } 

   function get_overview($overview){
     $overview = self::removeTag( $overview, 'p' );
     $overview = self::removeTag( $overview, 'a' );
     $overview = self::removeTag( $overview, 'em' );
     return $overview;
   }

   function removeTag($str, $name){
       $regx = "/<\/?$name(.*?)>/s";
       return preg_replace($regx, '', $str);
   }

   function remove_tab_break($str){
       $str = trim( preg_replace("/\t|\r/", '', $str) );
       return preg_replace('/<br\/>|<br \/>/', ' ', $str);
   }
}

?>
