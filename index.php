<?php

include_once("modele.php");
include_once("config.php");


if (isset($_REQUEST["nomRep"]))  $nomRep = $_REQUEST["nomRep"];
else $nomRep = false;

if (isset($_REQUEST["action"]))
{
	switch($_REQUEST["action"])
	{
		case 'Creer' : 
		if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != ""))
		if (!is_dir("./" . $_GET["nomRep"])) 
		{
			// A compléter : Code de création d'un répertoire
			mkdir("./" . $_GET["nomRep"]);

			// DM 2022 : Création du répertoire dans la base de données
			$nomRep = $_GET["nomRep"];
			insererRepertoire($nomRep);
		}
		break;

		case 'Supprimer' : 
		if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != ""))
		if (isset($_GET["fichier"]) && ($_GET["fichier"] != ""))
		{
			$nomRep = $_GET["nomRep"];
			$fichier = $_GET["fichier"];
			
			// A compléter : Supprime le fichier image
			unlink($nomRep . "/" . $fichier);
	
			// A compléter : Supprime aussi la miniature si elle existe					
			unlink($nomRep . "/thumbs/" . $fichier);	

			// DM 2022 : Suppression du repertoire dans la base de données
			supprimerPhoto($fichier,$nomRep);

		}
		break;

		case 'Renommer' : 
		if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != ""))
		if (isset($_GET["fichier"]) && ($_GET["fichier"] != ""))
		if (isset($_GET["nomFichier"]) && ($_GET["nomFichier"] != ""))
		{
			$nomRep = $_GET["nomRep"];
			$fichier = $_GET["fichier"];
			$nomFichier = $_GET["nomFichier"]; // nouveau nom 

			// A compléter : renomme le fichier et sa miniature si elle existe
			if (file_exists("./$nomRep/$fichier"))			
				rename("./$nomRep/$fichier","./$nomRep/$nomFichier");

			if (file_exists("./$nomRep/thumbs/$fichier"))			
				rename("./$nomRep/thumbs/$fichier","./$nomRep/thumbs/$nomFichier");

			// DM 2022 : Renommer le fichier dans la base de données
			renommerPhoto($fichier,$nomRep,$nomFichier);
			
			
		}
		break;

		case 'Uploader' : 
		if (!empty($_FILES["FileToUpload"]))
		{

			if (is_uploaded_file($_FILES["FileToUpload"]["tmp_name"]))
			{
				//print("Quelques informations sur le fichier récupéré :<br>");
				//print("Nom : ".$_FILES["FileToUpload"]["name"]."<br>");
				//print("Type : ".$_FILES["FileToUpload"]["type"]."<br>");
				//print("Taille : ".$_FILES["FileToUpload"]["size"]."<br>");
				//print("Tempname : ".$_FILES["FileToUpload"]["tmp_name"]."<br>");
				$name = $_FILES["FileToUpload"]["name"];
				copy($_FILES["FileToUpload"]["tmp_name"],"./$nomRep/$name");

				// créer le répertoire miniature s'il n'existe pas
				if (!is_dir("./$nomRep/thumbs")) 
				{
					mkdir("./$nomRep/thumbs");
				}
					
				$dataImg = getimagesize("./$nomRep/$name");  
				$type= substr($dataImg["mime"],6);// on enleve "image/" 

				// créer la miniature dans ce répertoire 
				miniature($type,"./$nomRep/$name",200,"./$nomRep/thumbs/$name");



				// DM 2022 : On recupere les informations de la photo en fonction de ses metadonnees exif
				$nom = $name;

				// On recuperer les metadonnees de la photo
				$exif = exif_read_data("./$nomRep/$name", 0, true);

				// On recupere la date de prise de vue
				$date = $exif['EXIF']['DateTimeOriginal'];

				// On recupere la largeur et la hauteur de la photo
				$largeur = $exif['COMPUTED']['Width'];
				$hauteur = $exif['COMPUTED']['Height'];

				// On recupere l'id du repertoire dans lequel se trouve la photo
				$idRep = getIdRepertoire($nomRep);

				// On recupere la latitude et la longitude de la photo si elle existe
				if(isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])){
					//Attention, les coordonnées sont stockées sous la forme de fractions, il faut donc les convertir en décimales
					$latitude = convertDMStoDEC($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
					$longitude = convertDMStoDEC($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);

					// On recupere l'adresse de la photo
					$adresse = getAdresseByGPS($latitude, $longitude);

				}
				else{
					$latitude = 0;
					$longitude = 0;
					$adresse = "";
				}

				insererPhoto($nom, $date, $largeur, $hauteur, $latitude, $longitude, $idRep, $adresse,$type);

				ajouterFiligrane($type,"./$nomRep/$name",$date,$adresse);

			}
			else
			{
				echo "pb";
			}
		}

		break;

		case 'Supprimer Repertoire':
			// On ne peut supprimer que des répertoires vide !
			if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != ""))
			{
				// A compléter : Supprime le répertoire des miniatures s'il existe, puis le répertoire principal

				if (is_dir("./$nomRep/thumbs"))
				{
					$rep = opendir("./$nomRep/thumbs"); 		// ouverture du repertoire 
					while ( $fichier = readdir($rep))	// parcours de tout le contenu de ce répertoire
					{

						if (($fichier!=".") && ($fichier!=".."))
						{
							// Pour éliminer les autres répertoires du menu déroulant, 
							// on dispose de la fonction 'is_dir'
							if (!is_dir("./$nomRep/thumbs/" . $fichier))
							{
								unlink("./$nomRep/thumbs/" . $fichier);
							}
						}
					}
					rmdir("./$nomRep/thumbs");
				}

				// répertoire principal
				$rep = opendir("./$nomRep"); 		// ouverture du repertoire 
				while ( $fichier = readdir($rep))	// parcours de tout le contenu de ce répertoire
				{

					if (($fichier!=".") && ($fichier!=".."))
					{
						// Pour éliminer les autres répertoires du menu déroulant, 
						// on dispose de la fonction 'is_dir'
						if (!is_dir("./$nomRep/" . $fichier))
						{
							unlink("./$nomRep/" . $fichier);
							supprimerPhoto($fichier, $nomRep);
						}
						
					}
				}
				
				supprimerRepertoire($nomRep);
				rmdir("./$nomRep");
				$nomRep = false;

			}
		break;
	}
}





