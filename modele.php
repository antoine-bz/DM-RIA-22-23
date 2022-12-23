<?php

/*
Partie modèle : on effectue ici tous les traitements sur la base de données (lecture, insertion, suppression, mise à jour).

Quelques squelettes de fonctions vous sont donnés, à titre purement indicatif.
Il n'est pas nécessaire de les respecter, et cette liste n'est pas exhaustive.
*/

include_once("maLibSQL.pdo.php");

function getIdRepertoire($nom) {
  $SQL="SELECT id FROM Repertoires WHERE nom='$nom'";
	return SQLGetChamp($SQL);
}

function insererRepertoire($nom) {
  $SQL="INSERT INTO Repertoires (nom) VALUES ('$nom')";
  return SQLInsert($SQL);
}

function supprimerRepertoire($nom) {
  $SQL="DELETE FROM Repertoires WHERE nom='$nom'";
  return SQLDelete($SQL);
}

function insererPhoto($nom, $date, $largeur, $hauteur, $latitude, $longitude, $idRepertoire, $adresse,$type) {
  $SQL="INSERT INTO Photos (nom, date, largeur, hauteur, latitude, longitude, idRep, adresse,type) VALUES ('$nom', '$date', '$largeur', '$hauteur', '$latitude', '$longitude', '$idRepertoire', '$adresse','$type')";
  return SQLInsert($SQL);
}

function supprimerPhoto($nom,$nomRep) {
  $SQL="DELETE FROM Photos WHERE nom='$nom' AND idRep=(SELECT id FROM Repertoires WHERE nom='$nomRep')";
  return SQLDelete($SQL);
}

function renommerPhoto($nom,$nomRep,$nouveauNom) {
  $SQL="UPDATE Photos SET nom='$nouveauNom' WHERE nom='$nom' AND idRep=(SELECT id FROM Repertoires WHERE nom='$nomRep')";
  return SQLUpdate($SQL);
}

function photoExiste($nom,$nomRep) {
  $SQL="SELECT COUNT(*) FROM Photos WHERE nom='$nom' AND idRep=(SELECT id FROM Repertoires WHERE nom='$nomRep')";
  return SQLGetChamp($SQL);
}

function getPhotos($nomRep) {
  $SQL="SELECT * FROM Photos WHERE idRep=(SELECT id FROM Repertoires WHERE nom='$nomRep')";
  return parcoursRs(SQLSelect($SQL));
}

function getPhoto($nom,$nomRep) {
  $SQL="SELECT * FROM Photos WHERE nom='$nom' AND idRep=(SELECT id FROM Repertoires WHERE nom='$nomRep')";
  return parcoursRs(SQLSelect($SQL));
}


?>
