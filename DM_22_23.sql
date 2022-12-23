    /*Table Photos*/
    CREATE TABLE `Photos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `idRep` int(11) NOT NULL,
    `nom` varchar(255) NOT NULL,
    `date` datetime NOT NULL,
    `largeur` int(11) NOT NULL,
    `hauteur` int(11) NOT NULL,
    `type` varchar(255) NOT NULL,
    `longitude` float NOT NULL,
    `latitude` float NOT NULL,
    `adresse` varchar(1000) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

    /* Table: Repertoires */
    CREATE TABLE `Repertoires` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nom` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;


    /*relation entre les photos et les répertoires*/
    ALTER TABLE `Photos`
    ADD CONSTRAINT `Photos_ibfk_1` FOREIGN KEY (`idRep`) REFERENCES `Repertoires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
