FROM php:7.0-apache

RUN apt-get update && \
        apt-get install -y vim

COPY conf/ /etc/apache2
COPY template/ /var/apache2/template/
COPY apache2-foreground /usr/local/bin/

RUN a2enmod lbmethod_byrequests
RUN a2enmod proxy_balancer
RUN a2enmod proxy proxy_http 
RUN a2ensite 000-* 001-*