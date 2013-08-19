<?php

/**
 *
 * Blok defaultniho dynamickeho modulu lite verze
 *
 */

//nadefinovani konstant pro databaze
define("NODATA", 0);
define("SQLITE", 1);
define("MYSQLI", 2);
define("SQLITE3", 3);

class DefaultModule
{
  private $adresa_menu, $typdb, $ukazatel, $var, $trida,
          $cesta, $newcesta, $nazvydb, $pripojeno, $admin_user_id,
          $start, $konec, $dbpredpona, $pathdb, $realyclass, $idukazatel;

/**
 *
 * Vrati absolutni adresu s pathem
 *
 * @return absolutni adresa
 */
  public function AbsolutniUrl()
  {
    $path = dirname($_SERVER["SCRIPT_NAME"]);
    $result = "http://{$_SERVER["SERVER_NAME"]}".($path != "/" ? $path : "")."/";

    return $result;
  }

/**
 *
 * Vypise absolutni cestu ke korenu stranek
 *
 * @return absolutni cesta
 */
  public function AdresarWebu()
  {
    $result = dirname($_SERVER["SCRIPT_FILENAME"]);

    return $result;
  }

/**
 *
 * Prepocet velikosti
 *
 * pouziti: <strong>$velikost = $this->var->main[0]->NactiFunkci("Funkce", "Velikost", filesize($soubor));</strong>
 *
 * @param size zmerena velikost
 * @return prepocitana velikost
 */
  public function Velikost($size)
  {
    $symbol = array("bajtů", "kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");

    $exp = 0;
    $converted_value = 0;
    if ($size > 0)
    {
      $exp = floor(log($size) / log(1024));
      $converted_value = ($size / pow(1024, floor($exp)));
    }

    $result = sprintf(($exp == 0 ? "%d {$symbol[$exp]}" : "%.2f {$symbol[$exp]}"), $converted_value);

    return $result;
  }

/**
 *
 * Rekurzivni/nerekurzivni mereni velikosti souboru/slozek
 *
 * @param cesta cesta adresare
 * @param rekurzivne rekurzivni prochazeni true/false
 * @return velikost v zakladnich jednotkach
 */
  public function VelikostAdresare($cesta, $rekurzivne = false)
  {
    if (file_exists($cesta) &&  //existuje-li
        is_readable($cesta))  //a jde-li z neho cist
    {
      $cesta = (is_dir($cesta) ? $cesta : dirname($cesta));
      $handle = opendir($cesta);
      $sum = 0;
      while($soub = readdir($handle))
      {
        if ($soub != "." && $soub != "..")
        {
          switch (@filetype("{$cesta}/{$soub}"))  //umlceni warningu
          {
            case "dir":
              $sum += ($rekurzivne ? $this->VelikostAdresare("{$cesta}/{$soub}", $rekurzivne) : 0);
            break;

            case "file":
              $sum += filesize("{$cesta}/{$soub}");
            break;
          }

        }
      }
      closedir($handle);
    }

    return $sum;
  }

/**
 *
 * Filtr na koncovky
 *
 * @param nazev nazev souboru
 * @param filtr pole filtru
 * @return boolean zdali obsahuje danou koncovku
 */
  private function FiltrKoncovek($nazev, $filtr)
  {
    $result = true; //normalne pousti
    if (!Empty($filtr) &&
        is_array($filtr))
    {
      $a = explode(".", strtolower($nazev));  //prevedeni na male a rozdeleni podle tecky

      $result = in_array($a[count($a) - 1], $filtr);  //prohledani pole filtru
    }

    return $result;
  }

/**
 *
 * Vypise soubory ze zadane slozky
 *
 * @param cesta cesta k souborum
 * @param sort pole nastavujici vystupni razeni, array("date/name", "asc/desc")
 * @param filtr pole nastavujuci vystupni koncovky, array("php", "js")
 * @return pole souboru
 */
  public function VypisSouboru($cesta, $sort = NULL, $filtr = NULL)
  {
    $result = NULL;
    if (file_exists($cesta))
    {
      $soub = "";
      $dat = array();
      $handle = opendir($cesta);
      while($soub = readdir($handle))
      {
        if ($soub != "." &&
            $soub != ".." &&
            is_file("{$cesta}/{$soub}"))
        {
          if ($this->FiltrKoncovek($soub, $filtr))  //filtrovani koncovek
          {
            $result[] = $soub;  //nacitani souboru
            $dat[] = filemtime("{$cesta}/{$soub}"); //datum zmeny
          }
        }
      }
      closedir($handle);

      if (!Empty($sort) && is_array($result))
      {
        $result = $this->RazeniPole($result, $dat, $sort);
      }
    }

    return $result;
  }

/**
 *
 * Vypise adresare ze zadane slozky
 *
 * @param cesta cesta k adresarum
 * @param sort pole nastavujici vystupni razeni, array("date/name", "asc/desc")
 * @return pole slozek a souboru
 */
  public function VypisAdresaru($cesta, $sort = NULL)
  {
    $result = NULL;
    if (file_exists($cesta))
    {
      $soub = "";
      $dat = array();
      $handle = opendir($cesta);
      while($soub = readdir($handle))
      {
        if ($soub != "." &&
            $soub != ".." &&
            is_dir("{$cesta}/{$soub}"))
        {
          $result[] = $soub;  //nacitani souboru
          $dat[] = filemtime("{$cesta}/{$soub}"); //datum zmeny
        }
      }
      closedir($handle);

      if (!Empty($sort) && is_array($result))
      {
        $result = $this->RazeniPole($result, $dat, $sort);
      }
    }

    return $result;
  }

/**
 *
 * Aplikuje razeni na pole
 *
 * @param pole vstupni pole dat
 * @param datum datumy souboru
 * @param sort parametry razeni
 * @return serazene pole dle parametru
 */
  private function RazeniPole($pole, $datum, $sort)
  {
    $result = $pole;

    if (!Empty($sort) &&
        is_array($sort) &&  //konfigurace musi byt pole
        is_array($datum)) //vstupni datumy musi byt pole
    {
      switch ($sort[0]) //aplikace razeni
      {
        case "date":  //razeni dle data vytvoreni [filemtime]
          switch ($sort[1])
          {
            case "asc":
              asort($datum);  //serazeni datumu
              $res = "";
              foreach ($datum as $index => $hod)
              {
                $res[] = $result[$index]; //vlozeni serazenych indexu
              }
              $result = $res;
            break;

            case "desc":
              arsort($datum);
              $res = "";
              foreach ($datum as $index => $hod)
              {
                $res[] = $result[$index]; //vlozeni serazenych indexu
              }
              $result = $res;
            break;
          }
        break;

        case "name":  //razeni dle nazvu
          switch ($sort[1])
          {
            case "asc":
              sort($result);
            break;

            case "desc":
              rsort($result);
            break;
          }
        break;
      }
    }

    return $result;
  }

/**
 *
 * Porovnani vstupnich poli a vrati jejich prebytek
 *
 * @param pole1 vzorove pole
 * @param pole2 aktualni pole
 * @return hodnoty ktere v aktualnim poli prebyvaji
 */
  public function PorovnaniPole($pole1, $pole2)
  {
    $result = "";
    if (!is_null($pole1) &&
        is_array($pole1) &&
        count($pole1) != 0 &&
        !is_null($pole2) &&
        is_array($pole2) &&
        count($pole2) != 0)
    {
      $diff = array_diff($pole1, $pole2); //vyhozeni rozdilu
      $result = array_values($diff);  //oprava indexu
    }

    return $result;
  }

/**
 *
 * Zjisti atributy opravneni souboru
 *
 * @param cesta cesta k souboru
 * @param oktal true/false - vraci oktal/vraci standartni textovy rwx
 * @return text opravneni
 */
  public function ZjistiAtributy($cesta, $oktal = false)
  {
    $result = "";
    if (file_exists($cesta))
    {
      $perms = fileperms($cesta); //nacte prava v int

      if ($oktal) //pocta ctal a nebo plny tvar
      {
        $result = substr(decoct($perms), -4);
      }
        else
      {
        if (($perms & 0xC000) == 0xC000)
        {
          // Socket
          $result = "s";
        }
          elseif (($perms & 0xA000) == 0xA000)
        {
          // Symbolic Link
          $result = "l";
        }
          elseif (($perms & 0x8000) == 0x8000)
        {
          // Regular
          $result = "-";
        }
          elseif (($perms & 0x6000) == 0x6000)
        {
          // Block special
          $result = "b";
        }
          elseif (($perms & 0x4000) == 0x4000)
        {
          // Directory
          $result = "d";
        }
          elseif (($perms & 0x2000) == 0x2000)
        {
          // Character special
          $result = "c";
        }
          elseif (($perms & 0x1000) == 0x1000)
        {
          // FIFO pipe
          $result = "p";
        }
          else
        {
          // Unknown
          $result = "u";
        }

        //Owner
        $result .= (($perms & 0x0100) ? "r" : "-");
        $result .= (($perms & 0x0080) ? "w" : "-");
        $result .= (($perms & 0x0040) ?
                    (($perms & 0x0800) ? "s" : "x" ) :
                    (($perms & 0x0800) ? "S" : "-"));
        // Group
        $result .= (($perms & 0x0020) ? "r" : "-");
        $result .= (($perms & 0x0010) ? "w" : "-");
        $result .= (($perms & 0x0008) ?
                    (($perms & 0x0400) ? "s" : "x" ) :
                    (($perms & 0x0400) ? "S" : "-"));
        // World
        $result .= (($perms & 0x0004) ? "r" : "-");
        $result .= (($perms & 0x0002) ? "w" : "-");
        $result .= (($perms & 0x0001) ?
                    (($perms & 0x0200) ? "t" : "x" ) :
                    (($perms & 0x0200) ? "T" : "-"));
      }
    }

    return $result;
  }

/**
 *
 * Vraceni odpocitavaneho casu pro vypocet delky provaden skryptu
 *
 * @return cas stopek v ms
 */
  public function MeritCas() //funkce pro vrácení času
  {
    $cas = explode(" ", microtime());
    $result = $cas[1] + $cas[0];

    return $result;
  }

/**
 *
 * Kalkulace odpocitavani
 *
 * @param start pocatecni cas
 * @param konec koncovy cas
 * @return uplynuly cas v sec.
 */
  public function KalkulaceCas($start, $konec)
  {
    $result = Abs(Round(($konec - $start) * 10000) / 10000); //Abs, vypocet

    return $result;
  }

/**
 *
 * Vynorovani se ze slozek, bliz ke korenu
 *
 * @param cesta vstupni cesta
 * @return vynorenou cestu
 */
  public function FileVynorovani($cesta)
  {
    $poc = 0;
    $prvni = $cesta;
    //cyklicke vynorovani ze slozek(sestup)
    if (!file_exists($cesta)) //pokud cesta neexistuje zanoruje
    {
      while (!file_exists($cesta))
      {
        $cesta = "../{$cesta}";
        $poc++;
        if ($poc > 10)  //kontrola zacykleni
        {
          $cesta = $prvni;  //vrati puvodni cestu kdyz se zacykli
          break;
        }
      }
    }

    return $cesta;
  }

/**
 *
 * Vytvari slozky dle konfiguracniho pole az do libovolnych zanoreni
 *
 * pole:
 * array(array("adresar", "adresar1", "adresar2"))
 *
 * @param pole, vstupni pole vytvarenych adresaru
 */
  public function ControlCreateDir($pole)
  {
    foreach ($pole as $hodnoty) //prochazi hlavni pole polozek
    {
      $slozka = ""; //vynulovani generovaneho nazvu slozek
      foreach ($hodnoty as $polozka)  //prochazi kazde jeden smer slozek
      {
        $slozka .= "{$polozka}/"; //scita jmeno slozky
        if (!file_exists($slozka))  //overuje existenci
        {
          mkdir($slozka, 0777); //vytvari slozky
        }
      }
    }
  }

/**
 *
 * Vytvari mezery v cisle, po tisicech
 *
 * @param cislo vstupni cislo
 * @param desetinne odelovac desetinne carky
 * @param mezera znak mezery mezi tisicama
 * @return cislo s mezerama
 */
  public function MezeraCisla($cislo, $desetinna = ".", $mezera = " ")
  {
    $result = number_format($cislo, 0, $desetinna, $mezera);

    return $result;
  }

/**
 *
 * Prevadi hex reprezentaci (s #) barev na dec reprezentaci
 *
 * @param hex hex barva, s delkou 3 nebo 6
 * @return pole v dec podobe
 */
  public function PrevodNaRGB($hex)
  {
    $result = "";
    $hex = array_slice(str_split($hex), 1); //orezani #
    if (count($hex) == 3)
    { //vypocet 3 znakeho cisla na 6 znake a prevede na dec
      foreach ($hex as $kod)
      {
        $result[] = hexdec("{$kod}{$kod}");
      }
    }
      else
    if (count($hex) == 6)
    { //slouceni po dvojcich a prevedeni cisla na dec
      foreach (array_chunk($hex, 2) as $kod)
      {
        $result[] = hexdec(implode("", $kod));
      }
    }
      else
    {
      $result = NULL;
    }

    return $result;
  }

/**
 *
 * Vygeneruje nahodnou rgb barvu (s #) v 3 nebo 6 mistnem tvaru zobrazeni
 *
 * @param min minimalni barva v 3 a nebo 6 tvaru
 * @param max maximalni barva v 3 a nebo 6 tvaru
 * @return nahodna barva v zadanem limitu
 */
  public function NahodnaRGBBarva($min, $max)
  {
    $result = "";
    $min = array_slice(str_split($min), 1); //orezani #
    $max = array_slice(str_split($max), 1); //orezani #
    if (count($min) == count($max))
    {
      if (count($min) == 3)
      {
        $result[] = "#";  //vlozeni #
        foreach ($min as $index => $kod)
        { //prevedeni na dec, vypocet rand, prevedeni na hex
          $result[] = dechex(rand(hexdec("{$kod}{$kod}"), hexdec("{$max[$index]}{$max[$index]}")));
        } //slouceni dvojic
        $result = implode("", $result);
      }
        else
      if (count($min) == 6)
      {
        $min = array_chunk($min, 2);
        $max = array_chunk($max, 2);
        $result[] = "#";  //vlozeni #
        foreach ($min as $index => $kod)
        {
          $result[] = dechex(rand(hexdec(implode("", $kod)), hexdec(implode("", $max[$index]))));
        }
        $result = implode("", $result);
      }
        else
      {
        $result = NULL;
      }
    }

    return $result;
  }

}
?>
