RES - Laboratoire Infrastructure

Maxime Vulliens

## Task1 - Static HTTP

Intallation de Docker : https://store.docker.com/editions/community/docker-ce-desktop-mac

Ensuite il faut commencer par créer une machine virtuelle dans laquelle seront créé nos containers.

```bash
Maxime$ docker-machine create --driver=virtual_box vbox-test
Maxime$ docker-machine ls
NAME        ACTIVE   DRIVER       STATE     URL                         SWARM   DOCKER        ERRORS
vbox-test   -        virtualbox   Running   tcp://192.168.99.100:2376           v18.05.0-ce   
Maxime$ docker-machine env vbox-test
export DOCKER_TLS_VERIFY="1"
export DOCKER_HOST="tcp://192.168.99.100:2376"
export DOCKER_CERT_PATH="/Users/Maxime/.docker/machine/machines/vbox-test"
export DOCKER_MACHINE_NAME="vbox-test"
# Run this command to configure your shell: 
# eval $(docker-machine env vbox-test)
docker-machine ssh vbox-test		# pour se connecter à la machine en ssh
```

Commande : Découvrir container

Dans ce laboratoire, nous allons utiliser l'image officiel de php que l'on trouve sur docker hub à l'adresse suivante :

https://hub.docker.com/_/php/

A des fin de test, il est possible de démarrer un container directement à partir de cette image, mais toutes les configurations que l'on fera à l'intérieur seront perdus lorsque l'on stoppera le container. Cela peut donc être utile pour tester certaines configurations mais il est nécessaire de les reporter dans des fichiers de configurations qui seront copiés dans le container à son lancement. 

Quelques commandes utiles :

```bash
# Permet de démarrer un container à partir d'une image ou d'un build avec une redirection de port en arrière plan (-d)
docker run -p 9090:80 -d "image"	
# Permet d'exécuter un bash à l'intérieur d'un container en exécution
docker exec -it "nom_container" /bin/bash

docker ps	# Liste les containers en exécution
docker logs "nom_container"

# pour tester telnet peut s'avérer un moyen rapide
telnet "ip" po"rt
# Envoie une requête http
GET / HTTP/1.0
Host: test.res.ch

# permet de build un container à partir du répertoire courant 
docker build -t "name" .
# stoppe le container
docker kill "nom_container"

# Récupère l'adresse ip d'un container
docker inspect "nom_container" | grep -i ipaddr

# Permet de coper le contenu de ma machine local dans la VM
docker-machine scp -r docker-images/ vbox-test:/home/docker/
```

Dans la première étape, on va créer un simple serveur web avec une page de bienvenue accueillante que l'on aura trouve en ligne parmis les template bootstrap disponible.

Dans un premier temps il faut créer l'arborescence, on créer déjà un dossier "content" avec un fichier "index.html" avec un contenu simple pour tester la fonctionnalités de notre serveur.

```bash
mkdir docker-images
cd docker-images
mkdir apache-php-image
cd apache-php-image
touch Dockerfile
mkdir content
echo "<h1> welcome </h1>" > content/index.html
```

Puis il faut éditer le Dockerfile avec les informations que l'on trouve sur le Docker Hub:

```dockerfile
FROM php:7.0-apache

RUN apt-get update && \
	apt-get install -y vim

COPY content/ /var/www/html/
```

On y ajouter l'installation de vim pour avoir un éditeur de fichier agréable si besoin. On remarque que l'on coope le contenu de notre dossier "content" dans le dossier "var/www/html" du container ce qui rendra notre page "index.html" directement visible.

A cette étape on peut tester la fonctionnalité de notre serveur en buildant l'image et en la lançante:

```
docker build -t res/apache_php .
docker run -p 8000:80 -d res/apache_php
```

Test :

![intro](/Users/Maxime/Desktop/intro.png)

Maintenant que le serveur est fonctionne, il nous reste plus qu'à trouver un thème agréable pour notre serveur. J'ai décidé d'utiliser celui-ci :

https://startbootstrap.com/template-overviews/one-page-wonder/

Il suffit de copier les sources dans notre répertoire "content"

