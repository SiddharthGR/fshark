<?php
namespace fshark;
include_once("include/nitro_response.php");

session_start();

$request_uri = $_SERVER["REQUEST_URI"];

$obj = new nitro();

call_user_func_array(array($obj,"processReq"), array());

class nitro{

   private $response = null;

   public function __construct(){
      $this->response = new nitro_response();
   }

   public function processReq(){
      $args = $_GET;
      if($this->checkArgsForError($args)){
         $this->send_response();
         exit(0);
      }
      $this->processCapReq($args["cap"],$args["filter"],$args["IsProtocolColorScheme"]);
      $this->send_response();
   }

   private function processCapReq($file_name, $disp_filter, $colorCoding){
      $capResopnse = array();
      $output;
      $error;
      if($disp_filter != "" && $disp_filter != "undefined"){
         exec("tshark -r "."caps/".$file_name." -R \"".$disp_filter."\" 2>&1 > caps/__".$file_name, $output , $error);
         if($error != 0){
            $this->response->set_errorcode(-1);
            $this->response->set_message($output);
            return false;
         }
      }
      else {
         exec("tshark -r "."caps/".$file_name." 2>&1 > caps/__".$file_name, $output , $error);
         if($error != 0){
            $this->response->set_errorcode(-1);
            $this->response->set_message($output);
            return false;
         }
      }

      $lines = file("caps/__".$file_name);
      $colors = array("#ff7f2a", "#ffd332", "#1bae44", "#008fc7",
                      "#09c0e3", "#CC5480", "#ef3124", "#ffbe00",
                      "#db4c3c", "#c5881c", "#6a93c7", "#386398",
                      "#669999", "#993333", "#006600", "#990099",
                      "#ff9966", "#99ff99", "#9999ff", "#cc6600",
                      "#33cc33", "#cc99ff", "#ff6666", "#99cc66",
                      "#009999", "#cc3333", "#9933ff", "#ff0000",
                      "#0000ff", "#00ff00", "#ffcc99", "#999999",
                      "#ff3333", "#6666ff", "#ffff00", "#000000");
      
      $defaultColorArray = array( "#dff0d8", "#f2dede", "#d9edf7", "#faf2cc");
      $protoArr = array();
      $packetIndex = 0;
      if(count($lines) != 0){
         foreach ($lines as $line_num => $line) {
            $returnValue = preg_split('/( | -> |->)/', $line, 7, PREG_SPLIT_NO_EMPTY);
            $pktnum = $returnValue[0];
            $time = $returnValue[1];
            $src = $returnValue[2];
            $dst = $returnValue[3];
            $proto = $returnValue[4];
            $length = $returnValue[5];
            $info = $returnValue[6];
            if($colorCoding == "true"){
               if(!in_array($proto, $protoArr))
                  array_push($protoArr,$proto);
               $color = $colors[array_search($proto, $protoArr)];
            } else {
               $color = $defaultColorArray[$packetIndex++ % 4];
            }
            $capResopnse[$line_num] = array("number" => $pktnum, "time"   => $time,
                                        "src"    => $src       , "dst"    => $dst,
                                        "proto"  => $proto     , "length" => $length,
                                        "info"   => $info      , "color"  => $color
                                   );
         }
         $this->response->add_response("cap",$capResopnse);
      }
      else {
         // nothing to display
      }
   }
   private function checkArgsForError($args){
      foreach($args as $key => $value){
         if($key == "cap" && $value == "undefined"){
            $this->response->set_errorcode(-1);
            $this->response->set_message("Plese provide valid cap file");
            return true;
         }
      }
      return false;
   }

   private function send_response(){
      header("Content-type: application/json; charset=utf-8");
      print $this->response->to_string();
   }
}
?>