function miniature($type,$nom,$dw,$nomMin)
{
	// Crée une miniature de l'image $nom
	// de largeur $dw
	// et l'enregistre dans le fichier $nomMin 


	// lecture de l'image d'origine, enregistrement dans la zone mémoire $im
	switch($type)
	{
		case "jpeg" : $im =  imagecreatefromjpeg ($nom);break;
		case "png" : $im =  imagecreatefrompng ($nom);break;
		case "gif" : $im =  imagecreatefromgif ($nom);break;		
	}

	$sw = imagesx($im); // largeur de l'image d'origine
	$sh = imagesy($im); // hauteur de l'image d'origine
	$dh = $dw * $sh / $sw;

	$im2 = imagecreatetruecolor($dw, $dh);

	$dst_x= 0;
	$dst_y= 0;
	$src_x= 0; 
	$src_y= 0; 
	$dst_w= $dw ; 
	$dst_h= $dh ; 
	$src_w= $sw ; 
	$src_h= $sh ;
	
	imagecopyresized ($im2,$im,$dst_x , $dst_y  , $src_x  , $src_y  , $dst_w  , $dst_h  , $src_w  , $src_h);
	
	
	switch($type)
	{
		case "jpeg" : imagejpeg($im2,$nomMin);break;
		case "png" : imagepng($im2,$nomMin);break;
		case "gif" : imagegif($im2,$nomMin);break;		
	}

	imagedestroy($im);
	imagedestroy($im2);
}


/**
 * Source : https://stackoverflow.com/questions/2526304/php-extract-gps-exif-data 
 * Convertir des coord de DMS ( Degrees / minutes / seconds ) vers decimal
 * @param string $coordinate
 * @param string $hemisphere
 * @return float
 */