Puis il faut build notre container et le relancer :

```
docker build -t res/apache_php .
docker run -p 8000:80 -d res/apache_php
```

Test :

![Etape1](/Users/Maxime/Desktop/Etape1.png)

On peut maintenant déposer la première étape sur git.

## Task2

Dans cette étape,  je vais créer du contenu dynamique. Pour faire cela, nous allons utiliser l'image "Node" officiel. 

Créer l'arborescence pour une nouvelle image :

```bash
mkdir express-image
cd express-image
touch Dockerfile
mkdir src

cd src
npm init #instancie une instance node -> remplir informaption -> crée automatiquement package
npm install --save chance #enregisrer la dépendance
npm install --save express #enregisrer la dépendance

touch index.js
```

Editer le Dockerfile:

```bash
FROM node:8.11.2
COPY src /opt/app

CMD ["node", "/opt/app/index.js"]
```

Créer le contenu du fichier index.js:

```json
// Créer une variable à l'aide du constructure fourni par le package chance
var Chance = require('chance');
var chance = new Chance();

// Créer une variable à l'aide du constructure fourni par le package express
var express = require('express');
var app = express();

// permet d'appeler la fonction depuis l'extérieur
app.get('/', function(request, respond) {
	respond.send(generateStudents());
});

// Ecooute sur le port 3000 et répond au demande
app.listen(3000, function() {
	console.log("Accept HTTP requests");
});

// Fonction permettant de générer des étudiants avec un genre, une date d'anniversaire, un nom et un prénom. Le nombre d'étudiant créer est aléatoire entre 0 et 10.
function generateStudents() {
 	var numberOfStudents = chance.integer({min:0,max:10});
	console.log(numberOfStudents);
	var students = [];
	for (var i=0;i<numberOfStudents;i++) {
		var gender = chance.gender();
		var birthYear = chance.year({min:1986,max:1996});
		students.push({fistName:chance.first({gender: gender}), lastname:chance.last(),gender:gender,birthday:chance.birthday({year: birthYear})});
	};
	console.log(students);
	return students;
}
```

A ce moment, on peut builder l'image et la lancer.

```bash
cd /home/docker/docker-images/express-image
docker build -t res/node_js .
docker run -p 9091:3000 -d res/node_js 
```

Test :

![node](/Users/Maxime/Desktop/node.png)

## Task3

Dans cette étape nous allons utiliser un serveur appache comme serveur proxy permettant de redistribuer les requêtes arrivant dans la vm en fonction de leur destinataire, la page statique ou la page dynmaique.

On crée l'arborescence :

```bash
mkdir conf
cd conf/
mkdir sites-available
cd sites-available/
touch 000-default.conf
touch 001-reverese-proxy.conf
```

On édite le Dockerfile :

```dockerfile
FROM php:7.0-apache
COPY conf/ /etc/apache2

RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001-*
```

Il faut stopper les containers pour supprimer les relancer sans la direction de port

```bash
docker kill "container_name"
docker run -d res/node_js  # 172.17.0.3
docker run -d res/apache_php # 172.17.0.2

# récupération des adresse ip avec 
# docker inspect "nom_container" | grep -i ipaddr
```

On édite 001-reverese-proxy.conf:

```
<VirtualHost *:80>
        ServerName test.res.ch

        #ErrorLog ${APACHE_LOG_DIR}/error.log
        #CustomLog ${APACHE_LOG_DIR}/access.log combined

        ProxyPass "/api/students/" "http://172.17.0.3:3000/"
        ProxyPassReverse "/api/sutdents" "http://172.17.0.3:3000/"

        ProxyPass "/" "http://172.17.0.2/"
        ProxyPassReverse "/" "http://172.17.0.2/"

</VirtualHost>
```

Dorénavant pour consulter la page dynamique il faudra joindre la page "/api/students" qui est une bonne pratique.

Créer également le fichier 000-default.conf pour ne pas que le serveur apache considère notre configuration proxy comme celle par défaut.

```
<VirtualHost *:80>
</VirtualHost>
```

On peut maintenant construire l'image puis tester :

