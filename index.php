<?php

include_once "config.php";
require 'Neon.php';

  /**
   * generator fontu
   *
   * @package stable
   * @author geniv & Martin[cze]
   * @version 2.02
   */
  class FontGen extends Konfig {

    private $configure = null;

    /**
     * defaultni konstruktor
     *
     * @since 1.00
     * @param void
     */
    public function __construct() {

      $this->configure = Neon::decode(file_get_contents('config.neon'));

      if (!file_exists($this->configure['dirfont'])) {
        mkdir($this->configure['dirfont'], 0777, true);
      }


//TODO opravit vypis a spravne zaradit do metod
//~ var_dump($this->listFiles($this->configure['dirfont'], array('ttf')));


      $absolutni_url = $this->AbsolutniUrl();



      if (file_exists($this->configure['dirfont'])) {
        $cesta = isset($_POST['cesta']) ? $_POST['cesta'] : $this->configure['dirfont'];
        $obrcesta = isset($_POST['obrcesta']) ? $_POST['obrcesta'] : $this->configure['dirfontobr'];

        $soubory = $this->VypisSouboru($cesta, null, array("ttf"));
        $poc = count($soubory);

        if (!isset($_POST["tlacitko"])) {
          $fonty = "";
          if (is_array($soubory)) {
            $fonty = "<kbd>".implode("</kbd><kbd>", $soubory)."</kbd>";
          }

          $result = <<<T
<!DOCTYPE html>
  <html>
  <head>
    <title>Generátor náhledú ttf fontů</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/kube.min.css" />
    <link rel="stylesheet" type="text/css" href="css/master.css" />
  </head>
  <body>
    <div class="units-container">
      <div class="units-row">
        <div class="unit-100">
          <br />
          <h3>Generátor náhledú ttf fontů</h3>
          <hr />
        </div>
      </div>
      <div class="units-row">
        <div class="unit-50">
          <form action="" method="post" class="forms">
            <fieldset>
              <legend>Section</legend>
              <label>
                Cesta ke složce s fontama
                <input type="text" name="cesta" value="{$cesta}" class="width-30">
                <div class="forms-desc">Složka musí existovat!</div>
              </label>
              <label>
                Cesta ke složce s náhledy
                <input type="text" name="obrcesta" value="{$obrcesta}" class="width-30">
                <div class="forms-desc">Složka musí existovat!</div>
              </label>
              <p>
                <input type="submit" name="tlacitko" value="Vygenerovat náhledy" class="btn" />
              </p>
            </fieldset>
          </form>
        </div>
        <div class="unit-50">
          <p class="zero text-centered">Počet fontů: <strong>{$poc}</strong></p>
          <hr />
          <p class="zero text-centered uprava-hr">Výpis fontů ze složky <strong>{$cesta}</strong>:</p>
          <hr />
          <p class="zero">
            {$fonty}
          </p>
        </div>
      </div>
      <footer>
        <p class="small text-centered zero">Created by <a href="http://www.gfdesign.cz/" title="GF Design - Tvorba webových stránek a systémů" class="color-gray-light">GF design</a></p>
      </footer>
    </div>
  </body>
  </html>
T;
        } else {
          //vytvari potrebne slozky
            $this->ControlCreateDir(array(array($cesta),
                                          ));
          //rozdeleni na bloky
          $rozgen = array_chunk($soubory, $this->safegen);
          //prochazeni bloku fontu
          $row = array();
          foreach ($rozgen as $index => $blokgen) {
            $p = 0;
            //vypis bloku fontu
            foreach ($blokgen as $soubor) {
              $font = "{$cesta}/{$soubor}";
              $obrfont = "{$obrcesta}/{$soubor}.png";
              if (!file_exists($obrfont)) {
                $text = wordwrap(html_entity_decode($this->fonttext, NULL, "UTF-8"), $this->fontwrap);

                $font_size = $this->fontsize;
                $font_file = $font;
                $bbox = @imagettfbbox($font_size, 0, $font_file, $text);
                //nacitani paddingu TOP RIGHT BOTTOM LEFT
                $padding = $this->fontpadding;
                $p_top = $padding[0];
                $p_right = $padding[1];
                $p_bottom = $padding[2];
                $p_left = $padding[3];
                //redeklarace typu pro jistotu
                settype($p_top, "integer");
                settype($p_right, "integer");
                settype($p_bottom, "integer");
                settype($p_left, "integer");

                //vypocet sirky a vysky
                $width = abs(($bbox[2] + $p_right) - ($bbox[0] - $p_left));
                $height = abs(($bbox[7] - $p_top) - ($bbox[1] + $p_bottom));

                //generovani nahledu
                $im = imagecreatetruecolor($width, $height);
                list($bar1, $bar2, $bar3) = $this->PrevodNaRGB($this->fontpozadi);
                $pozadi = imagecolorallocate($im, $bar1, $bar2, $bar3); //nastaveni barvy
                imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $pozadi);  //vyplneni pozadi barvou
                list($bar1, $bar2, $bar3) = $this->PrevodNaRGB($this->fontbarvafontu);
                $color_font = imagecolorallocate($im, $bar1, $bar2, $bar3); //nastaveni barvy

                //vypocet x a y pozice
                $pos_x = abs($bbox[6] - $p_left); //levy horni roh
                $pos_y = abs($bbox[7] - $p_top);
                imagettftext($im, $font_size, 0, $pos_x, $pos_y, $color_font, $font_file, $text);
                if (imagepng($im, $obrfont))
                {
                  $row[] = "<kbd>{$soubor}.png</kbd>";
                  $p++;
                }
              }
                else {
                //$row[] = "{$soubor}<img src=\"{$obrfont}\" alt=\"{$soubor}\"><br /><br />";
              }
            }

            if ($p == $this->safegen) { //zastaveni po zadanem poctu
              $row[] = ""; // <p>generovani bloku: {$index}</p>
              break;
            } else {
              $row[] = "<br /><br />"; // <p>blok: {$index} hotovo</p>
            }
          }

          $row = implode($row);



        $result = "<!DOCTYPE html>
<html>
<head>
	<title>Generátor náhledú ttf fontů</title>
	<meta charset=\"utf-8\">
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"css/kube.min.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"css/master.css\" />
</head>
<body>
	<div class=\"units-container\">
    <div class=\"units-row\">
      <div class=\"unit-100\">
        <br />
        <h3>Generátor náhledú ttf fontů</h3>
        <hr />
      </div>
    </div>
    <div class=\"units-row\">
      <h4>Náhledy byly vygenerovány</h4>
      <p>
        <br />
        {$row}
      </p>
      <p>
        <a href=\"{$absolutni_url}\" class=\"btn\" title=\"\">Pokračovat</a>
      </p>
    </div>
    <footer>
      <p class=\"small text-centered zero\">Created by <a href=\"http://www.gfdesign.cz/\" title=\"GF Design - Tvorba webových stránek a systémů\" class=\"color-gray-light\">GF design</a></p>
    </footer>
  </div>
</body>
</html>";
        }
      } else {
      $result = <<<T
<!DOCTYPE html>
<html>
<head>
  <title>Generátor náhledú ttf fontů</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="css/kube.min.css" />
  <link rel="stylesheet" type="text/css" href="css/master.css" />
</head>
<body>
  <div class="units-container">
    <div class="units-row">
      <div class="unit-100">
        <br />
        <h3>Generátor náhledú ttf fontů</h3>
        <hr />
      </div>
    </div>
    <div class="units-row">
      <h4>Složka: {$this->dirfont} neexistuje!</h4>
      <br />
      <p>
        <a href="{$absolutni_url}" class="btn" title="">Obnovit</a>
      </p>
    </div>
    <footer>
      <p class="small text-centered zero">Created by <a href="http://www.gfdesign.cz/" title="GF Design - Tvorba webových stránek a systémů" class="color-gray-light">GF design</a></p>
    </footer>
  </div>
</body>
</html>
T;
      }

      echo $result;
    }

    /**
     * nacte soubory ze slozky
     *
     * @since 2.00
     * @param string path cesta adresare
     * @param array suffix pole vypisovanych koncovek
     * @return array pole souboru
     */
    private function listFiles($path, $suffix = null) {
      $result = null;
      foreach (new \DirectoryIterator($path) as $item) {
        if ($item->isFile() && !$item->isDot()) {
          if (in_array(strtolower($item->getExtension()), $suffix)) {
            $result[] = $item->getFilename();
          }
        }
      }
      return $result;
    }

    public function render() {
      $result = '';

      return $result;
    }

    /**
     *
     *
     * @since 1.00
     * @param
     * @return
     */
    public function __toString() {
      return $this->render();
    }
  }

  $gen = new FontGen();

  echo $gen;