function convertDMStoDEC($coordinate, $hemisphere) {

	if (is_string($coordinate)) {
	  $coordinate = array_map("trim", explode(",", $coordinate));
	}
	for ($i = 0; $i < 3; $i++) {
	  $part = explode('/', $coordinate[$i]);
	  if (count($part) == 1) {
		$coordinate[$i] = $part[0];
	  } else if (count($part) == 2) {
		$coordinate[$i] = floatval($part[0])/floatval($part[1]);
	  } else {
		$coordinate[$i] = 0;
	  }
	}
	list($degrees, $minutes, $seconds) = $coordinate;
	$sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
	return $sign * ($degrees + $minutes/60 + $seconds/3600);
}


/**
 * Récupère l'adresse à partir des coordonnées GPS
 * @param float $latitude
 * @param float $longitude
 * @return string
 */
function getAdresseByGPS($latitude, $longitude)
{
	//Récupère l'adresse à partir des coordonnées GPS
	// $latitude : latitude de la photo
	// $longitude : longitude de la photo

	global $apiKey;

	//  On fait la requête à l'API de positionstack pour récupérer l'adresse
	$data= file_get_contents('http://api.positionstack.com/v1/reverse?access_key='.$apiKey.'&query='.$latitude.','.$longitude);
	return $adresse = json_decode($data)->data[0]->label;
}


function ajouterFiligrane($type,$fichier,$date,$adresse=null)
{
	//La date de la prise de photo est ajoutée en filigrane par dessus l'image, dans le coin en bas à droite avec une largeur de 5% de la largeur de l'image
	// $type : type de l'image
	// $fichier : nom du fichier
	// $date : date de la prise de photo

	//On récupère l'image
	switch($type)
	{
		case "jpeg" : $im =  imagecreatefromjpeg ($fichier);break;
		case "png" : $im =  imagecreatefrompng ($fichier);break;
		case "gif" : $im =  imagecreatefromgif ($fichier);break;		
	}
	
	$couleur = imagecolorallocatealpha($im, 255, 255, 255, 50);
	$size = getimagesize($fichier);
	$largeur = $size[0];
	$hauteur = $size[1];
	$taillePolice = ($largeur/75);

	//position de la date
	$positionXdate = $largeur - ($taillePolice)*strlen($date);
	$positionYdate = $hauteur - $taillePolice;

	//position de l'adresse
	$positionXadresse =$taillePolice ;
	$positionYadresse = $hauteur - $taillePolice;


	//On ajoute la date
	imagettftext($im, $taillePolice, 0, $positionXdate, $positionYdate, $couleur, "./arial.ttf", $date);

	//on ajoute l'adresse
	if($adresse!=null)
		imagettftext($im, $taillePolice, 0, $positionXadresse, $positionYadresse, $couleur, "./arial.ttf", $adresse);
	
	//On enregistre l'image
	switch($type)
	{
		case "jpeg" : imagejpeg($im,$fichier);break;
		case "png" : imagepng($im,$fichier);break;
		case "gif" : imagegif($im,$fichier);break;		
	}
	//On détruit l'image
	imagedestroy($im);	


}

?>

<html>
<head>
<style>

.mini
{
	position:relative;
	width:200px;
	height:400px;
	float:left;
	border:1px black solid;
	margin-right:5px;
	margin-bottom:5px;
}
div img
{
	margin : 0 auto 0 auto;
	border : none;
}
div div 
{
	position:absolute;
	bottom:0px;
	width:100%;
	background-color:lightgrey;
	border-top:1px black solid;
	text-align:center;
}

.renommer
{
	width:150px;
}
.btn_renommer
{

	width:35px;
}

</style>
</head>

<body>

<h1>Gestion des répertoires </h1>
<form>
<label>Créer un nouveau répertoire : </label>
<input type="text" name="nomRep"/>
<input type="submit" name="action" value="Creer" />
</form>