```bash
docker build -t res/apache_rp .
docker run -p 8080:80 -d res/apache_rp
```

Pour tester on utilise dans un premier temps telnet :

![test_php](/Users/Maxime/Desktop/test_php.png)

![test_node](/Users/Maxime/Documents/HEIG-VD/Semestre 6/RES/Labo_infrastructure/test_node.png)

Modifier le fichier ```/etc/hosts```  de la machine host et ajouter la ligne 

```bash
192.168.99.100  test.res.ch
```

Cela permettra de taper cela dans le navigateur web ```demo.res.ch``` et qu'il soit résolu comme 192.168.99.100 qui est l'adresse IP de la docker-machine.

## Task4 

Dans cette étape, le but est de créer une intéraction entre nos deux serveurs pour rendre le contenu de notre page web php dynamique à l'aide du container node_js.

On modifier l'image apache-php-image, on reconstruire l'image apache,  on modifie notre hiérarchie pour ajouter un dossier "js" et le fichier "student.js"

```bash
mkdir js
cd js
touch student.js
chmod +x student.js #il est nécessaire de rendre le script exécutable
```

On conserve le même Dockerfile.

```dockerfile
FROM php:7.0-apache

RUN apt-get update && \
	apt-get install -y vim

COPY content/ /var/www/html/
```

Pour tester dans un premier temps au peux commencer par éditer le fichier "student.js" pour qu'il fasse des logs à la console :

```javascript
$(function() {
console.log("Loading Students");
});
```

reconstruire l'image apache :

```bash
docker build -t res/apache_php .
```

Il faut stopper et redémarrer les containrs. Je le fais en leur donnan un nom cette fois-ci:

```bash
docker rm `docker ps -qa`
docker run --name static -d res/apache_php
docker run --name dynamic -d res/node_js 
docker run --name apache_rp -p 8080:80 -d res/apache_rp
```

Ajouter une classe au fichier index.html

```html
<h1 class="java-test">One Page Wonder</h1>
```

![index](/Users/Maxime/Documents/HEIG-VD/Semestre 6/RES/Labo_infrastructure/index.png)



Puis on modifie le fichier java script pour utiliser la fonction que l'on a créer avec node dans la partie 2 du laboratoire :

```javascript
$(function() {
        console.log("Loading Students");

    function loadStudents() {
        $.getJSON( "/api/students/", function( students ) {
            console.log(students[0]);
            var message = "Nobody is here";
            if (students.length > 0) {
                message = students[0].fistName + " " + students[0].lastname;
            }
            $(".java-test").text(message);
        });
    };
    loadStudents();
    setInterval( loadStudents, 1000);
});
```

reconstruire l'image apache :

```bash
docker build -t res/apache_php .
```

Il faut stopper et redémarrer les containrs. Je le fais en leur donnan un nom cette fois-ci:

```bash
docker run --name static -d res/apache_php
```

![dynamic_ref](/Users/Maxime/Desktop/dynamic_ref.png)

On voit bien les requêtes qui se font périodiquement ainsi que le nom qui change sur la page.

## Task5

L'argument "-e" de Docker permet de setter des variables d'environements dans les containers et il est possible de les enchainer :

```bash
docker run -e HELLO=world -e TEST=test -it res/apache_rp /bin/bash

# set une variable d'environnement "HELLO=world"
# "export" pour voir les variables d'environements
```

Dans cette étape nous allons profiter de cette commande pour transmettre l'adresse ip de notre serveur statique et dynamique. Cela nous permet de lui transmettre ces valeurs dynamiquement sans avoir besoin de les mettre en dur dans les fichiers de configuration.

Créer un nouveau script dans le répertoire de configuration du proxy:

```bash
touch apache2-foreground
chmod +x apache2-foreground
```

Contenu du script:

```bash
#!/bin/bash
set -e

## Add setup for RES lab
echo "Start setup RES"
echo "Static application URL: $STATIC_APP"
echo "Dynamic application URL: $DYNAMIC_APP"

rm -f /var/run/apache2/apache2.pid
exec apache2 -D FOREGROUND
```

