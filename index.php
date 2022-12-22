<?php

//on inclut la bibliotheque sql
include_once "modele.php";

if (isset($_REQUEST["nomRep"])) 
	$nomRep = $_REQUEST["nomRep"];
else 
	$nomRep = false;

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
			insererRepertoire($_GET["nomRep"]);
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

/*************************************************************************************************/
/********************************MODIFICATIONS POUR DM********************************************/
/*************************************************************************************************/


				//Lecture des méta-données exif
				$exif = exif_read_data("./$nomRep/$name");
				
				//Lors de l'ajout d'une image, les méta-données exif sont lues et stockées dans une base de données
				//si l'image existe déjà, on supprime les anciennes méta-données (supprimerPhoto($name,$nomRep)) et on ajoute les nouvelles
				if(photoExiste($name,$nomRep)){
					supprimerPhoto($name,$nomRep);
				}
				stockerMetaDonnees($name, $nomRep, $exif,$type);		
				
				$photo =getPhoto($name,$nomRep);
				//La date de la prise de photo est ajoutée en filigrane par dessus l'image, dans le coin en bas à droite.
				//On récupère la date de la photo si elle existe
				if($photo[0]['date']!=null){
					ajouterDatePhoto($type,"./$nomRep/$name",$photo[0]['date']);	
				}		
				
				if($photo[0]['adresse']!=null){
					ajouterAdressePhoto($type,"./$nomRep/$name",$photo[0]['adresse']);
				}


/*************************************************************************************************/
/*************************************************************************************************/
/*************************************************************************************************/
				
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





/*************************************************************************************************/
/******************************MODIFICATIONS POUR DM**********************************************/
/*************************************************************************************************/
//Ici toutes les foctions qui vont nous servir pour le projet





