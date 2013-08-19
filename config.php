<?php

include_once "modul.php";

class Konfig extends DefaultModule
{
  public $dirfont = "ttf/";
  public $dirfontobr = "previews/";
  public $safegen = 200;
  public $fontwrap = 60;
  public $fonttext = "a b c d e f g h i j k l m n o p q r s t u v w x y z\n\n0 1 2 3 4 5 6 7 8 9  + - * / ^ √\n\nA B C D E F G H I J K L M N O P Q R S T U V W X Y Z\n\ně š č ř ž ý á í é ú ů ! ? ( )\n\nĚ Š Č Ř Ž Ý Á Í É Ú Ů";
  public $fontsize = 40;
  public $fontpadding = array(20, 20, 20, 20);
  public $fontpozadi = "#000";
  public $fontbarvafontu = "#fff";
}

?>