Modifier le docker file:

```dockerfile
FROM php:7.0-apache
                
RUN apt-get update && \       
        apt-get install -y vim
        
COPY conf/ /etc/apache2
COPY apache2-foreground /usr/local/bin/
                            
RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001-*
```

A cette étape, losque j'essaie de tester,  j'ai une erreur lors du démarrage du serveur apache qui me dit que la variable d'environnement : ${APACHE_RUN_DIR} n'est pas définie en recherchant j'ai trouvé un autre script avec lequel cela fonctionne , on remarque que dans ce script cette variable d'environnement est initialisée explicitement:

Lien de la ressource : https://github.com/docker-library/php/blob/master/5.6/jessie/apache/apache2-foreground

```bash
#!/bin/bash
set -e

# Script find on : https://github.com/docker-library/php/blob/master/5.6/jessie/apache/apache2-foreground
# after checkin for "apache2 foreground docker" on google 

## Add setup for RES lab
echo "Start setup RES"
echo "Static application URL: $STATIC_APP"
echo "Dynamic application URL: $DYNAMIC_APP"


# Note: we don't just use "apache2ctl" here because it itself is just a shell-script wrapper around apache2 which provides extra functionality like "apache2ctl start" for launching apache2 in the background.
# (also, when run as "apache2ctl <apache args>", it does not use "exec", which leaves an undesirable resident shell process)

: "${APACHE_CONFDIR:=/etc/apache2}"
: "${APACHE_ENVVARS:=$APACHE_CONFDIR/envvars}"
if test -f "$APACHE_ENVVARS"; then
	. "$APACHE_ENVVARS"
fi

# Apache gets grumpy about PID files pre-existing
: "${APACHE_RUN_DIR:=/var/run/apache2}"
: "${APACHE_PID_FILE:=$APACHE_RUN_DIR/apache2.pid}"
rm -f "$APACHE_PID_FILE"

# create missing directories
# (especially APACHE_RUN_DIR, APACHE_LOCK_DIR, and APACHE_LOG_DIR)
for e in "${!APACHE_@}"; do
	if [[ "$e" == *_DIR ]] && [[ "${!e}" == /* ]]; then
		# handle "/var/lock" being a symlink to "/run/lock", but "/run/lock" not existing beforehand, so "/var/lock/something" fails to mkdir
		#   mkdir: cannot create directory '/var/lock': File exists
		dir="${!e}"
		while [ "$dir" != "$(dirname "$dir")" ]; do
			dir="$(dirname "$dir")"
			if [ -d "$dir" ]; then
				break
			fi
			absDir="$(readlink -f "$dir" 2>/dev/null || :)"
			if [ -n "$absDir" ]; then
				mkdir -p "$absDir"
			fi
		done

		mkdir -p "${!e}"
	fi
done

php /var/apache2/template/config-template.php > /etc/apache2/sites-available/001-reverse-proxy.conf

exec apache2 -DFOREGROUND "$@"
```

recompiler le reverse proxy

```bash
docker build -t res/apache_rp .
docker run -e STATIC_APP=172.17.0.2:80 -e DYNAMIC_APP=172.17.0.3:3000 res/apache_rp 
# Sans le -d pour voir les erreurs et les #echo du du fichier apache2-foreground
```

 On voir le message à la console :

![ip](/Users/Maxime/Desktop/ip.png)Créer un scripte dans template nommé "config_template.php", ce script permettra de récupérer les variables d'environnement données en paramètres en lancement de l'application et ainsi de récrire le fichier 001-reverse-proxy.conf au lancement du container.

```php
<?php
        $ip_static = getenv('STATIC_APP');
        $ip_dyn = getenv("DYNAMIC_APP");
?>

<VirtualHost *:80>
        ServerName demo.res.ch

        #ErrorLog ${APACHE_LOG_DIR}/error.log
        #CustomLog ${APACHE_LOG_DIR}/access.log combined

        ProxyPass '/api/students/' 'http://<?php print "$ip_dyn";?>/'
        ProxyPassReverse '/api/sutdents' 'http://<?php print "$ip_dyn";?>/'

        ProxyPass '/' 'http://<?php print "$ip_static";?>/'
        ProxyPassReverse '/' 'http://<?php print "$ip_static";?>/'

</VirtualHost>
```