function stockerMetaDonnees($nom, $nomRep, $exif,$type)
{
	// Stocke les méta-données exif dans la base de données
	// $nom : nom du fichier
	// $nomRep : nom du répertoire
	// $exif : tableau contenant les méta-données exif

	//On récupère la date de la photo si elle existe sinon on met 0
	if(isset($exif['DateTimeOriginal']))
	{
		$date = $exif['DateTimeOriginal'];
		$date = date("Y-m-d H:i:s", strtotime($date));
		
	}
	else
	{
		$date = 0;
	}
	//On récupère la largeur de l'image
	$largeur = $exif['COMPUTED']['Width'];

	//On récupère la hauteur de l'image
	$hauteur = $exif['COMPUTED']['Height'];
	//On récupère la latitude de l'image si elle existe sinon on met 0
	if(isset($exif['GPSLatitude']))
	{
		//on convertit la latitude de exif qui est en DMS en degrés décimaux
		$latitude = convertirDMSenDD($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
		//on convertit la longitude de exif qui est en DMS en degrés décimaux
		$longitude = convertirDMSenDD($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
		$adresse = getAdresseByGPS($latitude, $longitude);

	}
	else
	{
		$longitude = 0;
		$latitude = 0;
		$adresse = null;
	}
	//On récupère l'altitude de l'image si elle existe
	if(isset($exif['GPSAltitude']))
	{
		$altitude = $exif['GPSAltitude'];
	}
	else
	{
		$altitude = 0;
	}
	//On recupere l'id du répertoire
	$idRepertoire= getIdRepertoire($nomRep);
	//On construit la requête SQL
	$Result= insererPhoto($nom, $date, $largeur, $hauteur, $latitude, $longitude, $altitude, $idRepertoire, $adresse,$type);

}

function ajouterDatePhoto($type,$fichier,$date){
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
	
	//On récupère la couleur de la date
	$couleur = imagecolorallocatealpha($im, 255, 255, 255, 50);
	//On récupère la taille de l'image
	$size = getimagesize($fichier);
	//On récupère la police
	$police = "arial.ttf";
	//On récupère la largeur de l'image
	$largeur = $size[0];
	//On récupère la hauteur de l'image
	$hauteur = $size[1];

	//On calcule la taille de la police
	$taillePolice = ($largeur/(strlen($date)))*0.25;
	//On calcule la position de la date
	$positionX = $largeur - $taillePolice*strlen($date);
	$positionY = $hauteur - $taillePolice;


	//On ajoute la date
	imagettftext($im, $taillePolice, 0, $positionX, $positionY, $couleur, $police, $date);
	
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

function convertirDMSenDD($coordinate, $hemisphere){
	//Convertit la latitude ou la longitude de exif qui est en DMS en degrés décimaux
	// $exifTps : tableau contenant les valeurs de la latitude ou de la longitude
	// $exifRef : référence de la latitude ou de la longitude

	//Source : https://stackoverflow.com/questions/2526304/php-extract-gps-exif-data

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


function getAdresseByGPS($latitude, $longitude){
	//Récupère l'adresse à partir des coordonnées GPS
	// $latitude : latitude de la photo
	// $longitude : longitude de la photo

	//On fait la requête à l'API de positionstack pour récupérer l'adresse
	$data= file_get_contents('http://api.positionstack.com/v1/reverse?access_key=dba7a09098fa39d9a5be8f4d2f23b0f0&query='.$latitude.','.$longitude);
	return $adresse = json_decode($data)->data[0]->label;
}


function ajouterAdressePhoto($type,$fichier,$adresse){
	//On affiche l'adresse de la photo dans le coin en bas à gauche avec une largeur de 5% de la largeur de l'image
	// $type : type de l'image
	// $fichier : nom du fichier
	// $adresse : adresse de la photo

	//On récupère l'image
	switch($type)
	{
		case "jpeg" : $im =  imagecreatefromjpeg ($fichier);break;
		case "png" : $im =  imagecreatefrompng ($fichier);break;
		case "gif" : $im =  imagecreatefromgif ($fichier);break;		
	}

	//On récupère la couleur de la date
	$couleur = imagecolorallocatealpha($im, 255, 255, 255, 50);
	//On récupère la taille de l'image
	$size = getimagesize($fichier);
	//On récupère la police
	$police = "arial.ttf";
	//On récupère la largeur de l'image
	$largeur = $size[0];
	//On récupère la hauteur de l'image
	$hauteur = $size[1];

	//On calcule la taille de la police
	$taillePolice = ($largeur/(strlen($adresse)))*0.5;
	//On calcule la position de la date
	$positionX =$taillePolice ;
	$positionY = $hauteur - $taillePolice;
	
	//On ajoute la date
	imagettftext($im, $taillePolice, 0, $positionX, $positionY, $couleur, $police, $adresse);

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


/*************************************************************************************************/
/*************************************************************************************************/
/*************************************************************************************************/

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
	<input type="file" name="FileToUpload" accept="image/jpeg, image/gif, image/png, image/jpg">
	<input type="submit" value="Uploader" name="action">
</form>

<?php

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

					$dataImg = getPhoto($fichier,$nomRep)[0]; 

					// A compléter : récupérer le type d'une image, et sa taille 
					$width= $dataImg["largeur"];
					$height= $dataImg["hauteur"]; 
					$type= $dataImg["type"];

					// A compléter : On cherche si une miniature existe pour l'afficher...
					// Si non, on crée éventuellement le répertoire des miniatures, 
					// et la miniature que l'on place dans ce sous-répertoire				

					echo "<div class=\"mini\">\n";
					echo "<a target=\"_blank\" href=\"$nomRep/$fichier\"><img src=\"$nomRep/thumbs/$fichier\"/></a>\n";
					echo "<div>$fichier \n";			
					echo "<a href=\"?nomRep=$nomRep&fichier=$fichier&action=Supprimer\" >Supp</a>\n";
					echo "<br />($width * $height $type)\n";
					//La date de la prise de photo
					$date = $dataImg["date"];
					echo "<br />$date\n";
					echo "<br />\n";

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

<?php

/*TODO:
Galerie d'images géolocalisées
On ajoutera les fonctionnalités suivantes :
Lors de l'ajout d'une image, les méta-données exif sont lues et stockées dans une base de données. 
La date de la prise de photo est ajoutée en filigrane par dessus l'image, dans le coin en bas à droite. Cette information est ajoutée au nom de l'image dans la galerie  
Si l'image dispose de tags de géolocalisation, le serveur émet une requête vers une API de géolocalisation inverse de votre choix (consulter par exemple https://geekflare.com/fr/geolocation-ip-api/) et l'adresse correspondant à la localisation de l'image dans la base de données. Cette information est ajoutée à l'image en bas à gauche
*/
?>


</body>