<form>
<label>Choisir un répertoire : </label>
<select name="nomRep">
<?php
	$rep = opendir("./"); // ouverture du repertoire 
	while ( $fichier = readdir($rep))
	{
		// On élimine le résultat '.' (répertoire courant) 
		// et '..' (répertoire parent)

		if (($fichier!=".") && ($fichier!=".."))
		{
			// Pour éliminer les autres fichiers du menu déroulant, 
			// on dispose de la fonction 'is_dir'
			if (is_dir("./" . $fichier))
				printf("<option value=\"$fichier\">$fichier</option>");
		}
	}
	closedir($rep);
?>
</select>
<input type="submit" value="Explorer"> <input type="submit" name="action" value="Supprimer Repertoire">
</form>

<?php
	if (!$nomRep)  die("Choisissez un répertoire"); 
	// interrompt immédiatement l'exécution du code php
?>

<hr />
<h2> Contenu du répertoire '<?php echo$_GET["nomRep"]?>' </h2>


<form enctype="multipart/form-data" method="post">
	<input type="hidden" name="MAX_FILE_SIZE" value="10000000">
	<input type="hidden" name="nomRep" value="<?php echo $nomRep; ?>">
	<label>Ajouter un fichier image : </label>
	<input type="file" name="FileToUpload">
	<input type="submit" value="Uploader" name="action">
</form>

<?php
	if(!repertoireExiste($nomRep)) insererRepertoire($nomRep);

	$numImage = 0;
	$rep = opendir("./$nomRep"); 		// ouverture du repertoire 
	while ( $fichier = readdir($rep))	// parcours de tout le contenu de ce répertoire
	{
	
		if (($fichier!=".") && ($fichier!=".."))
		{
			// Pour éliminer les autres répertoires du menu déroulant, 
			// on dispose de la fonction 'is_dir'
			if (!is_dir("./$nomRep/" . $fichier))
			{
				// Un fichier... est-ce une image ?
				// On ne liste que les images ... 
				$formats = ".jpeg.jpg.gif.png";
				if (strstr($formats,strrchr($fichier,"."))) 
				{
					$numImage++;
					$dataImg = getimagesize("./$nomRep/$fichier"); 

					// A compléter : récupérer le type d'une image, et sa taille 
					$width= $dataImg[0];
					$height= $dataImg[1]; 
					$type= substr($dataImg["mime"],6);

					// A compléter : On cherche si une miniature existe pour l'afficher...
					// Si non, on crée éventuellement le répertoire des miniatures, 
					// et la miniature que l'on place dans ce sous-répertoire				

					echo "<div class=\"mini\">\n";
					echo "<a target=\"_blank\" href=\"$nomRep/$fichier\"><img src=\"$nomRep/thumbs/$fichier\"/></a>\n";
					echo "<div>$fichier \n";			
					echo "<a href=\"?nomRep=$nomRep&fichier=$fichier&action=Supprimer\" >Supp</a>\n";
					echo "<br />($width * $height $type)\n";
					echo "<br />\n";

					// DM 2022 : On récupère les données de l'image
					if(photoExiste($fichier,$nomRep)){
						
					$data = getPhoto($fichier,$nomRep)[0];

					// DM 2022 : On ajoute la date de prise de vue

					echo $data["date"];
					echo "<br />\n";
				}
					echo "<form>\n";
					echo "<input type=\"hidden\" name=\"fichier\" value=\"$fichier\" />\n";
					echo "<input type=\"hidden\" name=\"nomRep\" value=\"$nomRep\" />\n";
					echo "<input type=\"hidden\" name=\"action\" value=\"Renommer\" />\n";
					echo "<input type=\"text\" class=\"renommer\" name=\"nomFichier\" value=\"$fichier\" onclick=\"this.select();\" />\n";
					echo "<input type=\"submit\" class=\"btn_renommer\" value=\">\" />\n";
					echo "</form>\n";

					echo "</div></div>\n";

					// A compléter : appeler echo "<br style=\"clear:left;\" />"; si on a affiché 5 images sur la ligne actuelle
					
					if (($numImage%5) ==0)
					echo "<br style=\"clear:left;\" />";
				}
			}
		}

	
	}
	closedir($rep);

	// A compléter : afficher un message lorsque le répertoire est vide
	if ($numImage==0) echo "<h3>Aucune image dans le répertoire</h3>";

?>


</body>