Re modifier le docker file:

```dockerfile
FROM php:7.0-apache

RUN apt-get update && \
        apt-get install -y vim

COPY conf/ /etc/apache2
COPY template/ /var/apache2/template/
COPY apache2-foreground /usr/local/bin/

RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001-*
```

recompiler le reverse proxy

```bash
docker build -t res/apache_rp .
docker run -p 8020:80 -e STATIC_APP=172.17.0.3:80 -e DYNAMIC_APP=172.17.0.2:3000 res/apache_rp 
# Sans le -d pour voir les erreurs et les #echo du du fichier apache2-foreground
```

 On peut définir les adresses de l'application statique et dynamique au lancement du container reverse proxy. Pour tester j'ai simplement inversé l'ordre de lancement des deux serveurs, ce qui fait que l'application statique est maintenant à l'adresse 172.17.0.**3**.

Et on peut tester le serveur depuis l'host qui fonctionne comme avant.

On peut maintenant configurer l'adresse ip de nos serveurs dynamiquement.

## Task6

## Load-Balancing

###### Sources :

- https://www.youtube.com/watch?v=se4PhIwyWLw&t=157
- https://support.rackspace.com/how-to/simple-load-balancing-with-apache/
- https://httpd.apache.org/docs/2.4/fr/mod/mod_proxy_balancer.html

Dans cette étape, il s'agit du configurer un serveur apace pour faire du load balancing. Ce mécanisme permet de répartir le travail des requêtes arrivantes sur plusieurs serveurs internes de manière à se répartir la charge de travaille.

Pour cette partie, nous allons tout d'abord créer deux dossier contenant la configuration de deux serveurs apache extrêmement simple :

```bash
#Répertoire courant : docker-images
mkdir apache_srv_img1
mkdir apache_srv_img2

# Création des fichiers Dockerfile. sur la base de ceux utilisé pour apache-php
cp apache-php-image/Dockerfile apache_srv_img1/Dockerfile 
cp apache-php-image/Dockerfile apache_srv_img2/Dockerfile 

# Création des dossier "content" qui sera copier dans "/var/www/html/"
mkdir apache_srv_img1/content
mkdir apache_srv_img2/content

#Création des fichiers :
echo -e "<h2> serveur 01 répond </h2>\nTest load balancing" > apache_srv_img1/content/index.html

echo -e "<h2> serveur 02 répond </h2>\nTest load balancing" > apache_srv_img1/content/index.html
```

Créer un build de ces containers et les tester.

```bash
apache_srv_img1$ docker build -t res/serveur_1 .
apache_srv_img2$ docker build -t res/serveur_2 .
```

Lancer les container et tester en mappant un port.

```bash
docker run -d -p 8000:80 res/serveur_1
docker run -d -p 8001:80 res/serveur_2
```

=> les deux containers fonctionnent.

![apache_lb](/Users/Maxime/Documents/HEIG-VD/Semestre 6/RES/Labo_infrastructure/RES_Projet_Infra_DH_YA.png)

On peut les relance sans les lier à un port spécifique

```bash
docker run -d  res/serveur_1 # IP 172.17.0.5
docker run -d  res/serveur_2 # IP 172.17.0.6

```

Créer maintenant un nouveau dossier qui sera le dossier du container apache load-balancing de test. Créer également un dossier de configuration d'apache et un Dockerfile

```bash
mkdir apache-load-balancing
cd apache-load-balancing
touch Dockerfile
mkdir conf
cd conf
mkdir sites-available
cd sites-available
touch 001-load-balancing.conf
```

remplir le Dockerfile comme ci-dessous :

```dockerfile
FROM php:7.0-apache

RUN apt-get update && \
        apt-get install -y vim

COPY conf/ /etc/apache2/

RUN a2enmod lbmethod_byrequests
RUN a2enmod proxy_balancer
RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001*
```

Et le fichier de configuration Apache comme suit :

