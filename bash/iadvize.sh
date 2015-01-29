#!/bin/bash
#Script de deploiement

echo Déploiement `date +%Y-%m-%d_%H-%M`

# EXPORT DU CODE
racine='https://github.com/MaxenceMinguet/test-iadvize.git'
dest='/opt/iadvize'
workingFolder='/tmp/iadvize-install'
echo $racine

mkdir -p $dest

log='/var/log/iadvize'

if [ -e $log ];
then
    #le dossier existe
    echo 'Le dossier '$log' existe déjà';
else
    #le dossier n'existe pas
    mkdir -vp $log
    chown -vR www-data:www-data $log
fi

rm -rf $workingFolder
mkdir -p $workingFolder
cd $workingFolder
git clone $racine -b master --depth=1

#Exécution de la suite uniquement si le git clone a réussi
if [[ $? == 0 ]]
then	

	#Remplacement des données
	rm -rf $dest/*
	mv $workingFolder/iadvize/php/* $dest/

	rsync -va $workingFolder/iadvize/etc/apache2/sites-available/iadvize.conf /etc/apache2/sites-available/iadvize.conf 
	a2ensite iadvize.conf

	# On recharge apache au cas où il y ait eu des modifs de vhost
	service apache2 reload

	#Mise à jour cron
	echo MISE A JOUR CRON
	rsync -va $workingFolder/iadvize/etc/cron.d/put-last-post-vdm /etc/cron.d/put-last-post-vdm

else
	echo "Echec du git clone"
fi

php ../composer.phar update