```bash
<VirtualHost *:80>
        ProxyRequests off

        ServerName test.res.ch

        <Proxy balancer://mycluster>
                # IP du container du serveur 1
                BalancerMember http://172.17.0.5:80
                # IP du container du serveur 2
                BalancerMember http://172.17.0.6:80

                # Security "technically we aren't blocking
                # anyone but this is the place to make
                # those changes.
                Require all granted
                # In this example all requests are allowed.

                # Load Balancer Settings
                # We will be configuring a simple Round
                # Robin style load balancer.  This means
                # that all webheads take an equal share of
                # of the load.
                ProxySet lbmethod=byrequests

        </Proxy>

        # balancer-manager
        # This tool is built into the mod_proxy_balancer
        # module and will allow you to do some simple
        # modifications to the balanced group via a gui
        # web interface.
        <Location /balancer-manager>
                SetHandler balancer-manager

                # I recommend locking this one down to your
                # your office
                Require host test.res.ch

        </Location>

        # Point of Balance
        # This setting will allow to explicitly name the
        # the location in the site that we want to be
        # balanced, in this example we will balance "/"
        # or everything in the site.
        ProxyPass /balancer-manager !
        ProxyPass / balancer://mycluster/

</VirtualHost>
```

Créer un build de ce containter et lancer le.

```bash
docker build -t res/apache_lb .
docker run -p 8030:80 -d res/apache_lb
```



A cet étape nos deux petit serveurs de démonstrations se partagent la charge. Si on désactive le cache du navigateur, on remarque que le texte change à chaque raffraichissement de la page.

![load_balance](/Users/Maxime/Documents/HEIG-VD/Semestre 6/RES/Labo_infrastructure/load_balance.png)

<u>**Intégrer maintenant cette configuration au reverse proxy:**</u>

Copier la configuration actuelle du reverse proxy dans un nouveau dossier

```bash
cp -R apache-reverse-proxy/ apache-rp-lb/
```

Rajouter ensuite le module nécessaire dans le Dockerfile

```dockerfile
FROM php:7.0-apache

RUN apt-get update && \
        apt-get install -y vim

COPY apache2-foreground /usr/local/bin/
COPY conf/ /etc/apache2
COPY template/ /var/apache2/template/


RUN a2enmod proxy proxy_http proxy_balancer lbmethod_byrequests
RUN a2ensite 000-* 001-*
```

éditer ensuite le fichier de template 

```php

<?php
        $ip_static = getenv('STATIC_APP');
        $ip_static2 = getenv('STATIC_APP2');
        $ip_dynamic = getenv('DYNAMIC_APP');
        $ip_dynamic2 = getenv('DYNAMIC_APP2');
?>

<VirtualHost *:80>
        ServerName test.res.ch

        <Proxy balancer://staticclust>
                # Static
                BalancerMember 'http://<?php print "$ip_static"?>'
                BalancerMember 'http://<?php print "$ip_static2"?>'

                ProxySet lbmethod=byrequests
        </Proxy>


        <Proxy balancer://dynclust>
                BalancerMember 'http://<?php print "$ip_dynamic"?>'
                BalancerMember 'http://<?php print "$ip_dynamic2"?>'

                ProxySet lbmethod=byrequests
        </Proxy>

        <Location /balancer-manager>
            SetHandler balancer-manager
        </Location>

        ProxyPass '/balancer-manager' '!'

        ProxyPass '/api/students/' 'balancer://dynclust/'
        ProxyPassReverse '/api/students/' 'balancer://dynclust/'

        ProxyPass '/' 'balancer://staticclust/'
        ProxyPassReverse '/' 'balancer://staticclust/'
        

</VirtualHost>

```

On créer 2 load-balancer appelés :

- staticclust
- dynclust

Dans lesquels on met respectivement les deux containers static qui seront lancés.

On lance les containers respectifs et on récupère leur adresse ip

```bash
docker run -d res/apache_php  # 172.17.0.2
docker run -d res/apache_php  # 172.17.0.3
docker run -d res/node_js     # 172.17.0.4
docker run -d res/node_js     # 172.17.0.5
```

Build le nouveau container et le lancer.

```bash
docker build -t res/apache-rp-lb .
docker run -p 8010:80 -e STATIC_APP=172.17.0.2:80 -e STATIC_APP2=172.17.0.3:80 -e DYNAMIC_APP=172.17.0.4:3000 -e DYNAMIC_APP2=172.17.0.5:3000 -d res/apache-rp-lb
```

On fait quelque modification dans le fichier html d'un des containers apache pour voir la différence entre les deux réponses.

```bash
 docker ps
CONTAINER ID        IMAGE               COMMAND                  CREATED             STATUS              PORTS                  NAMES
fab5fbf8912c        res/apache-rp-lb    "docker-php-entrypoi…"   15 minutes ago      Up 15 minutes       0.0.0.0:8010->80/tcp   wonderful_shannon
5e03d503e654        res/node_js         "node /opt/app/index…"   2 hours ago         Up 2 hours                                 awesome_mcnulty
4fcffdf1fc10        res/node_js         "node /opt/app/index…"   2 hours ago         Up 2 hours                                 frosty_tesla
e830395669ce        res/apache_php      "docker-php-entrypoi…"   2 hours ago         Up 2 hours          80/tcp                 thirsty_leakey
014aa1f2195c        res/apache_php      "docker-php-entrypoi…"   2 hours ago         Up 2 hours          80/tcp                 silly_ride
docker exec -ti silly_ride /bin/bash
:/var/www/html# vi index.html 
```

On teste pour voir le bon fonctionnement. A chaque rafraichissement de page le texte doit changer en fonction de ce que vous avez ajouté.

## Task7

Load balancer avec Sticky session. Jusqu'à maintenant nous avons vu comment répartir la charge de traîtement sur différents serveurs. Mais cela peur s'avérer problèmatique si le serveur auquel on s'adresse change à chaque nouvelle requête, par exemple dans le cas ou l'on veut travailler avec des données locales au serveur. Dans ce cas on peut ajouter un mécanisme de "stick session" qui permet de lier un clien à une route, ce qui permet de fixer la route définie pour chaque nouveau client à l'aide de cookie.

Pour faire ça on va utiliser la commande wget qui permet d'afficher rapidement les cookies.

Dans un premier temps il faut modifier le Dockerfile du serveur-rp-lb, pour y ajouter l'activation du module "headers" :

```dockerfile
FROM php:7.0-apache

RUN apt-get update && \
        apt-get install -y vim

COPY conf/ /etc/apache2
COPY template/ /var/apache2/template/
COPY apache2-foreground /usr/local/bin/

RUN a2enmod proxy_balancer lbmethod_byrequests
RUN a2enmod proxy proxy_http 
RUN a2enmod headers
RUN a2ensite 000-* 001-*

```

Ensuite on modifie le fichier config-template.php pour ajouter le mécanisme de stick session lorsque les requêtes envoyées concerne le serveure apache-php:

```

<?php
        // BALANCER_WORKER_ROUTE est la route du membre du groupe de répartition de charge qui sera utilisé pour la requête courante.
        // BALANCE_ROUTE_CHANGED vaut 1 si la route de session n'est pas la même que la route de travail

        // Récupération des adresses ips
        $ip_static = getenv('STATIC_APP');
        $ip_static2 = getenv('STATIC_APP2');
        $ip_dynamic = getenv('DYNAMIC_APP');
        $ip_dynamic2 = getenv('DYNAMIC_APP2');
?>

<VirtualHost *:80>
        ServerName test.res.ch

        
        Header add Set-Cookie "ROUTEID=.%{BALANCER_WORKER_ROUTE}e; path=/" env=BALANCER_ROUTE_CHANGED

        <Proxy balancer://staticclust>
                # Static
                BalancerMember 'http://<?php print "$ip_static"?>'  route=node1
                BalancerMember 'http://<?php print "$ip_static2"?>' route=node2

                ProxySet lbmethod=byrequests
                ProxySet stickysession=ROUTEID
        </Proxy>


        <Proxy balancer://dynclust>
                BalancerMember 'http://<?php print "$ip_dynamic"?>'
                BalancerMember 'http://<?php print "$ip_dynamic2"?>'

                ProxySet lbmethod=byrequests
        </Proxy>

        <Location /balancer-manager>
            SetHandler balancer-manager
        </Location>

        ProxyPass '/balancer-manager' '!'

        ProxyPass '/api/students/' 'balancer://dynclust/'
        ProxyPassReverse '/api/students/' 'balancer://dynclust/'

        ProxyPass '/' 'balancer://staticclust/'
        ProxyPassReverse '/' 'balancer://staticclust/'
        

</VirtualHost>

```

On peut stopper le container "apache-rp-lb" re builder l'image et la lancer à nouveau :

```bash
docker build -t res/apache-rp-lb .
docker run -p 8010:80 -e STATIC_APP=172.17.0.2:80 -e STATIC_APP2=172.17.0.3:80 -e DYNAMIC_APP=172.17.0.4:3000 -e DYNAMIC_APP2=172.17.0.5:3000 -d res/apache-rp-lb
```

Premièrement on remaque que le texte ne change plus lorsqu'on charge la page à nouveau. Ensuite on peut utiliser la commande wget pour voir le cookie qui a été créer et contrôler qu'on le reçoit uniquement pour les pages statiques :

```bash
wget -S test.res.ch:8010/api/students/
--2018-06-10 19:34:59--  http://test.res.ch:8010/api/students/
Résolution de test.res.ch (test.res.ch)… 192.168.99.100
Connexion à test.res.ch (test.res.ch)|192.168.99.100|:8010… connecté.
requête HTTP transmise, en attente de la réponse… 
  HTTP/1.1 200 OK
  Date: Sun, 10 Jun 2018 17:35:00 GMT
  Server: Apache/2.4.25 (Debian)
  X-Powered-By: Express
  Content-Type: application/json; charset=utf-8
  Content-Length: 592
  ETag: W/"250-Qy06CPWaBqtZjqj+YnY5LliqL68"
  Keep-Alive: timeout=5, max=100
  Connection: Keep-Alive
Taille : 592 [application/json]
Sauvegarde en : « index.html.7 »

index.html.7                         100%[======================================================================>]     592  --.-KB/s    ds 0s      

2018-06-10 19:35:00 (70,6 MB/s) — « index.html.7 » sauvegardé [592/592]

wget -S test.res.ch:8010
--2018-06-10 19:35:03--  http://test.res.ch:8010/
Résolution de test.res.ch (test.res.ch)… 192.168.99.100
Connexion à test.res.ch (test.res.ch)|192.168.99.100|:8010… connecté.
requête HTTP transmise, en attente de la réponse… 
  HTTP/1.1 200 OK
  Date: Sun, 10 Jun 2018 17:35:03 GMT
  Server: Apache/2.4.25 (Debian)
  Last-Modified: Sun, 10 Jun 2018 12:56:49 GMT
  ETag: "b05-56e492a913f4e"
  Accept-Ranges: bytes
  Content-Length: 2821
  Vary: Accept-Encoding
  Content-Type: text/html
  Set-Cookie: ROUTEID=.node1; path=/		<====== ON VOIT LE COOKIE ICI 
  Keep-Alive: timeout=5, max=100
  Connection: Keep-Alive
Taille : 2821 (2,8K) [text/html]
Sauvegarde en : « index.html.8 »

index.html.8                         100%[======================================================================>]   2,75K  --.-KB/s    ds 0s      

2018-06-10 19:35:03 (224 MB/s) — « index.html.8 » sauvegardé [2821/2821]

mbp-de-maxime-2:homebrew Maxime$ 
```

On voit que le cookie est délivré uniquement pour les pages statiques. On remarque qu'il a choisi le 1er noeud pour répondre à ce client.

On peut aussi le voir à travers le navigateur : 

![cookie](/Users/Maxime/Documents/HEIG-VD/Semestre 6/RES/Labo_infrastructure/cookie.png)

On peut modifier le cookie à la main pour s'assurer que les deux serveurs sont toujours fonctionnels.